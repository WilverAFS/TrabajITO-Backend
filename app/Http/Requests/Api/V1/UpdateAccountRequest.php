<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountRequest extends FormRequest
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

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $idUsuario = $this->user()->id_usuario;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                ValidationRules::nombreEspanol(),
            ],
            'apellido' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                ValidationRules::nombreEspanol(),
            ],
            'correo' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'correo')->ignore($idUsuario, 'id_usuario'),
            ],
            'telefono' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/\A[0-9]{10}\z/',
                Rule::unique('users', 'telefono')->ignore($idUsuario, 'id_usuario'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('correo') || ! $this->exists('correo')) {
                return;
            }

            $correo = $this->input('correo');

            if (! is_string($correo)) {
                return;
            }

            $duplicado = User::query()
                ->whereRaw('LOWER(correo) = ?', [mb_strtolower($correo, 'UTF-8')])
                ->where('id_usuario', '!=', $this->user()->id_usuario)
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'correo',
                    'Ese correo ya está registrado por otra cuenta.'
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
        ];
    }
}
