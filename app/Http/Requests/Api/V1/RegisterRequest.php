<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['nombre', 'apellido', 'telefono'] as $campo) {
            $valor = $this->input($campo);

            if (is_string($valor)) {
                $datos[$campo] = trim($valor);
            }
        }

        $correo = $this->input('correo');

        if (is_string($correo)) {
            $datos['correo'] = mb_strtolower(trim($correo), 'UTF-8');
        }

        if ($datos !== []) {
            $this->merge($datos);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                ValidationRules::nombreEspanol(),
            ],
            'apellido' => [
                'required',
                'string',
                'max:150',
                ValidationRules::nombreEspanol(),
            ],
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
                'regex:/\A[0-9]{10}\z/',
                Rule::unique('users', 'telefono'),
            ],
            'password' => [
                'required',
                'string',
                ValidationRules::password(),
                'confirmed',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('correo')) {
                return;
            }

            $correo = $this->input('correo');

            if (! is_string($correo)) {
                return;
            }

            $duplicado = User::query()
                ->whereRaw('LOWER(correo) = ?', [mb_strtolower($correo, 'UTF-8')])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'correo',
                    'Ya existe una cuenta registrada con ese correo.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido.regex' => 'Los apellidos solo pueden contener letras y espacios.',
            'telefono.regex' => 'El teléfono debe contener exactamente 10 dígitos.',
            'password' => 'La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
