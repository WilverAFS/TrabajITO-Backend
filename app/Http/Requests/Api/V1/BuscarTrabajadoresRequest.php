<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuscarTrabajadoresRequest extends FormRequest
{
    /**
     * La exploración de trabajadores es pública.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para los filtros de búsqueda.
     */
    public function rules(): array
    {
        return [
            'buscar' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'id_oficio' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'id_municipio' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'calificacion_minima' => [
                'sometimes',
                'numeric',
                'between:1,5',
            ],

            'dia_semana' => [
                'sometimes',
                'integer',
                'between:1,7',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'between:1,50',
            ],

            'sort' => [
                'sometimes',
                Rule::in([
                    'reputacion',
                    'fecha_registro',
                ]),
            ],

            'direction' => [
                'sometimes',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'buscar.max' => 'La búsqueda no puede superar los 150 caracteres.',

            'id_oficio.integer' => 'El oficio debe ser un identificador válido.',
            'id_municipio.integer' => 'El municipio debe ser un identificador válido.',

            'calificacion_minima.numeric' => 'La calificación debe ser numérica.',
            'calificacion_minima.between' => 'La calificación debe estar entre 1 y 5.',

            'dia_semana.between' => 'El día de la semana debe estar entre 1 y 7.',

            'page.min' => 'La página debe ser mayor o igual a 1.',
            'per_page.between' => 'La cantidad de resultados debe estar entre 1 y 50.',

            'sort.in' => 'El criterio de ordenamiento no es válido.',
            'direction.in' => 'La dirección debe ser asc o desc.',
        ];
    }
}
