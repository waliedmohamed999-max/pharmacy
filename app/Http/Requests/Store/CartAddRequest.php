<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CartAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'المنتج مطلوب.',
            'product_id.exists' => 'المنتج غير موجود.',
            'qty.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'qty.min' => 'الكمية لا تقل عن 1.',
        ];
    }
}

