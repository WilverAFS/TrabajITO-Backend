<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ActualizarValoracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $comentario = $this->input('comentario');

        if (is_string($comentario)) {
            $comentario = trim($comentario);
            $this->merge([
                'comentario' => $comentario === '' ? null : $comentario,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'calificacion' => [
                'sometimes',
                'required',
                'integer',
                'between:1,5',
            ],
            'comentario' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                ! array_key_exists('calificacion', $this->all()) &&
                ! array_key_exists('comentario', $this->all())
            ) {
                $validator->errors()->add(
                    'valoracion',
                    'Debes proporcionar al menos un campo para actualizar.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'calificacion.integer' => 'La calificación debe ser un número entero.',
            'calificacion.between' => 'La calificación debe estar entre 1 y 5.',
            'comentario.max' => 'El comentario no puede superar 1000 caracteres.',
        ];
    }
}
