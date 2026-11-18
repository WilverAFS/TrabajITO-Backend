<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BuscarOficiosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $buscar = $this->query('buscar');

        if (is_string($buscar)) {
            $this->merge(['buscar' => trim($buscar)]);
        }
    }

    public function rules(): array
    {
        return [
            'buscar' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }
}
