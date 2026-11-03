<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilOficiosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oficios' => [
                'required',
                'array',
                'min:1',
            ],

            'oficios.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('oficios', 'id_oficio')
                    ->where(
                        fn ($query) => $query->where('activo', true)
                    ),
            ],
        ];
    }
}