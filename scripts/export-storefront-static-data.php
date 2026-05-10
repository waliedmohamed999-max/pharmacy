<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$relativeAsset = static function (?string $path, string $fallback = '/images/placeholder.png'): string {
    if (!$path) {
        return $fallback;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'images/') || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
        return '/' . $path;
    }

    return '/storage/' . $path;
};

$routePath = static function (string $type, ?string $slug): string {
    return $slug ? "/{$type}/{$slug}" : '#';
};

$fixText = static function (?string $value): string {
    $value = (string) ($value ?? '');

    if (
        $value === ''
        || (
            !str_contains($value, "\xC3\x98")
            && !str_contains($value, "\xC3\x99")
            && !str_contains($value, "\xC3\x83")
        )
    ) {
        return $value;
    }

    $cp1252 = [
        0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84,
        0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88,
        0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
        0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93,
        0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
        0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
        0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
    ];

    $bytes = '';
    foreach (mb_str_split($value) as $char) {
        $code = mb_ord($char, 'UTF-8');
        if ($code <= 0xFF) {
            $bytes .= chr($code);
            continue;
        }

        if (isset($cp1252[$code])) {
            $bytes .= chr($cp1252[$code]);
            continue;
        }

        return $value;
    }

    return mb_check_encoding($bytes, 'UTF-8') ? $bytes : $value;
};

$store = [
    'name' => $fixText(StoreSetting::getValue('store_name', 'صيدلية د. محمد رمضان')),
    'tagline' => $fixText(StoreSetting::getValue('store_tagline', 'رعاية موثوقة وتسوق أسرع')),
    'phone' => $fixText(StoreSetting::getValue('support_phone', '0509095816')),
];

$categories = Category::query()
    ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
    ->where('is_active', true)
    ->orderBy('sort_order')
    ->orderBy('id')
    ->limit(18)
    ->get()
    ->map(fn (Category $category) => [
        'id' => $category->id,
        'name' => $fixText($category->display_name),
        'slug' => $category->slug,
        'count' => $category->products_count,
        'image' => $relativeAsset($category->image, '/images/categories/pharmacy-products.svg'),
        'url' => $routePath('category', $category->slug),
    ])
    ->values();

$productsQuery = Product::query()
    ->with('category')
    ->where('is_active', true)
    ->orderByDesc('featured')
    ->orderByDesc('id');

$products = (clone $productsQuery)
    ->limit(32)
    ->get()
    ->map(fn (Product $product) => [
        'id' => $product->id,
        'name' => $fixText($product->name),
        'slug' => $product->slug,
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'category' => $fixText($product->category?->display_name),
        'description' => $fixText(Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 120)),
        'price' => (float) $product->price,
        'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
        'discount_percent' => $product->discount_percent,
        'quantity' => (float) ($product->quantity ?? 0),
        'image' => $relativeAsset($product->primary_image, '/images/products/medicine-pack-green.svg'),
        'url' => $routePath('product', $product->slug),
    ])
    ->values();

$deals = Product::query()
    ->with('category')
    ->where('is_active', true)
    ->whereNotNull('compare_price')
    ->whereColumn('compare_price', '>', 'price')
    ->orderByDesc('compare_price')
    ->limit(12)
    ->get()
    ->map(fn (Product $product) => [
        'id' => $product->id,
        'name' => $fixText($product->name),
        'slug' => $product->slug,
        'category' => $fixText($product->category?->display_name),
        'price' => (float) $product->price,
        'compare_price' => (float) $product->compare_price,
        'discount_percent' => $product->discount_percent,
        'quantity' => (float) ($product->quantity ?? 0),
        'image' => $relativeAsset($product->primary_image, '/images/products/medicine-pack-green.svg'),
        'url' => $routePath('product', $product->slug),
    ])
    ->values();

$banner = Banner::query()
    ->activeForToday()
    ->orderBy('sort_order')
    ->orderByDesc('id')
    ->first();

$hero = [
    'title' => $fixText($banner?->title ?: 'عروض الصيدلية'),
    'subtitle' => $fixText($banner?->subtitle ?: 'خصومات على منتجات العناية والصحة وأدوية موثوقة ومنتجات طبية.'),
    'image' => $relativeAsset($banner?->image ?: $banner?->image_path, '/images/categories/medicine-prescription.svg'),
    'url' => $banner?->link_url ?: '#',
];

$brands = collect(json_decode(StoreSetting::getValue('brand_logos', '[]') ?: '[]', true))
    ->filter(fn ($brand) => is_array($brand) && !empty($brand['name']))
    ->map(fn ($brand) => [
        'name' => $fixText($brand['name']),
        'logo' => $relativeAsset($brand['logo'] ?? null, ''),
        'url' => $brand['url'] ?? '#',
    ])
    ->values();

if ($brands->isEmpty()) {
    $brands = collect(['Bioderma', 'La Roche-Posay', 'Vichy', 'Centrum', 'Mustela', 'Accu-Chek', 'Sebamed', 'Now']);
}

$data = [
    'generated_at' => now()->toIso8601String(),
    'store' => $store,
    'hero' => $hero,
    'categories' => $categories,
    'products' => $products,
    'deals' => $deals,
    'brands' => $brands,
    'mobile_app' => [
        'screenshot' => '/images/app-home-preview.svg',
    ],
];

$target = base_path('resources/data/storefront-static.json');
File::ensureDirectoryExists(dirname($target));
File::put($target, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo 'Exported storefront snapshot: ' . $target . PHP_EOL;
echo 'Products: ' . $products->count() . ', Categories: ' . $categories->count() . ', Deals: ' . $deals->count() . PHP_EOL;
