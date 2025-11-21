<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate flight offer search requests.
 */
class SearchFlightOffersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Sanctum middleware gates access
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_location_code' => ['required', 'string', 'size:3'],
            'destination_location_code' => ['required', 'string', 'size:3'],
            'departure_date' => ['required', 'date', 'after_or_equal:today', 'date_format:Y-m-d'],
            'return_date' => ['nullable', 'date', 'after:departure_date', 'date_format:Y-m-d'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9'],
            'travel_class' => ['nullable', 'string', Rule::in(['ECONOMY', 'PREMIUM_ECONOMY', 'BUSINESS', 'FIRST'])],
            'non_stop' => ['nullable', 'boolean'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'max_price' => ['nullable', 'numeric', 'min:1'],
            'max' => ['nullable', 'integer', 'min:1', 'max:250'],
            'included_checked_bags_only' => ['nullable', 'boolean'],
            'one_way' => ['nullable', 'boolean'],
            'sources' => ['nullable', 'array', 'min:1'],
            'sources.*' => ['string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'origin_location_code.size' => 'Origin location code must be a 3-letter IATA code.',
            'destination_location_code.size' => 'Destination location code must be a 3-letter IATA code.',
        ];
    }
}
