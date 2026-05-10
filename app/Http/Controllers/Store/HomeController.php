<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StoreSetting;
use App\Services\InventoryService;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(InventoryService $inventory)
    {
        $sections = HomeSection::where('is_active', true)->orderBy('sort_order')->get();
        $sectionData = [];

        foreach ($sections as $section) {
            $sectionData[$section->key] = $this->resolveSectionData($section, $inventory);
        }

        $afterNewBanners = $this->resolveAfterNewBanners();

        return view('store.home', [
            'sections' => $sections,
            'sectionData' => $sectionData,
            'bannerAutoplay' => StoreSetting::getBool('home_banner_autoplay', true),
            'afterNewBanners' => $afterNewBanners,
            'storefrontProducts' => $this->attachAvailableQty(
                Product::query()->where('is_active', true)->latest()->limit(60)->get(),
                $inventory
            ),
        ]);
    }

    private function resolveSectionData(HomeSection $section, InventoryService $inventory)
    {
        $filters = $section->filters_json ?? [];
        $limit = (int) ($filters['limit'] ?? 12);

        if ($section->key === 'slider_banners' || $section->data_source === 'banners') {
            return Banner::activeForToday()->orderBy('sort_order')->get();
        }

        if ($section->type === 'manual' && ($section->key === 'categories_circles' || $section->data_source === 'categories')) {
            $ids = $section->items()->where('item_type', 'category')->orderBy('sort_order')->pluck('item_id');

            if ($ids->isNotEmpty()) {
                $categories = Category::query()
                    ->whereIn('id', $ids)
                    ->where('is_active', true)
                    ->withCount('products')
                    ->get()
                    ->keyBy('id');

                return $ids->map(fn ($id) => $categories[$id] ?? null)->filter()->values();
            }
        }

        if ($section->key === 'categories_circles' || $section->data_source === 'categories') {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->whereNotNull('image')
                ->withCount('products')
                ->orderBy('sort_order')
                ->limit($limit)
                ->get();
        }

        if ($section->type === 'manual') {
            $ids = $section->items()->where('item_type', 'product')->orderBy('sort_order')->pluck('item_id');
            if ($ids->isEmpty()) {
                if ($section->key === 'featured_products') {
                    return $this->attachAvailableQty(
                        Product::query()->where('is_active', true)->where('featured', true)->latest()->limit($limit)->get(),
                        $inventory
                    );
                }
                return collect();
            }

            $products = Product::query()->whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');

            return $this->attachAvailableQty(
                $ids->map(fn ($id) => $products[$id] ?? null)->filter(),
                $inventory
            );
        }

        return match ($section->data_source) {
            'discounted' => $this->attachAvailableQty(
                Product::query()->where('is_active', true)->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')->latest()->limit($limit)->get(),
                $inventory
            ),
            'best_sellers' => $this->attachAvailableQty($this->bestSellers($limit), $inventory),
            'new_arrivals' => $this->attachAvailableQty(
                Product::query()->where('is_active', true)->latest()->limit($limit)->get(),
                $inventory
            ),
            'tags' => collect($filters['tags'] ?? ['برد وزكام', 'فيتامينات', 'عناية شعر', 'أطفال']),
            default => collect(),
        };
    }

    private function bestSellers(int $limit): Collection
    {
        $ids = OrderItem::query()
            ->selectRaw('product_id, SUM(qty) as qty_sum')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('qty_sum')
            ->limit($limit)
            ->pluck('product_id');

        if ($ids->isEmpty()) {
            return Product::query()->where('is_active', true)->latest()->limit($limit)->get();
        }

        $products = Product::query()->whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');
        return $ids->map(fn ($id) => $products[$id] ?? null)->filter();
    }

    private function attachAvailableQty(Collection $items, InventoryService $inventory): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $warehouseId = $inventory->defaultStorefrontWarehouseId();
        if (!$warehouseId) {
            return $items->each(fn (Product $product) => $product->setAttribute('available_qty', max(0, (int) $product->quantity)));
        }

        $stocks = ProductStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $items->pluck('id'))
            ->pluck('qty', 'product_id');

        return $items->each(function (Product $product) use ($stocks): void {
            if ($stocks->has($product->id)) {
                $product->setAttribute('available_qty', (int) max(0, (float) $stocks[$product->id]));
                return;
            }

            $product->setAttribute('available_qty', max(0, (int) $product->quantity));
        });
    }

    private function resolveAfterNewBanners(): Collection
    {
        $raw = StoreSetting::getValue('home_after_new_banner_ids');
        $decoded = json_decode((string) $raw, true);
        $ids = [];
        if (is_array($decoded)) {
            $ids = collect($decoded)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        } else {
            $legacy = (int) (StoreSetting::getValue('home_after_new_banner_id', '0') ?: 0);
            if ($legacy > 0) {
                $ids = [$legacy];
            }
        }

        if (empty($ids)) {
            return collect();
        }

        $banners = Banner::query()
            ->whereIn('id', $ids)
            ->activeForToday()
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $banners->get($id))->filter()->values();
    }
}
