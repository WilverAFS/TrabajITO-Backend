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
            ],

            'archivos.*' => [
                'required',
                'file',

                /*
                 * Imágenes:
                 * jpeg
                 * jpg
                 * png
                 * webp
                 * heic
                 * heif
                 *
                 * Videos:
                 * mp4
                 * webm
                 * mov
                 */
                'mimetypes:'
                . 'image/jpeg,'
                . 'image/png,'
                . 'image/webp,'
                . 'image/heic,'
                . 'image/heif,'
                . 'video/mp4,'
                . 'video/webm,'
                . 'video/quicktime',

                /*
                 * La validacion del tamaño maximo permitido se maneja en el controlador
                 * Imagen = 10MB
                 * Video = 100MB
                 */
            ],
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [

            'archivos.required' =>
                'Debes cargar al menos un archivo.',

            'archivos.array' =>
                'La lista de archivos no es válida.',

            'archivos.min' =>
                'Debes cargar al menos un archivo.',

            'archivos.*.required' =>
                'Todos los elementos deben ser archivos válidos.',

            'archivos.*.file' =>
                'Uno de los elementos enviados no es un archivo válido.',

            'archivos.*.mimetypes' =>
                'Solo se permiten imágenes JPEG, PNG, WebP, HEIC, HEIF y videos MP4, WebM o MOV.',

            'archivos.*.max' =>
                'Cada archivo debe ser menor o igual a 100 MB.',
        ];
    }
}