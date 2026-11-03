<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'apellido' => [
                'sometimes',
                'string',
                'max:150',
            ],

            'correo' => [
                'sometimes',
                'email',
                Rule::unique('users', 'correo')
                    ->ignore(
                        $this->user()->id_usuario,
                        'id_usuario'
                    ),
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }
}