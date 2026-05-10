<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'footer_enabled' => 'nullable|boolean',
            'footer_show_pages' => 'nullable|boolean',
            'footer_newsletter_enabled' => 'nullable|boolean',
            'footer_brand_title' => 'nullable|string|max:100',
            'footer_links_title' => 'nullable|string|max:100',
            'footer_newsletter_title' => 'nullable|string|max:100',
            'footer_contact_title' => 'nullable|string|max:100',
            'footer_about' => 'nullable|string|max:1000',
            'footer_newsletter_text' => 'nullable|string|max:255',
            'footer_contact_address' => 'nullable|string|max:255',
            'footer_contact_phone' => 'nullable|string|max:100',
            'footer_contact_email' => 'nullable|email|max:255',
            'footer_copyright' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*.label' => 'nullable|string|max:100',
            'links.*.url' => 'nullable|url|max:2048',
        ];
    }
}
