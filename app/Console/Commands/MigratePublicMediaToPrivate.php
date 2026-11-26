<?php

namespace App\Console\Commands;

use App\Models\Certificado;
use App\Models\Evidencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePublicMediaToPrivate extends Command
{
    protected $signature = 'media:migrate-public-to-private
                            {--dry-run : Mostrar los archivos que se migrarían sin modificarlos}';

    protected $description = 'Migra certificados y evidencias del disco público al disco privado.';

    private int $migrated = 0;
    private int $alreadyPrivate = 0;
    private int $missing = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');

        $this->info(
            $dryRun
                ? 'Modo de simulación: no se copiarán ni eliminarán archivos.'
                : 'Iniciando migración de archivos al disco privado.'
        );

        $this->migrateModel(
            Certificado::class,
            'ruta_documento',
            'certificados',
            $public,
            $private,
            $dryRun
        );

        $this->migrateModel(
            Evidencia::class,
            'ruta_archivo',
            'publicaciones',
            $public,
            $private,
            $dryRun
        );

        $this->newLine();
        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Migrados', $this->migrated],
                ['Ya privados', $this->alreadyPrivate],
                ['Archivos faltantes', $this->missing],
                ['Errores/conflictos', $this->errors],
            ]
        );

        if ($this->missing > 0 || $this->errors > 0) {
            $this->error(
                'La migración terminó con incidencias. Revisa los mensajes antes de continuar.'
            );

            return self::FAILURE;
        }

        $this->info(
            $dryRun
                ? 'Simulación finalizada. Ejecuta el comando sin --dry-run para aplicar los cambios.'
                : 'Migración finalizada correctamente.'
        );

        return self::SUCCESS;
    }

    private function migrateModel(
        string $modelClass,
        string $column,
        string $expectedDirectory,
        $public,
        $private,
        bool $dryRun
    ): void {
        $key = (new $modelClass())->getKeyName();

        $records = $modelClass::query()
            ->whereNotNull($column)
            ->orderBy($key)
            ->cursor();

        foreach ($records as $record) {
            $rawPath = (string) $record->{$column};
            $path = str_replace('\\', '/', $rawPath);

            if (! $this->isSafePath($path, $expectedDirectory)) {
                $this->errors++;
                $this->error(
                    "{$modelClass} #{$record->getKey()}: ruta no permitida: {$rawPath}"
                );
                continue;
            }

            $publicExists = $public->exists($path);
            $privateExists = $private->exists($path);

            if ($privateExists && ! $publicExists) {
                $this->alreadyPrivate++;
                continue;
            }

            if (! $publicExists && ! $privateExists) {
                $this->missing++;
                $this->warn(
                    "{$modelClass} #{$record->getKey()}: no existe en ninguno de los discos: {$path}"
                );
                continue;
            }

            if ($privateExists && $publicExists) {
                $publicHash = $this->hashFile($public, $path);
                $privateHash = $this->hashFile($private, $path);

                if (! $publicHash || $publicHash !== $privateHash) {
                    $this->errors++;
                    $this->error(
                        "{$modelClass} #{$record->getKey()}: existe un archivo privado distinto; no se eliminó la copia pública: {$path}"
                    );
                    continue;
                }

                if (! $dryRun && ! $public->delete($path)) {
                    $this->errors++;
                    $this->error(
                        "{$modelClass} #{$record->getKey()}: no se pudo retirar la copia pública: {$path}"
                    );
                    continue;
                }

                $this->alreadyPrivate++;
                $this->line(
                    ($dryRun ? '[SIMULACIÓN] Retiraría duplicado público: ' : 'Duplicado público retirado: ')
                    . $path
                );
                continue;
            }

            if ($dryRun) {
                $this->migrated++;
                $this->line('[SIMULACIÓN] Migraría: ' . $path);
                continue;
            }

            $stream = $public->readStream($path);

            if (! is_resource($stream)) {
                $this->errors++;
                $this->error(
                    "{$modelClass} #{$record->getKey()}: no se pudo leer: {$path}"
                );
                continue;
            }

            try {
                $written = $private->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $written || ! $private->exists($path)) {
                $this->errors++;
                $this->error(
                    "{$modelClass} #{$record->getKey()}: no se pudo copiar al disco privado: {$path}"
                );
                continue;
            }

            $publicHash = $this->hashFile($public, $path);
            $privateHash = $this->hashFile($private, $path);

            if (! $publicHash || $publicHash !== $privateHash) {
                $private->delete($path);
                $this->errors++;
                $this->error(
                    "{$modelClass} #{$record->getKey()}: la copia no coincide; se conservó el original público: {$path}"
                );
                continue;
            }

            if (! $public->delete($path)) {
                $this->errors++;
                $this->error(
                    "{$modelClass} #{$record->getKey()}: se copió, pero no se pudo eliminar el original público: {$path}"
                );
                continue;
            }

            $this->migrated++;
            $this->line('Migrado: ' . $path);
        }
    }

    private function isSafePath(
        string $path,
        string $expectedDirectory
    ): bool {
        if (
            $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, "\0")
        ) {
            return false;
        }

        $segments = explode('/', $path);

        return ! in_array('..', $segments, true)
            && str_starts_with($path, $expectedDirectory . '/');
    }

    private function hashFile($disk, string $path): ?string
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
