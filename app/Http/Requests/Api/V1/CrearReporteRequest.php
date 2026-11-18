<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CrearReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['motivo', 'descripcion'] as $campo) {
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
                'min:3',
                'max:150',
            ],
            'descripcion' => [
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
            'id_objetivo.integer' => 'El identificador del objetivo debe ser numérico.',
            'id_objetivo.min' => 'El identificador del objetivo debe ser mayor que cero.',
            'motivo.min' => 'El motivo debe contener al menos 3 caracteres.',
            'descripcion.min' => 'La descripción debe contener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción no puede superar 2000 caracteres.',
        ];
    }
}
