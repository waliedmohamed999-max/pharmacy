<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Models\Customer;
use App\Models\FinanceContact;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function index()
    {
        $summary = $this->cart->summary();

        if (empty($summary['items'])) {
            return redirect()->route('store.cart.index')->with('error', 'السلة فارغة');
        }

        return view('store.checkout', $summary);
    }

    public function store(
        CheckoutRequest $request,
        InventoryService $inventory,
        AccountingService $accounting
    ) {
        $summary = $this->cart->summary();
        if (empty($summary['items'])) {
            return back()->with('error', 'السلة فارغة');
        }

        $warehouseId = $inventory->defaultStorefrontWarehouseId();
        if (!$warehouseId) {
            return back()->with('error', 'لا يوجد مخزن افتراضي للمتجر.');
        }

        try {
            DB::transaction(function () use ($request, $summary, $warehouseId, $inventory, $accounting) {
                $lockedProducts = [];

                foreach ($summary['items'] as $item) {
                    $product = Product::query()->whereKey($item['product_id'])->lockForUpdate()->first();
                    if (!$product || !$product->is_active) {
                        throw new \RuntimeException('المنتج غير متاح: ' . $item['name']);
                    }

                    $stock = ProductStock::query()
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    $available = (float) ($stock->qty ?? 0);
                    if ($available < (float) $item['qty']) {
                        throw new \RuntimeException('الكمية غير كافية للمنتج: ' . $item['name']);
                    }

                    $lockedProducts[$product->id] = $product;
                }

                $customer = Customer::query()
                    ->where('phone', $request->string('phone')->value())
                    ->first();

                if ($customer) {
                    $customer->update([
                        'name' => $request->string('customer_name')->value(),
                        'phone' => $request->string('phone')->value(),
                        'city' => $request->string('city')->value(),
                        'address' => $request->string('address')->value(),
                        'is_active' => true,
                    ]);
                } else {
                    $customer = Customer::create([
                        'name' => $request->string('customer_name')->value(),
                        'phone' => $request->string('phone')->value(),
                        'city' => $request->string('city')->value(),
                        'address' => $request->string('address')->value(),
                        'is_active' => true,
                    ]);
                }

                $financeContact = FinanceContact::query()
                    ->where('phone', $request->string('phone')->value())
                    ->first();

                if ($financeContact) {
                    $financeContact->update([
                        'type' => in_array($financeContact->type, ['customer', 'both'], true) ? $financeContact->type : 'both',
                        'name' => $request->string('customer_name')->value(),
                        'phone' => $request->string('phone')->value(),
                        'city' => $request->string('city')->value(),
                        'address' => $request->string('address')->value(),
                        'is_active' => true,
                    ]);
                } else {
                    $financeContact = FinanceContact::create([
                        'type' => 'customer',
                        'name' => $request->string('customer_name')->value(),
                        'phone' => $request->string('phone')->value(),
                        'city' => $request->string('city')->value(),
                        'address' => $request->string('address')->value(),
                        'is_active' => true,
                    ]);
                }

                $couponNote = '';
                if (!empty($summary['coupon']['code'] ?? null)) {
                    $couponNote = ' | كوبون: ' . $summary['coupon']['code'];
                }

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'customer_name' => $request->string('customer_name')->value(),
                    'phone' => $request->string('phone')->value(),
                    'city' => $request->string('city')->value(),
                    'address' => $request->string('address')->value(),
                    'notes' => trim((string) $request->input('notes') . $couponNote),
                    'status' => 'new',
                    'subtotal' => $summary['subtotal'],
                    'discount' => $summary['discount'] ?? 0,
                    'shipping' => 0,
                    'total' => $summary['total'],
                ]);

                $salesInvoice = SalesInvoice::create([
                    'number' => $accounting->nextNumber('sales_invoices', 'number', 'SI-'),
                    'contact_id' => $financeContact->id,
                    'warehouse_id' => $warehouseId,
                    'invoice_date' => now()->toDateString(),
                    'due_date' => null,
                    'status' => 'posted',
                    'subtotal' => $summary['subtotal'],
                    'discount' => $summary['discount'] ?? 0,
                    'tax' => 0,
                    'total' => $summary['total'],
                    'paid_amount' => 0,
                    'balance' => $summary['total'],
                    'notes' => 'فاتورة متجر إلكتروني من الطلب #' . $order->id . $couponNote,
                ]);

                $totalCost = 0.0;
                foreach ($summary['items'] as $item) {
                    $product = $lockedProducts[$item['product_id']];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name_snapshot' => $product->name,
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'line_total' => $item['line_total'],
                    ]);

                    $salesInvoice->items()->create([
                        'product_id' => $product->id,
                        'description' => $product->name,
                        'qty' => $item['qty'],
                        'unit_price' => $item['price'],
                        'line_total' => $item['line_total'],
                    ]);

                    $unitCost = $inventory->issue(
                        $warehouseId,
                        (int) $product->id,
                        (float) $item['qty'],
                        now()->toDateString(),
                        'sales_invoice',
                        (int) $salesInvoice->id,
                        'صرف مبيعات متجر إلكتروني',
                        optional($request->user())->id
                    );

                    $totalCost += ((float) $item['qty'] * (float) $unitCost);
                }

                $accounting->postSalesInvoice($salesInvoice, optional($request->user())->id);
                $accounting->postSalesCost(
                    $salesInvoice->id,
                    $salesInvoice->number,
                    (string) $salesInvoice->invoice_date,
                    $totalCost,
                    (int) $salesInvoice->contact_id,
                    optional($request->user())->id
                );

                $this->cart->consumeCouponIfAny();
            }, 3);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->cart->clear();

        return redirect()->route('store.order.success')->with('success', 'تم إنشاء الطلب بنجاح');
    }
}

