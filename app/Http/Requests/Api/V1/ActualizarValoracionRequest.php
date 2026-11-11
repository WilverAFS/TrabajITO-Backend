<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarValoracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calificacion' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5',
            ],

            'comentario' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }
}
