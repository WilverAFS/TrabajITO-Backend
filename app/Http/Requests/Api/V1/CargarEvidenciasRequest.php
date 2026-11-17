<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CargarEvidenciasRequest extends FormRequest
{
    /**
     * Solo usuarios autenticados.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'archivos' => [
                'required',
                'array',
                'min:1',
                'max:13',
            ],
            'archivos.*' => [
                'required',
                'file',
                'max:102400',
                'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif,video/mp4,video/webm,video/quicktime',
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'archivos.required' => 'Debes cargar al menos un archivo.',
            'archivos.array' => 'La lista de archivos no es válida.',
            'archivos.min' => 'Debes cargar al menos un archivo.',
            'archivos.max' => 'No puedes enviar más de 13 archivos en una solicitud.',
            'archivos.*.required' => 'Todos los elementos deben ser archivos válidos.',
            'archivos.*.file' => 'Uno de los elementos enviados no es un archivo válido.',
            'archivos.*.max' => 'Cada archivo debe ser menor o igual a 100 MB.',
            'archivos.*.mimetypes' => 'Solo se permiten imágenes JPEG, PNG, WebP, HEIC, HEIF y videos MP4, WebM o MOV.',
        ];
    }
}
