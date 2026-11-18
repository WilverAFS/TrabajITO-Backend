<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];
        $correo = $this->input('correo');

        if (is_string($correo)) {
            $datos['correo'] = mb_strtolower(trim($correo), 'UTF-8');
        }

        // El contrato API v1 define device_name como opcional.
        if (! $this->exists('device_name')) {
            $datos['device_name'] = 'TrabajITO Web';
        } elseif (is_string($this->input('device_name'))) {
            $datos['device_name'] = trim($this->input('device_name'));
        }

        if ($datos !== []) {
            $this->merge($datos);
        }
    }

    public function rules(): array
    {
        return [
            'correo' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            // No se exige complejidad aquí: se permiten contraseñas heredadas.
            'password' => [
                'required',
                'string',
            ],
            'device_name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe ser válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'device_name.required' => 'El nombre del dispositivo no puede estar vacío.',
            'device_name.max' => 'El nombre del dispositivo no puede superar 255 caracteres.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
