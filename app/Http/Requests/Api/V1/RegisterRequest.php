<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
                'correo' => mb_strtolower(trim($correo)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:150'],
            'correo' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'correo'),
            ],
            'telefono' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10}$/',
                Rule::unique('users', 'telefono'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}