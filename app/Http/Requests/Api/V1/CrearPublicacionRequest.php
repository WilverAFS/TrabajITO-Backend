<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CrearPublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Preparar los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['titulo', 'descripcion'] as $campo) {
            $valor = $this->input($campo);

            if (is_string($valor)) {
                $datos[$campo] = trim($valor);
            }
        }

        if ($datos !== []) {
            $this->merge($datos);
        }
    }

    public function rules(): array
    {
        return [
            'id_tipo_publicacion' => [
                'required',
                'integer',
                'min:1',
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

    public function messages(): array
    {
        return [
            'titulo.min' => 'El título debe contener al menos 10 caracteres.',
            'titulo.max' => 'El título no puede superar 150 caracteres.',
            'descripcion.min' => 'La descripción debe contener al menos 20 caracteres.',
            'descripcion.max' => 'La descripción no puede superar 5000 caracteres.',
        ];
    }
}
