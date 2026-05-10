<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function index(InventoryService $inventory)
    {
        $q = trim((string) request('q'));

        $products = Product::query()
            ->where('is_active', true)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('barcode', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(16)
            ->withQueryString();

        $products->setCollection(
            $this->attachAvailableQty($products->getCollection(), $inventory)
        );

        return view('store.search', compact('products', 'q'));
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
