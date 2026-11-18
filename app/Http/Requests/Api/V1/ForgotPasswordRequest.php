<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $correo = $this->input('correo');

        if (is_string($correo)) {
            $this->merge([
                'correo' => mb_strtolower(trim($correo), 'UTF-8'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'correo' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ];
    }
}
