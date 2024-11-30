<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'card_number' => ['required', 'string', 'size:16'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
            'customer_email' => ['required', 'email'],
            'metadata' => ['sometimes', 'array']
        ];
    }

    public function messages()
    {
        return [
            'card_number.required' => 'Card number is required',
            'card_number.size' => 'Card number must be 16 digits',
            'amount.required' => 'Transaction amount is required',
            'amount.gt' => 'Amount must be greater than 0',
            'currency.required' => 'Currency code is required',
            'currency.size' => 'Currency must be a 3-letter ISO code',
            'customer_email.required' => 'Customer email is required',
            'customer_email.email' => 'Please provide a valid email address',
            'metadata.array' => 'Metadata must be an object'
        ];
    }
}
