<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeSectionController extends Controller
{
    public function index()
    {
        $this->ensureStorefrontSections();

        $sections = HomeSection::with('items')->orderBy('sort_order')->get();

        return view('admin.home-sections.index', compact('sections'));
    }

    public function edit(HomeSection $homeSection)
    {
        $this->ensureStorefrontSections();

        $homeSection->refresh();
        $homeSection->load('items');
        $products = Product::where('is_active', true)->latest()->limit(500)->get();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $brandLogos = $this->brandLogos();

        return view('admin.home-sections.edit', compact('homeSection', 'products', 'categories', 'brandLogos'));
    }

    public function update(Request $request, HomeSection $homeSection)
    {
        $data = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'type' => 'required|in:auto,manual,static',
            'is_active' => 'nullable|boolean',
            'data_source' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:40',
            'tags' => 'nullable|string|max:1000',
        ]);

        $filters = $homeSection->filters_json ?? [];
        $filters['limit'] = (int) ($data['limit'] ?? ($filters['limit'] ?? 12));

        if (!empty($data['tags'])) {
            $filters['tags'] = collect(explode(',', $data['tags']))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values()
                ->all();
        }

        $homeSection->update([
            'title_ar' => $data['title_ar'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'type' => $data['type'],
            'is_active' => $request->boolean('is_active'),
            'data_source' => $data['data_source'] ?? null,
            'filters_json' => $filters,
        ]);

        return redirect()->route('admin.home-sections.index')->with('success', 'تم تحديث إعدادات القسم');
    }

    public function updateOrder(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];

        foreach ($ids as $index => $id) {
            HomeSection::whereKey($id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'تم تحديث ترتيب أقسام الواجهة');
    }

    public function updateItems(Request $request, HomeSection $homeSection)
    {
        $validated = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);

        $homeSection->items()->delete();

        $order = 0;
        foreach (($validated['product_ids'] ?? []) as $id) {
            $homeSection->items()->create([
                'item_type' => 'product',
                'item_id' => $id,
                'sort_order' => ++$order,
            ]);
        }

        foreach (($validated['category_ids'] ?? []) as $id) {
            $homeSection->items()->create([
                'item_type' => 'category',
                'item_id' => $id,
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('success', 'تم حفظ العناصر اليدوية للقسم');
    }

    public function updateBrands(Request $request, HomeSection $homeSection)
    {
        abort_unless($homeSection->key === 'brands', 404);

        $data = $request->validate([
            'existing' => ['nullable', 'array'],
            'existing.*.name' => ['nullable', 'string', 'max:120'],
            'existing.*.url' => ['nullable', 'string', 'max:500'],
            'existing.*.image' => ['nullable', 'string', 'max:500'],
            'existing.*.remove' => ['nullable', 'boolean'],
            'brand_names' => ['nullable', 'array'],
            'brand_names.*' => ['nullable', 'string', 'max:120'],
            'brand_urls' => ['nullable', 'array'],
            'brand_urls.*' => ['nullable', 'string', 'max:500'],
            'brand_logos' => ['nullable', 'array'],
            'brand_logos.*' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml', 'max:4096'],
        ]);

        $brands = [];
        foreach (($data['existing'] ?? []) as $row) {
            $image = trim((string) ($row['image'] ?? ''));
            if (!empty($row['remove'])) {
                if ($image && !str_starts_with($image, 'images/') && Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
                continue;
            }

            if (!empty($row['name']) || $image) {
                $brands[] = [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'url' => trim((string) ($row['url'] ?? '')),
                    'image' => $image,
                ];
            }
        }

        foreach (($request->file('brand_logos', []) ?? []) as $index => $file) {
            if (!$file) {
                continue;
            }

            $name = trim((string) ($data['brand_names'][$index] ?? ''));
            $brands[] = [
                'name' => $name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'url' => trim((string) ($data['brand_urls'][$index] ?? '')),
                'image' => $file->store('brands', 'public'),
            ];
        }

        StoreSetting::setValue('home_brand_logos_json', json_encode(array_values($brands), JSON_UNESCAPED_UNICODE));

        return back()->with('success', 'تم تحديث لوجوهات الماركات بنجاح');
    }

    private function brandLogos(): array
    {
        $decoded = json_decode((string) StoreSetting::getValue('home_brand_logos_json', '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($row) => is_array($row) && (!empty($row['name']) || !empty($row['image'])))
            ->map(fn ($row) => [
                'name' => (string) ($row['name'] ?? ''),
                'url' => (string) ($row['url'] ?? ''),
                'image' => (string) ($row['image'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function ensureStorefrontSections(): void
    {
        $defaults = [
            ['key' => 'slider_banners', 'title_ar' => 'بنرات رئيسية', 'title_en' => 'Hero Banners', 'type' => 'static', 'data_source' => 'banners', 'sort_order' => 1, 'filters_json' => ['limit' => 8]],
            ['key' => 'categories_circles', 'title_ar' => 'أقسام الصيدلية', 'title_en' => 'Pharmacy Categories', 'type' => 'auto', 'data_source' => 'categories', 'sort_order' => 2, 'filters_json' => ['limit' => 12]],
            ['key' => 'flash_deals', 'title_ar' => 'عروض اليوم', 'title_en' => 'Today Deals', 'type' => 'auto', 'data_source' => 'discounted', 'sort_order' => 3, 'filters_json' => ['limit' => 12]],
            ['key' => 'all_products_cta', 'title_ar' => 'كل منتجات الصيدلية', 'title_en' => 'All Products', 'type' => 'static', 'data_source' => 'custom', 'sort_order' => 4, 'filters_json' => ['limit' => 1]],
            ['key' => 'featured_products', 'title_ar' => 'منتجات مميزة', 'title_en' => 'Featured Products', 'type' => 'manual', 'data_source' => 'products', 'sort_order' => 5, 'filters_json' => ['limit' => 12]],
            ['key' => 'brands', 'title_ar' => 'أشهر الماركات الطبية', 'title_en' => 'Trusted Brands', 'type' => 'static', 'data_source' => 'custom', 'sort_order' => 6, 'filters_json' => ['limit' => 12]],
            ['key' => 'collections', 'title_ar' => 'تسوق حسب الاحتياج', 'title_en' => 'Shop By Concern', 'type' => 'auto', 'data_source' => 'tags', 'sort_order' => 7, 'filters_json' => ['limit' => 8, 'tags' => ['نزلات البرد', 'السكري', 'القلب والضغط', 'العناية بالبشرة', 'الأم والطفل', 'المناعة']]],
            ['key' => 'best_sellers', 'title_ar' => 'الأكثر مبيعاً', 'title_en' => 'Best Sellers', 'type' => 'auto', 'data_source' => 'best_sellers', 'sort_order' => 8, 'filters_json' => ['limit' => 12]],
            ['key' => 'new_arrivals', 'title_ar' => 'وصل حديثاً', 'title_en' => 'New Arrivals', 'type' => 'auto', 'data_source' => 'new_arrivals', 'sort_order' => 9, 'filters_json' => ['limit' => 12]],
            ['key' => 'testimonials', 'title_ar' => 'آراء العملاء', 'title_en' => 'Testimonials', 'type' => 'static', 'data_source' => 'custom', 'sort_order' => 10, 'filters_json' => ['limit' => 3]],
            ['key' => 'app_banner', 'title_ar' => 'تطبيق الصيدلية', 'title_en' => 'Mobile App', 'type' => 'static', 'data_source' => 'custom', 'sort_order' => 11, 'filters_json' => ['limit' => 1]],
            ['key' => 'newsletter', 'title_ar' => 'النشرة البريدية', 'title_en' => 'Newsletter', 'type' => 'static', 'data_source' => 'custom', 'sort_order' => 12, 'filters_json' => ['limit' => 1]],
        ];

        foreach ($defaults as $default) {
            $section = HomeSection::firstOrNew(['key' => $default['key']]);
            $isNew = ! $section->exists;

            $section->title_ar = $default['title_ar'];
            $section->title_en = $default['title_en'];
            $section->type = $section->type ?: $default['type'];
            $section->data_source = $section->data_source ?: $default['data_source'];
            $section->filters_json = array_replace($default['filters_json'], $section->filters_json ?? []);

            if ($isNew || ! $section->sort_order) {
                $section->sort_order = $default['sort_order'];
            }

            if ($isNew) {
                $section->is_active = true;
            }

            $section->save();
        }
    }
}
