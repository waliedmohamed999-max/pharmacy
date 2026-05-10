<?php

namespace App\Http\Requests\Admin;

class UpdatePageRequest extends StorePageRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $page = $this->route('page');
        $rules['slug'] = 'nullable|string|max:255|unique:pages,slug,' . ($page?->id ?? 'NULL');

        return $rules;
    }
}
