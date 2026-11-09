<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CrearPublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_publicacion' => [
                'required',
                'integer',
                'exists:tipos_publicacion,id_tipo_publicacion',
            ],

            'titulo' => [
                'required',
                'string',
                'min:10',
                'max:150',
            ],

            'descripcion' => [
                'required',
                'string',
                'min:20',
                'max:5000',
            ],
        ];
    }
}