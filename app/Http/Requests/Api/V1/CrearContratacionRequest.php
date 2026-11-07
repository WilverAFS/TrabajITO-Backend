<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearContratacionRequest extends FormRequest
{
    /**
     * Solo los usuarios autenticados pueden
     * solicitar una contratación.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validaciones de la solicitud.
     */
    public function rules(): array
    {
        return [
            'id_trabajador' => [
                'required',
                'integer',
                Rule::exists(
                    'perfiles_profesionales',
                    'id_trabajador'
                ),
            ],

            'descripcion_servicio' => [
                'required',
                'string',
                'min:1',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'id_trabajador.required' =>
                'Debes seleccionar un trabajador.',

            'id_trabajador.integer' =>
                'El identificador del trabajador debe ser válido.',

            'id_trabajador.exists' =>
                'El trabajador seleccionado no existe.',

            'descripcion_servicio.required' =>
                'Debes describir el servicio que necesitas.',

            'descripcion_servicio.string' =>
                'La descripción del servicio debe ser texto.',

            'descripcion_servicio.min' =>
                'La descripción del servicio no puede estar vacía.',
        ];
    }
}
