<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\InventoryService;

class ProductController extends Controller
{
    public function show(string $slug, InventoryService $inventory)
    {
        $product = Product::with('images', 'category')->where('slug', $slug)->firstOrFail();
        $related = Product::where('category_id', $product->category_id)->where('id', '!=', $product->id)->where('is_active', true)->take(8)->get();
        $warehouseId = $inventory->defaultStorefrontWarehouseId();

        $allProductIds = $related->pluck('id')->push($product->id)->values();
        $stockByProduct = collect();
        if ($warehouseId && $allProductIds->isNotEmpty()) {
            $stockByProduct = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('product_id', $allProductIds)
                ->pluck('qty', 'product_id');
        }

        $resolveAvailableQty = function (int $productId, int $fallbackQty) use ($stockByProduct, $warehouseId): int {
            if (!$warehouseId) {
                return max(0, $fallbackQty);
            }

            if ($stockByProduct->has($productId)) {
                return (int) max(0, (float) $stockByProduct[$productId]);
            }

            return max(0, $fallbackQty);
        };

        $product->setAttribute('available_qty', $resolveAvailableQty((int) $product->id, (int) $product->quantity));
        $related->each(function (Product $item) use ($resolveAvailableQty): void {
            $item->setAttribute('available_qty', $resolveAvailableQty((int) $item->id, (int) $item->quantity));
        });

        return view('store.product', compact('product', 'related'));
    }
}
