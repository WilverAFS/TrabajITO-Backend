<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use RuntimeException;

class PrivateMediaResponse
{
    /**
     * Devuelve un archivo privado en línea, sin exponer su ruta física.
     */
    public static function inline(
        string $diskName,
        string $path,
        string $filename
    ): StreamedResponse {
        $disk = Storage::disk($diskName);
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException(
                'No fue posible abrir el archivo solicitado.'
            );
        }

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';
        $size = $disk->size($path);

        // El nombre que se expone al navegador es generado por el servidor.
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);

        return response()->stream(
            function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => (string) $size,
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'no-referrer',
            ]
        );
    }
}
