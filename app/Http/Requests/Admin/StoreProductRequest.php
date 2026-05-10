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
            'description' => 'nullable|string|max:10000',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0|max:1000000',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'tags' => 'nullable|string|max:1000',
            'primary_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'primary_image_url' => 'nullable|url|starts_with:https://|max:2048',
            'gallery' => 'nullable|array|max:10',
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }
}
