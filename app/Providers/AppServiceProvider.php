<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('store.layouts.app', function ($view) {
            $pages = collect();
            $footerSettings = [
                'enabled' => true,
                'show_pages' => true,
                'newsletter_enabled' => true,
                'brand_title' => 'صيدلية د. محمد رمضان',
                'links_title' => 'روابط مفيدة',
                'newsletter_title' => 'النشرة الإخبارية',
                'contact_title' => 'اتصل بنا',
                'about' => '',
                'newsletter_text' => '',
                'contact_address' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'copyright' => '© ' . now()->year . ' صيدلية د. محمد رمضان',
                'links' => [],
            ];

            if (Schema::hasTable('pages')) {
                $pages = Page::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['title', 'slug']);
            }

            if (Schema::hasTable('store_settings')) {
                $footerSettings['enabled'] = StoreSetting::getBool('footer_enabled', true);
                $footerSettings['show_pages'] = StoreSetting::getBool('footer_show_pages', true);
                $footerSettings['newsletter_enabled'] = StoreSetting::getBool('footer_newsletter_enabled', true);
                $footerSettings['brand_title'] = StoreSetting::getValue('footer_brand_title', $footerSettings['brand_title']) ?: $footerSettings['brand_title'];
                $footerSettings['links_title'] = StoreSetting::getValue('footer_links_title', $footerSettings['links_title']) ?: $footerSettings['links_title'];
                $footerSettings['newsletter_title'] = StoreSetting::getValue('footer_newsletter_title', $footerSettings['newsletter_title']) ?: $footerSettings['newsletter_title'];
                $footerSettings['contact_title'] = StoreSetting::getValue('footer_contact_title', $footerSettings['contact_title']) ?: $footerSettings['contact_title'];
                $footerSettings['about'] = StoreSetting::getValue('footer_about', '') ?: '';
                $footerSettings['newsletter_text'] = StoreSetting::getValue('footer_newsletter_text', '') ?: '';
                $footerSettings['contact_address'] = StoreSetting::getValue('footer_contact_address', '') ?: '';
                $footerSettings['contact_phone'] = StoreSetting::getValue('footer_contact_phone', '') ?: '';
                $footerSettings['contact_email'] = StoreSetting::getValue('footer_contact_email', '') ?: '';
                $footerSettings['copyright'] = StoreSetting::getValue('footer_copyright', $footerSettings['copyright']) ?: $footerSettings['copyright'];
                $footerSettings['links'] = $this->decodeFooterLinks(
                    StoreSetting::getValue('footer_links_json', '[]')
                );
            }

            $view->with('footerPages', $pages)
                ->with('footerSettings', $footerSettings);
        });
    }

    private function decodeFooterLinks(?string $json): array
    {
        $links = json_decode($json ?: '[]', true);

        if (!is_array($links)) {
            return [];
        }

        return collect($links)
            ->filter(fn ($link) => is_array($link) && !empty($link['label']) && !empty($link['url']))
            ->values()
            ->all();
    }
}
