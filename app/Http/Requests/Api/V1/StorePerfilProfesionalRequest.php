<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerfilProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_municipio' => [
                'required',
                'integer',
                Rule::exists('municipios', 'id_municipio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],

            'descripcion' => [
                'required',
                'string',
            ],

            'oficios' => [
                'required',
                'array',
                'min:1',
            ],

            'oficios.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('oficios', 'id_oficio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],
        ];
    }
}