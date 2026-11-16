<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOficioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',

                Rule::unique(
                    'oficios',
                    'nombre'
                )->ignore(
                    $this->route('id_oficio'),
                    'id_oficio'
                ),
            ],

            'descripcion' => [
                'sometimes',
                'string',
                'max:500',
            ],
        ];
    }
}