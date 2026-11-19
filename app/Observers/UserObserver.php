<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\BienvenidaUsuario;
use App\Notifications\CambioContrasena;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

class UserObserver implements ShouldHandleEventsAfterCommit
{
    public function created(User $user): void
    {
        try {
            $user->notify(new BienvenidaUsuario());
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged('contrasena_hash')) {
            return;
        }

        try {
            $user->notify(new CambioContrasena());
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
