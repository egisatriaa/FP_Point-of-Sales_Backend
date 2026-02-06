<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['sometimes', 'string'], // User mentioned it, but not sure if used in logic yet.
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'        => ['required', 'integer', 'min:1'],
        ];
    }
    
    public function messages()
    {
        return [
             'items.required' => 'Transaction must contain at least one item.',
             'payment_amount.required' => 'Payment amount (paid_amount) is required.',
        ];
    }

    protected function prepareForValidation()
    {
        // map paid_amount to payment_amount if present, to support user's requested payload key
        if ($this->has('paid_amount')) {
            $this->merge(['payment_amount' => $this->paid_amount]);
        }
    }
}
