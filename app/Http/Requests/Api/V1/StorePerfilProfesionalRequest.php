<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePerfilProfesionalRequest extends FormRequest
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
                'required',
                'integer',
                'min:1',
                Rule::exists('municipios', 'id_municipio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],
            'descripcion' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'oficios' => [
                'required',
                'array',
                'min:1',
            ],
            'oficios.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
                Rule::exists('oficios', 'id_oficio')
                    ->where(fn ($query) => $query->where('activo', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.min' => 'La descripción profesional debe contener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción profesional no puede superar 2000 caracteres.',
            'id_municipio.integer' => 'El municipio debe ser un identificador numérico válido.',
            'oficios.*.integer' => 'Cada oficio debe ser un identificador numérico válido.',
        ];
    }
}
