<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Oficio;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOficioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['nombre', 'descripcion'] as $campo) {
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
                'max:100',
                ValidationRules::nombreEspanol(),
                Rule::unique('oficios', 'nombre'),
            ],
            'descripcion' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('nombre')) {
                return;
            }

            $nombre = $this->input('nombre');

            if (! is_string($nombre)) {
                return;
            }

            $duplicado = Oficio::query()
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre, 'UTF-8')])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'nombre',
                    'Ya existe un oficio con ese nombre, sin distinguir mayúsculas y minúsculas.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre del oficio solo puede contener letras y espacios.',
            'descripcion.required' => 'La descripción del oficio es obligatoria.',
        ];
    }
}
