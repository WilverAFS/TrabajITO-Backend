<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['nombre', 'institucion_emisora'] as $campo) {
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
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],
            'institucion_emisora' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],
            'fecha_emision' => [
                'required',
                'date_format:Y-m-d',
            ],
            'fecha_vencimiento' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:fecha_emision',
            ],
            'documento' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            // El cliente no puede enviar el estado de verificación.
            'verificado' => [
                'missing',
            ],
        ];
    }
}
