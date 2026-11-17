<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePerfilProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $descripcion = $this->input('descripcion');

        if (is_string($descripcion)) {
            $this->merge(['descripcion' => trim($descripcion)]);
        }
    }

    public function rules(): array
    {
        return [
            'id_municipio' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::exists('municipios', 'id_municipio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],
            'descripcion' => [
                'sometimes',
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $datos = $this->all();

            if (
                ! array_key_exists('id_municipio', $datos) &&
                ! array_key_exists('descripcion', $datos)
            ) {
                $validator->errors()->add(
                    'perfil',
                    'Debes proporcionar al menos un campo para actualizar.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'descripcion.min' => 'La descripción profesional debe contener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción profesional no puede superar 2000 caracteres.',
        ];
    }
}
