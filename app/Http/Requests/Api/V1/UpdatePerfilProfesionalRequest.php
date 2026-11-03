<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePerfilProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_municipio' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('municipios', 'id_municipio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],

            'descripcion' => [
                'sometimes',
                'required',
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $datos = $this->all();

            if (
                !array_key_exists('id_municipio', $datos) &&
                !array_key_exists('descripcion', $datos)
            ) {
                $validator->errors()->add(
                    'perfil',
                    'Debes proporcionar al menos un campo para actualizar.'
                );
            }
        });
    }
}