<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Support\Collection;

class CategoryController extends Controller
{
    public function all(InventoryService $inventory)
    {
        $products = Product::query()->where('is_active', true);

        $this->applyProductFilters($products);

        $paginated = $products->paginate(24)->withQueryString();
        $paginated->setCollection(
            $this->attachAvailableQty($paginated->getCollection(), $inventory)
        );

        return view('store.category', [
            'category' => (object) [
                'display_name' => 'كل المنتجات',
            ],
            'products' => $paginated,
            'pageSubtitle' => 'كل منتجات الصيدلية في مكان واحد',
        ]);
    }

    public function show(string $slug, InventoryService $inventory)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = Product::where('category_id', $category->id)->where('is_active', true);

        $this->applyProductFilters($products);

        $paginated = $products->paginate(12)->withQueryString();
        $paginated->setCollection(
            $this->attachAvailableQty($paginated->getCollection(), $inventory)
        );

        return view('store.category', [
            'category' => $category,
            'products' => $paginated,
        ]);
    }

    private function applyProductFilters($products): void
    {
        $search = trim((string) request('q', ''));
        if ($search !== '') {
            $products->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if (request('min_price')) {
            $products->where('price', '>=', request('min_price'));
        }

        if (request('max_price')) {
            $products->where('price', '<=', request('max_price'));
        }

        if (request('in_stock')) {
            $products->where('quantity', '>', 0);
        }

        match (request('sort')) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'name_asc' => $products->orderBy('name'),
            default => $products->latest(),
        };
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
}
