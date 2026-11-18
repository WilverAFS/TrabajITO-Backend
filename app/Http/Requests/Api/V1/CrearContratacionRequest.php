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

    protected function prepareForValidation(): void
    {
        $descripcion = $this->input('descripcion_servicio');

        if (is_string($descripcion)) {
            $this->merge(['descripcion_servicio' => trim($descripcion)]);
        }
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
                'min:1',
                Rule::exists('perfiles_profesionales', 'id_trabajador'),
            ],
            'descripcion_servicio' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'id_trabajador.required' => 'Debes seleccionar un trabajador.',
            'id_trabajador.integer' => 'El identificador del trabajador debe ser numérico.',
            'id_trabajador.min' => 'El identificador del trabajador debe ser mayor que cero.',
            'id_trabajador.exists' => 'El trabajador seleccionado no existe.',
            'descripcion_servicio.required' => 'Debes describir el servicio que necesitas.',
            'descripcion_servicio.min' => 'La descripción del servicio debe contener al menos 10 caracteres.',
            'descripcion_servicio.max' => 'La descripción del servicio no puede superar 2000 caracteres.',
        ];
    }
}
