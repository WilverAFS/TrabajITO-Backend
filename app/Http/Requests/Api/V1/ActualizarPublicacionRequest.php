<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_publicacion' => [
                'sometimes',
                'integer',
                'exists:tipos_publicacion,id_tipo_publicacion',
            ],

            'titulo' => [
                'sometimes',
                'string',
                'min:10',
                'max:150',
            ],

            'descripcion' => [
                'sometimes',
                'string',
                'min:20',
                'max:5000',
            ],
        ];
    }
}