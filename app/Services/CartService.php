<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Str;

class CartService
{
    private string $key = 'cart';

    private string $couponKey = 'cart_coupon';

    public function getCart(): array
    {
        return session()->get($this->key, []);
    }

    public function add(Product $product, int $qty = 1): array
    {
        $qty = max(1, $qty);
        $available = $this->availableQty((int) $product->id);
        if ($available <= 0) {
            return ['ok' => false, 'message' => 'المنتج غير متاح حاليًا بالمخزون.'];
        }

        $cart = $this->getCart();
        $rowId = sha1((string) $product->id);
        $currentQty = (int) ($cart[$rowId]['qty'] ?? 0);
        $newQty = min($available, $currentQty + $qty);

        if ($newQty <= $currentQty) {
            return ['ok' => false, 'message' => 'لا يمكن إضافة كمية أكبر من المتاح بالمخزون.'];
        }

        $cart[$rowId] = [
            'rowId' => $rowId,
            'product_id' => (int) $product->id,
            'qty' => $newQty,
        ];

        session()->put($this->key, $cart);

        return ['ok' => true];
    }

    public function update(string $rowId, int $qty): array
    {
        $cart = $this->getCart();
        if (!isset($cart[$rowId])) {
            return ['ok' => false, 'message' => 'العنصر غير موجود في السلة.'];
        }

        $productId = (int) $cart[$rowId]['product_id'];
        $available = $this->availableQty($productId);

        if ($qty <= 0) {
            unset($cart[$rowId]);
            session()->put($this->key, $cart);
            return ['ok' => true];
        }

        if ($available <= 0) {
            unset($cart[$rowId]);
            session()->put($this->key, $cart);
            return ['ok' => false, 'message' => 'المنتج غير متاح بالمخزون.'];
        }

        $finalQty = min($available, $qty);
        $cart[$rowId]['qty'] = $finalQty;
        session()->put($this->key, $cart);

        if ($finalQty < $qty) {
            return ['ok' => false, 'message' => 'تم تعديل الكمية للحد المتاح في المخزون.'];
        }

        return ['ok' => true];
    }

    public function remove(string $rowId): void
    {
        $cart = $this->getCart();
        unset($cart[$rowId]);
        session()->put($this->key, $cart);
    }

    public function clear(): void
    {
        session()->forget($this->key);
        session()->forget($this->couponKey);
    }

    public function applyCoupon(string $code): array
    {
        $code = Str::upper(trim($code));
        if ($code === '') {
            return ['ok' => false, 'message' => 'كود الخصم غير صالح.'];
        }

        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [$code])->first();
        if (!$coupon) {
            return ['ok' => false, 'message' => 'كود الخصم غير موجود.'];
        }

        $validation = $this->validateCoupon($coupon, null);
        if (!$validation['ok']) {
            return $validation;
        }

        session()->put($this->couponKey, $coupon->code);

        return ['ok' => true, 'message' => 'تم تطبيق كود الخصم بنجاح.'];
    }

    public function removeCoupon(): void
    {
        session()->forget($this->couponKey);
    }

    public function summary(): array
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            $this->removeCoupon();
            return [
                'items' => [],
                'subtotal' => 0.0,
                'subtotal_compare' => 0.0,
                'line_saving_total' => 0.0,
                'discount' => 0.0,
                'total_saving' => 0.0,
                'total' => 0.0,
                'count' => 0,
                'distinct_count' => 0,
                'low_stock_count' => 0,
                'coupon' => null,
            ];
        }

        $rows = collect($cart);
        $products = Product::query()
            ->with('category:id,name')
            ->whereIn('id', $rows->pluck('product_id')->filter()->values())
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($rows as $rowId => $row) {
            $product = $products->get((int) ($row['product_id'] ?? 0));
            if (!$product || !$product->is_active) {
                continue;
            }

            $available = $this->availableQty((int) $product->id);
            if ($available <= 0) {
                continue;
            }

            $qty = min((int) ($row['qty'] ?? 1), $available);
            $price = (float) $product->price;
            $comparePrice = (float) ($product->compare_price ?? 0);
            $lineTotal = $price * $qty;
            $lineCompareTotal = $comparePrice > $price ? ($comparePrice * $qty) : $lineTotal;
            $lineSaving = max(0, $lineCompareTotal - $lineTotal);

            $items[] = [
                'rowId' => (string) $rowId,
                'product_id' => (int) $product->id,
                'product_slug' => (string) $product->slug,
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? ''),
                'category_name' => (string) optional($product->category)->name,
                'image_url' => (string) $product->image_url,
                'price' => $price,
                'compare_price' => $comparePrice,
                'qty' => $qty,
                'available_qty' => $available,
                'line_total' => $lineTotal,
                'line_compare_total' => $lineCompareTotal,
                'line_saving' => $lineSaving,
                'discount_percent' => (int) $product->discount_percent,
                'is_low_stock' => $available <= 5,
                'can_increase' => $qty < $available,
                'max_additional' => max(0, $available - $qty),
            ];
        }

        $sanitized = [];
        foreach ($items as $item) {
            $sanitized[$item['rowId']] = [
                'rowId' => $item['rowId'],
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
            ];
        }
        session()->put($this->key, $sanitized);

        $subtotal = (float) collect($items)->sum('line_total');
        $subtotalCompare = (float) collect($items)->sum('line_compare_total');
        $lineSavingTotal = max(0, $subtotalCompare - $subtotal);

        [$couponData, $couponDiscount] = $this->resolveCouponForSubtotal($subtotal);
        $couponDiscount = min($couponDiscount, $subtotal);
        $total = max(0, $subtotal - $couponDiscount);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'subtotal_compare' => $subtotalCompare,
            'line_saving_total' => $lineSavingTotal,
            'discount' => $couponDiscount,
            'total_saving' => ($lineSavingTotal + $couponDiscount),
            'total' => $total,
            'count' => (int) collect($items)->sum('qty'),
            'distinct_count' => count($items),
            'low_stock_count' => (int) collect($items)->where('is_low_stock', true)->count(),
            'coupon' => $couponData,
        ];
    }

    public function consumeCouponIfAny(): void
    {
        $couponCode = session()->get($this->couponKey);
        if (!$couponCode) {
            return;
        }

        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [Str::upper((string) $couponCode)])->first();
        if (!$coupon) {
            $this->removeCoupon();
            return;
        }

        $coupon->increment('used_count');
        $this->removeCoupon();
    }

    private function resolveCouponForSubtotal(float $subtotal): array
    {
        $couponCode = session()->get($this->couponKey);
        if (!$couponCode || $subtotal <= 0) {
            return [null, 0.0];
        }

        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [Str::upper((string) $couponCode)])->first();
        if (!$coupon) {
            $this->removeCoupon();
            return [null, 0.0];
        }

        $validation = $this->validateCoupon($coupon, $subtotal);
        if (!$validation['ok']) {
            $this->removeCoupon();
            return [null, 0.0];
        }

        $discount = $coupon->type === 'percent'
            ? ($subtotal * ((float) $coupon->value / 100))
            : (float) $coupon->value;

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = max(0, round($discount, 2));

        return [[
            'id' => (int) $coupon->id,
            'code' => (string) $coupon->code,
            'type' => (string) $coupon->type,
            'value' => (float) $coupon->value,
        ], $discount];
    }

    private function validateCoupon(Coupon $coupon, ?float $subtotal): array
    {
        if (!$coupon->is_active) {
            return ['ok' => false, 'message' => 'كود الخصم غير مفعل.'];
        }

        $now = now();
        if ($coupon->starts_at && $coupon->starts_at->gt($now)) {
            return ['ok' => false, 'message' => 'كود الخصم لم يبدأ بعد.'];
        }
        if ($coupon->ends_at && $coupon->ends_at->lt($now)) {
            return ['ok' => false, 'message' => 'انتهت صلاحية كود الخصم.'];
        }
        if ($coupon->usage_limit !== null && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            return ['ok' => false, 'message' => 'تم استهلاك كود الخصم بالكامل.'];
        }

        if ($subtotal !== null && $subtotal < (float) $coupon->min_subtotal) {
            return ['ok' => false, 'message' => 'الحد الأدنى لتفعيل الكود هو ' . number_format((float) $coupon->min_subtotal, 2) . ' ج.م'];
        }

        return ['ok' => true];
    }

    private function availableQty(int $productId): int
    {
        $warehouseId = app(InventoryService::class)->defaultStorefrontWarehouseId();
        if ($warehouseId) {
            $stockRow = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->first();

            if ($stockRow) {
                return (int) max(0, (float) $stockRow->qty);
            }

            // Legacy fallback: if product has old total quantity but no warehouse row yet,
            // bootstrap a default stock row so cart + checkout stay consistent.
            $product = Product::query()->whereKey($productId)->first();
            $fallbackQty = (int) max(0, (float) ($product?->quantity ?? 0));

            if ($fallbackQty > 0) {
                $created = ProductStock::query()->firstOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'product_id' => $productId,
                    ],
                    [
                        'qty' => $fallbackQty,
                        'avg_cost' => (float) ($product?->avg_cost ?? 0),
                    ]
                );

                return (int) max(0, (float) $created->qty);
            }
        }

        return (int) max(0, (float) (Product::query()->whereKey($productId)->value('quantity') ?? 0));
    }
}

