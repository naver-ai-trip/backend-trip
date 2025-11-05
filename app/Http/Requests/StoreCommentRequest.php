<?php

namespace App\Http\Requests;

use App\Models\MapCheckpoint;
use App\Models\Trip;
use App\Models\TripDiary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(['trip', 'map_checkpoint', 'trip_diary'])],
            'entity_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $entityType = $this->input('entity_type');
                    
                    if (!$entityType) {
                        return; // Let entity_type required validation handle this
                    }

                    // Convert entity_type string to model class
                    $modelClass = match ($entityType) {
                        'trip' => Trip::class,
                        'map_checkpoint' => MapCheckpoint::class,
                        'trip_diary' => TripDiary::class,
                        default => null,
                    };

                    if (!$modelClass) {
                        $fail('Invalid entity type.');
                        return;
                    }

                    // Check if entity exists
                    if (!$modelClass::find($value)) {
                        $fail('The selected entity does not exist.');
                    }
                },
            ],
            'content' => ['required', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'], // 10MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entity_type.required' => 'The entity type is required.',
            'entity_type.in' => 'The entity type must be trip, map_checkpoint, or trip_diary.',
            'entity_id.required' => 'The entity ID is required.',
            'entity_id.integer' => 'The entity ID must be an integer.',
            'content.required' => 'Content is required.',
            'content.max' => 'Content must not exceed 2000 characters.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be jpeg, jpg, png, gif, or webp format.',
            'images.*.max' => 'Each image must not exceed 10MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert entity_type to model class for storage
        if ($this->has('entity_type')) {
            $this->merge([
                'entity_class' => match ($this->input('entity_type')) {
                    'trip' => Trip::class,
                    'map_checkpoint' => MapCheckpoint::class,
                    'trip_diary' => TripDiary::class,
                    default => null,
                },
            ]);
        }
    }
}
