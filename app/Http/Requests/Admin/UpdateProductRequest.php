<?php

namespace App\Http\Requests\Admin;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $product = $this->route('product');
        $rules['sku'] = 'nullable|string|max:100|unique:products,sku,' . ($product?->id ?? 'NULL');
        $rules['barcode'] = 'nullable|string|max:100|unique:products,barcode,' . ($product?->id ?? 'NULL');

        return $rules;
    }
}
