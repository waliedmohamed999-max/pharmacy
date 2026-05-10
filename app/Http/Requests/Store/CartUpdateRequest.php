<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class CartUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qty' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.required' => 'الكمية مطلوبة.',
            'qty.integer' => 'الكمية يجب أن تكون رقمًا صحيحًا.',
            'qty.min' => 'الكمية لا يمكن أن تكون أقل من صفر.',
        ];
    }
}

