<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHotelBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'offer_id' => ['required', 'string'],
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.name' => ['required', 'string', 'max:255'],
            'guests.*.contact' => ['required', 'array'],
            'guests.*.contact.phone' => ['required', 'string'],
            'guests.*.contact.email' => ['required', 'email'],
            'payment' => ['required', 'array'],
            'payment.method' => ['required', 'string', 'in:CREDIT_CARD'],
            'payment.card' => ['required', 'array'],
            'payment.card.vendor_code' => ['required', 'string'],
            'payment.card.card_number' => ['required', 'string'],
            'payment.card.expiry_date' => ['required', 'string', 'regex:/^\d{2}\/\d{2}$/'],
            'payment.card.card_holder_name' => ['required', 'string'],
            'payment.card.card_type' => ['required', 'string', 'in:CREDIT,DEBIT'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'offer_id.required' => 'Offer ID is required',
            'guests.required' => 'At least one guest is required',
            'guests.*.name.required' => 'Guest name is required',
            'guests.*.contact.phone.required' => 'Guest phone number is required',
            'guests.*.contact.email.required' => 'Guest email is required',
            'guests.*.contact.email.email' => 'Guest email must be a valid email address',
            'payment.method.required' => 'Payment method is required',
            'payment.method.in' => 'Payment method must be CREDIT_CARD',
            'payment.card.expiry_date.regex' => 'Expiry date must be in MM/YY format',
        ];
    }
}

