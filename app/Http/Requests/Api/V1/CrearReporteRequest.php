<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CrearReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_objetivo' => [
                'required',
                'string',
                'in:PUBLICACION,PERFIL',
            ],

            'id_objetivo' => [
                'required',
                'integer',
                'min:1',
            ],

            'motivo' => [
                'required',
                'string',
                'max:150',
            ],

            'descripcion' => [
                'required',
                'string',
            ],
        ];
    }
}
