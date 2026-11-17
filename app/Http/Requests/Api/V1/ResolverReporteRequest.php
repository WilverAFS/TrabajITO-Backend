<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ResolverReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $resolucion = $this->input('resolucion');

        if (is_string($resolucion)) {
            $this->merge(['resolucion' => trim($resolucion)]);
        }
    }

    public function rules(): array
    {
        return [
            'resolucion' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'resolucion.min' => 'La resolución debe contener al menos 10 caracteres.',
            'resolucion.max' => 'La resolución no puede superar 2000 caracteres.',
        ];
    }
}
