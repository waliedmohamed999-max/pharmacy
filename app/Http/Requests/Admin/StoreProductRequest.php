<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'tags' => 'nullable|string|max:1000',
            'primary_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'primary_image_url' => 'nullable|url|max:2048',
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }
}
