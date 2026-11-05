<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'dia_semana' => [
                'sometimes',
                'required',
                'integer',
                'between:1,7',
            ],

            'hora_inicio' => [
                'sometimes',
                'required',
                'date_format:H:i',
            ],

            'hora_fin' => [
                'sometimes',
                'required',
                'date_format:H:i',
            ],

            'activo' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $camposPermitidos = [
                'dia_semana',
                'hora_inicio',
                'hora_fin',
                'activo',
            ];

            $datos = $this->all();

            foreach ($camposPermitidos as $campo) {
                if (array_key_exists($campo, $datos)) {
                    return;
                }
            }

            $validator->errors()->add(
                'disponibilidad',
                'Debes proporcionar al menos un campo para actualizar.'
            );
        });
    }
}
