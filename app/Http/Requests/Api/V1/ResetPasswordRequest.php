<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $correo = $this->input('correo');

        if (is_string($correo)) {
            $this->merge([
                'correo' => mb_strtolower(trim($correo), 'UTF-8'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
            ],
            'correo' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                ValidationRules::password(),
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password' => 'La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
