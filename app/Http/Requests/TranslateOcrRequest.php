<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateOcrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated via sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'url', 'regex:/\.(jpeg|jpg|png|gif|webp)(\?.*)?$/i'], // Image URL
            'source_language' => ['nullable', 'string'], // Optional: auto-detect if not provided
            'target_language' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.url' => 'The image must be a valid URL.',
            'image.regex' => 'The image URL must point to a valid image file (jpeg, jpg, png, gif, webp).',
        ];
    }
}
