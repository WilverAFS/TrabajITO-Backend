<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Reglas reutilizables de validación para la API de TrabajITO.
 */
final class ValidationRules
{
    /**
     * Letras del alfabeto latino (incluye acentos y ñ), separadas por espacios.
     * No permite números, guiones, apóstrofes ni otros símbolos.
     */
    public static function nombreEspanol(): string
    {
        return 'regex:/\A\p{Latin}[\p{Latin}\p{M}]*(?: \p{Latin}[\p{Latin}\p{M}]*)*\z/u';
    }

    /**
     * Política obligatoria para contraseñas nuevas.
     * No debe aplicarse al inicio de sesión para conservar cuentas existentes.
     */
    public static function password(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
