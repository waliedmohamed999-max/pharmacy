<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function index()
    {
        if (Warehouse::query()->count() < 1 || ProductStock::query()->count() < 1) {
            app(InventoryService::class)->syncProductsToDefaultWarehouse();
        }

        $stockRowsCount = ProductStock::query()->count();
        $warehousesCount = Warehouse::query()->count();
        $activeWarehousesCount = Warehouse::query()->where('is_active', true)->count();
        $productsCount = Product::query()->where('is_active', true)->count();
        $stockQty = $stockRowsCount > 0
            ? (float) ProductStock::query()->sum('qty')
            : (float) Product::query()->where('is_active', true)->sum('quantity');
        $stockValue = $stockRowsCount > 0
            ? (float) ProductStock::query()->selectRaw('COALESCE(SUM(qty * avg_cost),0) as v')->value('v')
            : (float) Product::query()->where('is_active', true)->selectRaw('COALESCE(SUM(quantity * avg_cost),0) as v')->value('v');
        $latestMovements = StockMovement::query()->with(['warehouse', 'product'])->latest()->limit(12)->get();
        $openCounts = StockCount::query()->where('status', 'draft')->count();
        $postedCounts = StockCount::query()->where('status', 'posted')->count();
        $lowStockRows = $this->lowStockRows($stockRowsCount);
        $lowStockCount = $lowStockRows->count();
        $reorderValue = (float) $lowStockRows->sum('estimated_cost');
        $zeroStockCount = $stockRowsCount > 0
            ? ProductStock::query()->where('qty', '<=', 0)->count()
            : Product::query()->where('is_active', true)->where('quantity', '<=', 0)->count();
        $noCostCount = Product::query()->where('is_active', true)->where(function ($q) {
            $q->whereNull('avg_cost')->orWhere('avg_cost', '<=', 0);
        })->count();
        $noReorderCount = Product::query()->where('is_active', true)->where(function ($q) {
            $q->whereNull('reorder_level')->orWhere('reorder_level', '<=', 0);
        })->count();
        $movementValue30 = (float) StockMovement::query()
            ->whereDate('movement_date', '>=', now()->subDays(30)->toDateString())
            ->sum('line_total');
        $movementQty30 = (float) StockMovement::query()
            ->whereDate('movement_date', '>=', now()->subDays(30)->toDateString())
            ->sum('qty');
        $movementTypes = StockMovement::query()
            ->whereDate('movement_date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('type, COUNT(*) as count, COALESCE(SUM(qty),0) as qty')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();
        $warehouseSummary = $this->warehouseSummary($stockRowsCount);
        $fastMovingProducts = $this->fastMovingProducts();
        $deadStockProducts = $this->deadStockProducts($stockRowsCount);
        $dataQualityIssues = [
            ['label' => 'منتجات بلا تكلفة', 'value' => $noCostCount, 'tone' => $noCostCount ? 'danger' : 'success'],
            ['label' => 'منتجات بلا حد طلب', 'value' => $noReorderCount, 'tone' => $noReorderCount ? 'warning' : 'success'],
            ['label' => 'أرصدة صفرية أو سالبة', 'value' => $zeroStockCount, 'tone' => $zeroStockCount ? 'danger' : 'success'],
            ['label' => 'مخازن غير نشطة', 'value' => max(0, $warehousesCount - $activeWarehousesCount), 'tone' => ($warehousesCount - $activeWarehousesCount) ? 'warning' : 'success'],
        ];
        $quickTools = $this->inventoryTools();

        return view('admin.inventory.index', compact(
            'stockRowsCount',
            'warehousesCount',
            'activeWarehousesCount',
            'productsCount',
            'stockQty',
            'stockValue',
            'latestMovements',
            'openCounts',
            'postedCounts',
            'lowStockCount',
            'lowStockRows',
            'reorderValue',
            'zeroStockCount',
            'noCostCount',
            'noReorderCount',
            'movementValue30',
            'movementQty30',
            'movementTypes',
            'warehouseSummary',
            'fastMovingProducts',
            'deadStockProducts',
            'dataQualityIssues',
            'quickTools'
        ));
    }

    public function exportOverview(): StreamedResponse
    {
        $rows = $this->lowStockRows(ProductStock::query()->count());

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Warehouse', 'Product', 'Current Qty', 'Reorder Level', 'Suggested Qty', 'Avg Cost', 'Estimated Cost']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->warehouse_name,
                    $row->product_name,
                    (float) $row->qty,
                    (float) $row->reorder_level,
                    (float) $row->suggested_qty,
                    (float) $row->avg_cost,
                    (float) $row->estimated_cost,
                ]);
            }

            fclose($output);
        }, 'inventory-reorder-plan-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function syncProducts(InventoryService $inventory)
    {
        $result = $inventory->syncProductsToDefaultWarehouse();

        return back()->with(
            'success',
            "تم ربط المخزون بالمنتجات: إنشاء {$result['created']} رصيد وتحديث {$result['updated']} رصيد داخل المخزن الرئيسي."
        );
    }

    public function stocks(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $productId = $request->integer('product_id') ?: null;

        $stocks = ProductStock::query()
            ->with(['warehouse', 'product'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->orderByDesc('qty')
            ->paginate(30)
            ->withQueryString();

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();

        return view('admin.inventory.stocks', compact('stocks', 'warehouses', 'products', 'warehouseId', 'productId'));
    }

    public function movements(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $type = $request->string('type')->value() ?: null;

        $movements = StockMovement::query()
            ->with(['warehouse', 'targetWarehouse', 'product'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $types = ['in', 'out', 'transfer_out', 'transfer_in', 'adjust_in', 'adjust_out'];

        return view('admin.inventory.movements', compact('movements', 'warehouses', 'types', 'warehouseId', 'type'));
    }

    public function warehouses()
    {
        $warehouses = Warehouse::query()->latest()->paginate(20);
        return view('admin.inventory.warehouses', compact('warehouses'));
    }

    public function storeWarehouse(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'code' => ['required', 'max:30', 'unique:warehouses,code'],
            'location' => ['nullable', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Warehouse::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'تم إضافة المخزن.');
    }

    public function transferForm()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();
        return view('admin.inventory.transfer', compact('warehouses', 'products'));
    }

    public function transfer(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'max:2000'],
        ]);

        try {
            $inventory->transfer(
                (int) $data['from_warehouse_id'],
                (int) $data['to_warehouse_id'],
                (int) $data['product_id'],
                (float) $data['qty'],
                $data['movement_date'],
                $data['notes'] ?? null,
                optional($request->user())->id
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['qty' => ['الكمية غير كافية في المخزن المصدر.']]);
        }

        return redirect()->route('admin.inventory.movements')->with('success', 'تم تحويل المخزون بنجاح.');
    }

    public function adjustmentForm()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();
        return view('admin.inventory.adjustment', compact('warehouses', 'products'));
    }

    public function adjustment(Request $request, InventoryService $inventory, AccountingService $accounting)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'qty_diff' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'max:2000'],
        ]);

        try {
            $inventory->adjust(
                (int) $data['warehouse_id'],
                (int) $data['product_id'],
                (float) $data['qty_diff'],
                (float) $data['unit_cost'],
                $data['movement_date'],
                $data['notes'] ?? null,
                optional($request->user())->id
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['qty_diff' => ['تسوية سالبة أكبر من الرصيد المتاح.']]);
        }

        $amount = abs((float) $data['qty_diff']) * (float) $data['unit_cost'];
        $isIncrease = ((float) $data['qty_diff']) > 0;
        $product = Product::query()->find((int) $data['product_id']);
        $accounting->postInventoryAdjustment(
            $data['movement_date'],
            $amount,
            $isIncrease,
            'تسوية مخزون للمنتج ' . ($product?->name ?? $data['product_id']),
            optional($request->user())->id
        );

        return redirect()->route('admin.inventory.movements')->with('success', 'تمت تسوية المخزون وترحيل القيد المالي.');
    }

    public function receiveForm()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();
        return view('admin.inventory.receive', compact('warehouses', 'products'));
    }

    public function receive(Request $request, InventoryService $inventory, AccountingService $accounting)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'max:2000'],
        ]);

        $inventory->receive(
            (int) $data['warehouse_id'],
            (int) $data['product_id'],
            (float) $data['qty'],
            (float) $data['unit_cost'],
            $data['movement_date'],
            'manual_receive',
            null,
            $data['notes'] ?? null,
            optional($request->user())->id
        );

        $amount = (float) $data['qty'] * (float) $data['unit_cost'];
        $accounting->postInventoryReceipt(
            $data['movement_date'],
            $amount,
            'استلام مخزني يدوي',
            optional($request->user())->id
        );

        return redirect()->route('admin.inventory.movements')->with('success', 'تم تسجيل سند الاستلام وربطه ماليًا.');
    }

    public function issueForm()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();
        return view('admin.inventory.issue', compact('warehouses', 'products'));
    }

    public function issue(Request $request, InventoryService $inventory, AccountingService $accounting)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'max:2000'],
        ]);

        try {
            $unitCost = $inventory->issue(
                (int) $data['warehouse_id'],
                (int) $data['product_id'],
                (float) $data['qty'],
                $data['movement_date'],
                'manual_issue',
                null,
                $data['notes'] ?? null,
                optional($request->user())->id
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['qty' => ['الكمية غير كافية.']]);
        }

        $accounting->postInventoryIssue(
            $data['movement_date'],
            ((float) $data['qty'] * (float) $unitCost),
            'صرف مخزني يدوي',
            optional($request->user())->id
        );

        return redirect()->route('admin.inventory.movements')->with('success', 'تم تسجيل سند الصرف وربطه ماليًا.');
    }

    public function alerts(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $rows = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->when($warehouseId, fn ($q) => $q->where('product_stocks.warehouse_id', $warehouseId))
            ->where('products.reorder_level', '>', 0)
            ->whereColumn('product_stocks.qty', '<=', 'products.reorder_level')
            ->selectRaw('product_stocks.id, products.id as product_id, products.name as product_name, products.reorder_level, products.reorder_qty, warehouses.name as warehouse_name, product_stocks.qty')
            ->orderBy('product_stocks.qty')
            ->paginate(40)
            ->withQueryString();

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.inventory.alerts', compact('rows', 'warehouses', 'warehouseId'));
    }

    public function stockCard(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $productId = $request->integer('product_id') ?: null;
        $dateFrom = $request->string('date_from')->value() ?: null;
        $dateTo = $request->string('date_to')->value() ?: null;

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->limit(300)->get();
        $movements = collect();
        $openingBalance = 0.0;

        if ($warehouseId && $productId) {
            $deltaExpr = "CASE WHEN type IN ('in','transfer_in','adjust_in') THEN qty ELSE -qty END";

            $openingQuery = StockMovement::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId);
            if ($dateFrom) {
                $openingQuery->whereDate('movement_date', '<', $dateFrom);
            }

            $openingBalance = (float) $openingQuery->selectRaw("COALESCE(SUM({$deltaExpr}),0) as opening")->value('opening');

            $query = StockMovement::query()
                ->with(['warehouse', 'targetWarehouse', 'product'])
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->when($dateFrom, fn ($q) => $q->whereDate('movement_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('movement_date', '<=', $dateTo))
                ->orderBy('movement_date')
                ->orderBy('id');

            $running = $openingBalance;
            $movements = $query->get()->map(function ($m) use (&$running) {
                $inTypes = ['in', 'transfer_in', 'adjust_in'];
                $delta = in_array($m->type, $inTypes, true) ? (float) $m->qty : -(float) $m->qty;
                $running += $delta;
                $m->delta_qty = $delta;
                $m->running_balance = $running;
                return $m;
            });
        }

        return view('admin.inventory.stock-card', compact(
            'warehouses',
            'products',
            'warehouseId',
            'productId',
            'dateFrom',
            'dateTo',
            'openingBalance',
            'movements'
        ));
    }

    public function movementPdf(StockMovement $movement)
    {
        $movement->load(['warehouse', 'targetWarehouse', 'product']);
        $branding = $this->pdfBranding();
        $generatedAt = now()->format('Y-m-d H:i');

        return Pdf::loadView('admin.inventory.pdf.movement', compact('movement', 'branding', 'generatedAt'))
            ->download('stock-movement-' . $movement->number . '.pdf');
    }

    public function counts()
    {
        $counts = StockCount::query()->with('warehouse')->latest()->paginate(20);
        return view('admin.inventory.counts.index', compact('counts'));
    }

    public function createCount()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        return view('admin.inventory.counts.create', compact('warehouses'));
    }

    public function storeCount(Request $request, AccountingService $accounting)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'count_date' => ['required', 'date'],
            'notes' => ['nullable', 'max:2000'],
            'include_zero_stock' => ['nullable', 'boolean'],
        ]);

        $count = DB::transaction(function () use ($data, $request, $accounting) {
            $count = StockCount::create([
                'number' => $accounting->nextNumber('stock_counts', 'number', 'SC-'),
                'warehouse_id' => $data['warehouse_id'],
                'count_date' => $data['count_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => optional($request->user())->id,
            ]);

            $query = ProductStock::query()
                ->where('warehouse_id', $data['warehouse_id'])
                ->with('product');

            if (!$request->boolean('include_zero_stock')) {
                $query->where('qty', '>', 0);
            }

            foreach ($query->get() as $stock) {
                $count->items()->create([
                    'product_id' => $stock->product_id,
                    'snapshot_qty' => (float) $stock->qty,
                    'counted_qty' => null,
                    'diff_qty' => 0,
                    'unit_cost_snapshot' => (float) $stock->avg_cost,
                    'diff_value' => 0,
                ]);
            }

            return $count;
        });

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', 'تم إنشاء جلسة الجرد.');
    }

    public function showCount(StockCount $count, Request $request)
    {
        $items = $count->items()
            ->with('product')
            ->when($request->boolean('only_diff'), fn ($q) => $q->where('diff_qty', '!=', 0))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.inventory.counts.show', compact('count', 'items'));
    }

    public function updateCountItems(StockCount $count, Request $request)
    {
        if ($count->status !== 'draft') {
            return back()->with('error', 'لا يمكن تعديل جلسة جرد معتمدة.');
        }

        $data = $request->validate([
            'counted_qty' => ['required', 'array'],
            'counted_qty.*' => ['nullable', 'numeric', 'min:0'],
            'item_notes' => ['nullable', 'array'],
            'item_notes.*' => ['nullable', 'max:1000'],
        ]);

        DB::transaction(function () use ($count, $data) {
            foreach ($data['counted_qty'] as $itemId => $countedQty) {
                /** @var StockCountItem|null $item */
                $item = $count->items()->whereKey($itemId)->lockForUpdate()->first();
                if (!$item) {
                    continue;
                }

                $cQty = $countedQty === null || $countedQty === '' ? null : (float) $countedQty;
                $snapshot = (float) $item->snapshot_qty;
                $diff = $cQty === null ? 0.0 : ($cQty - $snapshot);
                $diffValue = $diff * (float) $item->unit_cost_snapshot;

                $item->counted_qty = $cQty;
                $item->diff_qty = $diff;
                $item->diff_value = $diffValue;
                $item->notes = $data['item_notes'][$itemId] ?? null;
                $item->save();
            }
        });

        return back()->with('success', 'تم حفظ كميات الجرد.');
    }

    public function postCount(StockCount $count, InventoryService $inventory, AccountingService $accounting, Request $request)
    {
        if ($count->status !== 'draft') {
            return back()->with('error', 'الجلسة معتمدة بالفعل.');
        }

        DB::transaction(function () use ($count, $inventory, $accounting, $request) {
            foreach ($count->items()->lockForUpdate()->get() as $item) {
                if ($item->counted_qty === null) {
                    continue;
                }

                $diff = (float) $item->diff_qty;
                if (abs($diff) < 0.0001) {
                    continue;
                }

                $inventory->adjust(
                    (int) $count->warehouse_id,
                    (int) $item->product_id,
                    $diff,
                    (float) $item->unit_cost_snapshot,
                    (string) $count->count_date,
                    'تسوية من جرد ' . $count->number,
                    optional($request->user())->id
                );

                $accounting->postInventoryAdjustment(
                    (string) $count->count_date,
                    abs($diff) * (float) $item->unit_cost_snapshot,
                    $diff > 0,
                    'تسوية جرد للمخزن #' . $count->warehouse_id . ' - الجلسة ' . $count->number,
                    optional($request->user())->id
                );
            }

            $count->status = 'posted';
            $count->posted_at = now();
            $count->save();
        });

        return redirect()->route('admin.inventory.counts.show', $count)->with('success', 'تم اعتماد الجرد وترحيل التسويات ماليًا.');
    }

    public function countPdf(StockCount $count)
    {
        $count->load(['warehouse', 'items.product']);
        $branding = $this->pdfBranding();
        $generatedAt = now()->format('Y-m-d H:i');

        return Pdf::loadView('admin.inventory.pdf.count', compact('count', 'branding', 'generatedAt'))
            ->download('stock-count-' . $count->number . '.pdf');
    }

    private function lowStockRows(int $stockRowsCount)
    {
        if ($stockRowsCount > 0) {
            return ProductStock::query()
                ->join('products', 'products.id', '=', 'product_stocks.product_id')
                ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
                ->where('products.is_active', true)
                ->where('products.reorder_level', '>', 0)
                ->whereColumn('product_stocks.qty', '<=', 'products.reorder_level')
                ->selectRaw('warehouses.name as warehouse_name, products.id as product_id, products.name as product_name, product_stocks.qty, products.reorder_level, products.reorder_qty, product_stocks.avg_cost')
                ->orderBy('product_stocks.qty')
                ->limit(30)
                ->get()
                ->map(function ($row) {
                    $suggested = max((float) $row->reorder_qty, (float) $row->reorder_level - (float) $row->qty, 0);
                    $row->suggested_qty = $suggested;
                    $row->estimated_cost = $suggested * (float) $row->avg_cost;
                    return $row;
                });
        }

        return Product::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->selectRaw("'الرصيد العام' as warehouse_name, id as product_id, name as product_name, quantity as qty, reorder_level, reorder_qty, avg_cost")
            ->orderBy('quantity')
            ->limit(30)
            ->get()
            ->map(function ($row) {
                $suggested = max((float) $row->reorder_qty, (float) $row->reorder_level - (float) $row->qty, 0);
                $row->suggested_qty = $suggested;
                $row->estimated_cost = $suggested * (float) $row->avg_cost;
                return $row;
            });
    }

    private function warehouseSummary(int $stockRowsCount)
    {
        if ($stockRowsCount < 1) {
            return collect();
        }

        return ProductStock::query()
            ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->selectRaw('warehouses.id, warehouses.name, warehouses.code, warehouses.is_active, COUNT(product_stocks.id) as products_count, COALESCE(SUM(product_stocks.qty),0) as qty, COALESCE(SUM(product_stocks.qty * product_stocks.avg_cost),0) as value')
            ->groupBy('warehouses.id', 'warehouses.name', 'warehouses.code', 'warehouses.is_active')
            ->orderByDesc('value')
            ->get();
    }

    private function fastMovingProducts()
    {
        return StockMovement::query()
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->whereIn('stock_movements.type', ['out', 'transfer_out', 'adjust_out'])
            ->whereDate('stock_movements.movement_date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('products.id as product_id, products.name as product_name, COUNT(*) as movements_count, COALESCE(SUM(stock_movements.qty),0) as qty, COALESCE(SUM(stock_movements.line_total),0) as value')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->limit(8)
            ->get();
    }

    private function deadStockProducts(int $stockRowsCount)
    {
        $activeOutProductIds = StockMovement::query()
            ->whereIn('type', ['out', 'transfer_out'])
            ->whereDate('movement_date', '>=', now()->subDays(90)->toDateString())
            ->pluck('product_id');

        if ($stockRowsCount > 0) {
            return ProductStock::query()
                ->join('products', 'products.id', '=', 'product_stocks.product_id')
                ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
                ->where('product_stocks.qty', '>', 0)
                ->whereNotIn('product_stocks.product_id', $activeOutProductIds)
                ->selectRaw('warehouses.name as warehouse_name, products.id as product_id, products.name as product_name, product_stocks.qty, product_stocks.avg_cost, (product_stocks.qty * product_stocks.avg_cost) as value')
                ->orderByDesc('value')
                ->limit(8)
                ->get();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereNotIn('id', $activeOutProductIds)
            ->selectRaw("'الرصيد العام' as warehouse_name, id as product_id, name as product_name, quantity as qty, avg_cost, (quantity * avg_cost) as value")
            ->orderByDesc('value')
            ->limit(8)
            ->get();
    }

    private function inventoryTools(): array
    {
        return [
            ['label' => 'أرصدة المخزون', 'desc' => 'بحث حسب المخزن أو الصنف ومراجعة الكميات والتكلفة', 'route' => route('admin.inventory.stocks')],
            ['label' => 'حركات المخزون', 'desc' => 'كل الوارد والصادر والتحويلات والتسويات', 'route' => route('admin.inventory.movements')],
            ['label' => 'سند استلام', 'desc' => 'إدخال وارد مخزني وترحيله ماليًا', 'route' => route('admin.inventory.receive.form')],
            ['label' => 'سند صرف', 'desc' => 'صرف مخزني يدوي مع قيد تكلفة', 'route' => route('admin.inventory.issue.form')],
            ['label' => 'تحويل مخزني', 'desc' => 'نقل صنف بين مخازن أو فروع', 'route' => route('admin.inventory.transfer.form')],
            ['label' => 'تسوية مخزون', 'desc' => 'زيادة أو نقص مع أثر محاسبي', 'route' => route('admin.inventory.adjustment.form')],
            ['label' => 'تنبيهات النواقص', 'desc' => 'اقتراح كميات إعادة الطلب', 'route' => route('admin.inventory.alerts')],
            ['label' => 'كارت صنف', 'desc' => 'كشف حركة ورصيد جاري لصنف محدد', 'route' => route('admin.inventory.stock-card')],
            ['label' => 'الجرد الفعلي', 'desc' => 'جلسات جرد واعتماد فروقات', 'route' => route('admin.inventory.counts.index')],
            ['label' => 'المخازن والفروع', 'desc' => 'إدارة المخازن ونقاط التخزين', 'route' => route('admin.inventory.warehouses')],
        ];
    }

    private function pdfBranding(): array
    {
        return [
            'company_name' => StoreSetting::getValue('footer_brand_title', config('app.name')),
            'contact_phone' => StoreSetting::getValue('footer_contact_phone'),
            'contact_email' => StoreSetting::getValue('footer_contact_email'),
            'logo_data_uri' => $this->loadLogoDataUri(),
        ];
    }

    private function loadLogoDataUri(): ?string
    {
        $candidates = [
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('images/logo.jpeg'),
            public_path('images/finance-logo.svg'),
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                default => null,
            };

            if (!$mime) {
                continue;
            }

            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        return null;
    }
}
