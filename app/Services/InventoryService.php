<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function ensureDefaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()->where('code', 'MAIN')->first();

        if ($warehouse) {
            if (! $warehouse->is_active) {
                $warehouse->update(['is_active' => true]);
            }

            return $warehouse;
        }

        return Warehouse::create([
            'name' => 'المخزن الرئيسي',
            'code' => 'MAIN',
            'location' => 'صيدلية د. محمد رمضان',
            'is_active' => true,
        ]);
    }

    public function defaultStorefrontWarehouseId(): ?int
    {
        $main = Warehouse::query()->where('code', 'MAIN')->where('is_active', true)->value('id');
        if ($main) {
            return (int) $main;
        }

        $fallback = Warehouse::query()->where('is_active', true)->orderBy('id')->value('id');
        return $fallback ? (int) $fallback : null;
    }

    public function syncProductsToDefaultWarehouse(): array
    {
        $warehouse = $this->ensureDefaultWarehouse();
        $created = 0;
        $updated = 0;

        Product::query()
            ->where('is_active', true)
            ->select(['id', 'quantity', 'avg_cost'])
            ->orderBy('id')
            ->chunkById(300, function ($products) use ($warehouse, &$created, &$updated): void {
                $productIds = $products->pluck('id');
                $otherWarehouseQty = ProductStock::query()
                    ->whereIn('product_id', $productIds)
                    ->where('warehouse_id', '!=', $warehouse->id)
                    ->selectRaw('product_id, COALESCE(SUM(qty),0) as qty')
                    ->groupBy('product_id')
                    ->pluck('qty', 'product_id');

                foreach ($products as $product) {
                    $otherQty = (float) ($otherWarehouseQty[$product->id] ?? 0);
                    $targetQty = max(0, (float) $product->quantity - $otherQty);

                    $stock = ProductStock::query()->firstOrNew([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                    ]);

                    $exists = $stock->exists;
                    $stock->qty = $targetQty;
                    $stock->avg_cost = (float) ($product->avg_cost ?? 0);
                    $stock->save();

                    $exists ? $updated++ : $created++;
                    $this->syncProductTotals((int) $product->id);
                }
            });

        return [
            'warehouse_id' => (int) $warehouse->id,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    public function receive(
        int $warehouseId,
        int $productId,
        float $qty,
        float $unitCost,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): void {
        $this->applyMovement('in', $warehouseId, null, $productId, $qty, $unitCost, $date, $referenceType, $referenceId, $notes, $userId);
    }

    public function issue(
        int $warehouseId,
        int $productId,
        float $qty,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null
    ): float {
        return $this->applyMovement('out', $warehouseId, null, $productId, $qty, null, $date, $referenceType, $referenceId, $notes, $userId);
    }

    public function transfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $qty,
        string $date,
        ?string $notes = null,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $qty, $date, $notes, $userId) {
            $unitCost = $this->applyMovement('transfer_out', $fromWarehouseId, $toWarehouseId, $productId, $qty, null, $date, 'transfer', null, $notes, $userId);
            $this->applyMovement('transfer_in', $toWarehouseId, $fromWarehouseId, $productId, $qty, $unitCost, $date, 'transfer', null, $notes, $userId);
        });
    }

    public function adjust(
        int $warehouseId,
        int $productId,
        float $qtyDiff,
        float $unitCost,
        string $date,
        ?string $notes = null,
        ?int $userId = null
    ): void {
        if ($qtyDiff == 0.0) {
            return;
        }

        $type = $qtyDiff > 0 ? 'adjust_in' : 'adjust_out';
        $this->applyMovement($type, $warehouseId, null, $productId, abs($qtyDiff), $unitCost, $date, 'adjustment', null, $notes, $userId);
    }

    private function applyMovement(
        string $type,
        int $warehouseId,
        ?int $targetWarehouseId,
        int $productId,
        float $qty,
        ?float $unitCost,
        string $date,
        ?string $referenceType,
        ?int $referenceId,
        ?string $notes,
        ?int $userId
    ): float {
        if ($qty <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($type, $warehouseId, $targetWarehouseId, $productId, $qty, $unitCost, $date, $referenceType, $referenceId, $notes, $userId) {
            /** @var ProductStock $stock */
            $stock = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = ProductStock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'qty' => 0,
                    'avg_cost' => 0,
                ]);
            }

            $oldQty = (float) $stock->qty;
            $oldCost = (float) $stock->avg_cost;
            $effectiveUnitCost = $unitCost ?? $oldCost;
            $lineTotal = $qty * $effectiveUnitCost;

            if (in_array($type, ['out', 'transfer_out', 'adjust_out'], true) && $oldQty < $qty) {
                throw new \RuntimeException('Insufficient stock.');
            }

            if (in_array($type, ['in', 'transfer_in', 'adjust_in'], true)) {
                $newQty = $oldQty + $qty;
                $newCost = $newQty > 0 ? (($oldQty * $oldCost) + ($qty * $effectiveUnitCost)) / $newQty : $oldCost;
            } else {
                $newQty = $oldQty - $qty;
                $newCost = $oldCost;
                $effectiveUnitCost = $oldCost;
                $lineTotal = $qty * $oldCost;
            }

            $stock->qty = $newQty;
            $stock->avg_cost = $newCost;
            $stock->save();

            StockMovement::create([
                'number' => app(AccountingService::class)->nextNumber('stock_movements', 'number', 'SM-'),
                'movement_date' => $date,
                'type' => $type,
                'warehouse_id' => $warehouseId,
                'target_warehouse_id' => $targetWarehouseId,
                'product_id' => $productId,
                'qty' => $qty,
                'unit_cost' => $effectiveUnitCost,
                'line_total' => $lineTotal,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $this->syncProductTotals($productId);

            return $effectiveUnitCost;
        });
    }

    public function syncProductTotals(int $productId): void
    {
        $totals = ProductStock::query()
            ->where('product_id', $productId)
            ->selectRaw('COALESCE(SUM(qty),0) as total_qty')
            ->selectRaw('COALESCE(SUM(qty * avg_cost),0) as total_value')
            ->first();

        $totalQty = (float) ($totals->total_qty ?? 0);
        $avgCost = $totalQty > 0 ? ((float) ($totals->total_value ?? 0) / $totalQty) : 0;

        Product::query()->whereKey($productId)->update([
            'quantity' => (int) round($totalQty),
            'avg_cost' => $avgCost,
        ]);
    }
}
