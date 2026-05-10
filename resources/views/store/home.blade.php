@extends('store.layouts.app')

@section('full_bleed', true)

@section('content')
@php
    $cartCount = app(\App\Services\CartService::class)->summary()['count'] ?? 0;

    $imageUrl = function (?string $path) {
        if (!$path) {
            return asset('images/placeholder.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };

    $banners = collect($sectionData['slider_banners'] ?? [])->map(function ($banner) {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'image' => $banner->resolved_image,
            'url' => $banner->resolved_url,
        ];
    })->values();

    $categories = collect($sectionData['categories_circles'] ?? [])->map(function ($category) use ($imageUrl) {
        return [
            'id' => $category->id,
            'name' => $category->display_name,
            'image' => $imageUrl($category->image),
            'count' => $category->products_count ?? 0,
            'url' => $category->slug ? route('store.category.show', $category->slug) : route('store.home'),
        ];
    })->values();

    $products = collect($storefrontProducts ?? [])->map(function ($product, $index) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
            'discount' => $product->discount_percent,
            'available_qty' => (int) max(0, (float) ($product->available_qty ?? $product->quantity ?? 0)),
            'featured' => (bool) $product->featured,
            'image' => $product->image_url,
            'url' => route('store.product.show', $product->slug),
            'rating' => number_format(4.5 + (($index % 5) / 10), 1),
            'reviews' => 18 + ($index * 7 % 140),
        ];
    })->values();

    $formatProducts = function ($items) {
        return collect($items ?? [])->filter(fn ($item) => $item instanceof \App\Models\Product)->map(function ($product, $index) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
                'discount' => $product->discount_percent,
                'available_qty' => (int) max(0, (float) ($product->available_qty ?? $product->quantity ?? 0)),
                'featured' => (bool) $product->featured,
                'image' => $product->image_url,
                'url' => route('store.product.show', $product->slug),
                'rating' => number_format(4.5 + (($index % 5) / 10), 1),
                'reviews' => 18 + ($index * 7 % 140),
            ];
        })->values();
    };

    $sectionProducts = collect($sections ?? [])->mapWithKeys(function ($section) use ($sectionData, $formatProducts) {
        return [$section->key => $formatProducts($sectionData[$section->key] ?? [])];
    });

    $collectionTags = collect($sectionData['collections'] ?? [])->map(fn ($tag) => (string) $tag)->values();

    $brandLogos = collect(json_decode((string) \App\Models\StoreSetting::getValue('home_brand_logos_json', '[]'), true) ?: [])->filter(function ($brand) {
        return is_array($brand) && (!empty($brand['name']) || !empty($brand['image']));
    })->map(function ($brand) use ($imageUrl) {
        return [
            'name' => (string) ($brand['name'] ?? ''),
            'url' => (string) ($brand['url'] ?? ''),
            'image' => $imageUrl($brand['image'] ?? null),
        ];
    })->values();

    $sectionSettings = collect($sections ?? [])->map(function ($section) {
        return [
            'key' => $section->key,
            'title' => $section->display_title ?: $section->key,
            'type' => $section->type,
            'source' => $section->data_source,
            'active' => (bool) $section->is_active,
        ];
    })->values();

    $payload = [
        'cartCount' => $cartCount,
        'banners' => $banners,
        'categories' => $categories,
        'products' => $products,
        'sections' => $sectionSettings,
        'sectionProducts' => $sectionProducts,
        'collectionTags' => $collectionTags,
        'brandLogos' => $brandLogos,
        'routes' => [
            'home' => route('store.home'),
            'products' => route('store.products.index'),
            'search' => route('store.search'),
            'cart' => route('store.cart.index'),
            'login' => route('login'),
            'locale' => route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar'),
            'addToCart' => route('store.cart.add'),
            'csrf' => csrf_token(),
        ],
    ];
@endphp

<div id="storefront-home-root" data-payload='@json($payload)'>
    <div class="min-h-screen bg-slate-50 p-6">
        <div class="mx-auto max-w-7xl animate-pulse space-y-5">
            <div class="h-16 rounded-3xl bg-white"></div>
            <div class="h-[420px] rounded-[2rem] bg-white"></div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                @for($i = 0; $i < 10; $i++)
                    <div class="h-64 rounded-3xl bg-white"></div>
                @endfor
            </div>
        </div>
    </div>
</div>
@endsection
