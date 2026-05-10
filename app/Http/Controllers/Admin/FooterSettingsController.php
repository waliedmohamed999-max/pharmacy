<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFooterSettingsRequest;
use App\Models\StoreSetting;

class FooterSettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'footer_enabled' => StoreSetting::getBool('footer_enabled', true),
            'footer_show_pages' => StoreSetting::getBool('footer_show_pages', true),
            'footer_newsletter_enabled' => StoreSetting::getBool('footer_newsletter_enabled', true),
            'footer_brand_title' => StoreSetting::getValue('footer_brand_title', 'صيدلية د. محمد رمضان'),
            'footer_links_title' => StoreSetting::getValue('footer_links_title', 'روابط مفيدة'),
            'footer_newsletter_title' => StoreSetting::getValue('footer_newsletter_title', 'النشرة الإخبارية'),
            'footer_contact_title' => StoreSetting::getValue('footer_contact_title', 'اتصل بنا'),
            'footer_about' => StoreSetting::getValue('footer_about', ''),
            'footer_newsletter_text' => StoreSetting::getValue('footer_newsletter_text', 'اشترك لمتابعة أحدث العروض والمنتجات.'),
            'footer_contact_address' => StoreSetting::getValue('footer_contact_address', ''),
            'footer_contact_phone' => StoreSetting::getValue('footer_contact_phone', ''),
            'footer_contact_email' => StoreSetting::getValue('footer_contact_email', ''),
            'footer_copyright' => StoreSetting::getValue('footer_copyright', '© ' . now()->year . ' صيدلية د. محمد رمضان'),
            'links' => $this->decodeLinks(StoreSetting::getValue('footer_links_json', '[]')),
        ];

        return view('admin.footer.edit', compact('settings'));
    }

    public function update(UpdateFooterSettingsRequest $request)
    {
        $data = $request->validated();
        $links = collect($data['links'] ?? [])
            ->filter(function (array $link) {
                return !empty($link['label']) && $this->isSafeFooterUrl((string) ($link['url'] ?? ''));
            })
            ->map(fn (array $link) => [
                'label' => trim((string) $link['label']),
                'url' => trim((string) $link['url']),
            ])
            ->values()
            ->all();

        StoreSetting::setValue('footer_enabled', $request->boolean('footer_enabled') ? '1' : '0');
        StoreSetting::setValue('footer_show_pages', $request->boolean('footer_show_pages') ? '1' : '0');
        StoreSetting::setValue('footer_newsletter_enabled', $request->boolean('footer_newsletter_enabled') ? '1' : '0');
        StoreSetting::setValue('footer_brand_title', (string) ($data['footer_brand_title'] ?? ''));
        StoreSetting::setValue('footer_links_title', (string) ($data['footer_links_title'] ?? ''));
        StoreSetting::setValue('footer_newsletter_title', (string) ($data['footer_newsletter_title'] ?? ''));
        StoreSetting::setValue('footer_contact_title', (string) ($data['footer_contact_title'] ?? ''));
        StoreSetting::setValue('footer_about', (string) ($data['footer_about'] ?? ''));
        StoreSetting::setValue('footer_newsletter_text', (string) ($data['footer_newsletter_text'] ?? ''));
        StoreSetting::setValue('footer_contact_address', (string) ($data['footer_contact_address'] ?? ''));
        StoreSetting::setValue('footer_contact_phone', (string) ($data['footer_contact_phone'] ?? ''));
        StoreSetting::setValue('footer_contact_email', (string) ($data['footer_contact_email'] ?? ''));
        StoreSetting::setValue('footer_copyright', (string) ($data['footer_copyright'] ?? ''));
        StoreSetting::setValue('footer_links_json', json_encode($links, JSON_UNESCAPED_UNICODE));

        return redirect()->route('admin.footer.edit')->with('success', 'تم تحديث إعدادات الفوتر بنجاح');
    }

    private function decodeLinks(?string $json): array
    {
        $decoded = json_decode($json ?: '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isSafeFooterUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        return str_starts_with($url, '/')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'tel:');
    }
}
