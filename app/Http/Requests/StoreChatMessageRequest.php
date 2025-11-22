<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'from_role' => ['nullable', 'string', 'in:user,assistant,system'],
            'message_type' => ['nullable', 'string', 'in:text,suggestion,action_result,error'],
            'metadata' => ['nullable', 'array'],
            'references' => ['nullable', 'array'],
            'references.*.type' => ['required_with:references', 'string'],
            'references.*.id' => ['required_with:references', 'integer'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The message content is required.',
            'from_role.in' => 'The from_role must be either user, assistant, or system.',
            'message_type.in' => 'The message_type must be text, suggestion, action_result, or error.',
        ];
    }
}
