<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreFotoPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Debes seleccionar una fotografía.',
            'foto.file' => 'El archivo enviado no es válido.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'Solo se permiten imágenes JPEG, PNG o WebP.',
            'foto.max' => 'La fotografía no puede superar los 5 MB.',
        ];
    }
}
