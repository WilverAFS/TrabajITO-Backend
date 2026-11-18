<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password_actual' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                ValidationRules::password(),
                'confirmed',
                'different:password_actual',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password' => 'La nueva contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.different' => 'La nueva contraseña debe ser diferente de la contraseña actual.',
        ];
    }
}
