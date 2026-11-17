<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Oficio;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOficioRequest extends FormRequest
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
                'sometimes',
                'required',
                'string',
                'max:100',
                ValidationRules::nombreEspanol(),
                Rule::unique('oficios', 'nombre')->ignore(
                    $this->route('id_oficio'),
                    'id_oficio'
                ),
            ],
            'descripcion' => [
                'sometimes',
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $datos = $this->all();
            $campos = ['nombre', 'descripcion'];
            $hayCampo = false;

            foreach ($campos as $campo) {
                if (array_key_exists($campo, $datos)) {
                    $hayCampo = true;
                    break;
                }
            }

            if (! $hayCampo) {
                $validator->errors()->add(
                    'oficio',
                    'Debes proporcionar al menos un campo para actualizar.'
                );
                return;
            }

            if ($validator->errors()->has('nombre') || ! array_key_exists('nombre', $datos)) {
                return;
            }

            $nombre = $datos['nombre'];

            if (! is_string($nombre)) {
                return;
            }

            $duplicado = Oficio::query()
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre, 'UTF-8')])
                ->where('id_oficio', '!=', $this->route('id_oficio'))
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'nombre',
                    'Ya existe otro oficio con ese nombre, sin distinguir mayúsculas y minúsculas.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre del oficio solo puede contener letras y espacios.',
        ];
    }
}
