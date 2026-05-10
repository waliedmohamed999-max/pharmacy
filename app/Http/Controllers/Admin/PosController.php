<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FinanceAccount;
use App\Models\FinanceContact;
use App\Models\FinancePayment;
use App\Models\Order;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()
            ->with(['category', 'stocks'])
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(800)
            ->get();
        $customers = FinanceContact::query()->whereIn('type', ['customer', 'both'])->where('is_active', true)->orderBy('name')->limit(200)->get();

        return view('admin.pos.index', [
            'warehouses' => $warehouses,
            'products' => $products,
            'customers' => $customers,
            'recentSales' => PosSale::query()->with('warehouse')->latest()->limit(8)->get(),
        ]);
    }

    public function store(Request $request, AccountingService $accounting, InventoryService $inventory)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_mode' => ['required', 'in:walkin,registered'],
            'contact_id' => ['nullable', 'exists:finance_contacts,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'in:cash,card,transfer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
        ]);

        $rows = [];
        $subtotal = 0.0;
        foreach ($data['product_id'] as $i => $productId) {
            $qty = (float) ($data['qty'][$i] ?? 0);
            $price = (float) ($data['unit_price'][$i] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $lineTotal = round($qty * $price, 2);
            $subtotal += $lineTotal;
            $rows[] = [
                'product_id' => (int) $productId,
                'qty' => $qty,
                'unit_price' => $price,
                'line_total' => $lineTotal,
            ];
        }

        if (empty($rows)) {
            throw ValidationException::withMessages(['product_id' => ['أضف منتجًا واحدًا على الأقل.']]);
        }

        $discount = (float) ($data['discount'] ?? 0);
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxableAmount = max(0, $subtotal - $discount);
        $tax = round($taxableAmount * ($taxRate / 100), 2);
        $total = max(0, round($taxableAmount + $tax, 2));
        $paidAmount = (float) ($data['paid_amount'] ?? $total);
        $change = max(0, round($paidAmount - $total, 2));
        $balance = max(0, round($total - $paidAmount, 2));

        $posSale = DB::transaction(function () use ($data, $rows, $subtotal, $discount, $tax, $total, $paidAmount, $change, $balance, $accounting, $inventory, $request) {
            $contact = $this->resolveCustomerContact($data);
            $lockedProducts = [];

            foreach ($rows as $row) {
                $product = Product::query()->whereKey($row['product_id'])->lockForUpdate()->first();
                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'product_id' => ['منتج غير متاح للبيع: ' . ($product?->name ?? $row['product_id'])],
                    ]);
                }

                $stock = ProductStock::query()
                    ->where('warehouse_id', (int) $data['warehouse_id'])
                    ->where('product_id', (int) $row['product_id'])
                    ->lockForUpdate()
                    ->first();

                if ((float) ($stock->qty ?? 0) < (float) $row['qty']) {
                    throw ValidationException::withMessages([
                        'qty' => ['الكمية غير كافية للمنتج: ' . $product->name],
                    ]);
                }

                $lockedProducts[$product->id] = $product;
            }

            $status = $balance <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'posted');

            $order = Order::create([
                'customer_id' => null,
                'customer_name' => $contact->name,
                'phone' => $contact->phone ?? '-',
                'city' => $contact->city ?? '-',
                'address' => $contact->address ?? '-',
                'notes' => ($data['notes'] ?? null) ? 'POS: ' . $data['notes'] : 'POS sale',
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => 0,
                'total' => $total,
            ]);

            $salesInvoice = SalesInvoice::create([
                'number' => $accounting->nextNumber('sales_invoices', 'number', 'SI-'),
                'contact_id' => $contact->id,
                'warehouse_id' => (int) $data['warehouse_id'],
                'invoice_date' => now()->toDateString(),
                'due_date' => null,
                'status' => $status,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => min($paidAmount, $total),
                'balance' => $balance,
                'notes' => 'POS sale',
            ]);

            $posSale = PosSale::create([
                'number' => $accounting->nextNumber('pos_sales', 'number', 'POS-'),
                'warehouse_id' => (int) $data['warehouse_id'],
                'contact_id' => $contact->id,
                'customer_name' => $contact->name,
                'customer_phone' => $contact->phone,
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => min($paidAmount, $total),
                'change_amount' => $change,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'sales_invoice_id' => $salesInvoice->id,
                'order_id' => $order->id,
                'created_by' => optional($request->user())->id,
            ]);

            $totalCost = 0.0;
            foreach ($rows as $row) {
                $product = $lockedProducts[$row['product_id']];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'price' => $row['unit_price'],
                    'qty' => $row['qty'],
                    'line_total' => $row['line_total'],
                ]);

                $salesInvoice->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                ]);

                $posSale->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                ]);

                $unitCost = $inventory->issue(
                    (int) $data['warehouse_id'],
                    (int) $product->id,
                    (float) $row['qty'],
                    now()->toDateString(),
                    'pos_sale',
                    (int) $posSale->id,
                    'صرف مبيعات نقاط بيع',
                    optional($request->user())->id
                );

                $totalCost += ((float) $row['qty'] * (float) $unitCost);
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

            if ($paidAmount > 0) {
                $cashAccountId = $this->resolveCashAccountId($data['payment_method']);
                $payment = FinancePayment::create([
                    'number' => $accounting->nextNumber('finance_payments', 'number', 'PAY-'),
                    'payment_date' => now()->toDateString(),
                    'direction' => 'in',
                    'contact_id' => $contact->id,
                    'account_id' => $cashAccountId,
                    'amount' => min($paidAmount, $total),
                    'method' => $data['payment_method'],
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $salesInvoice->id,
                    'notes' => 'تحصيل POS - ' . $posSale->number,
                ]);

                $accounting->postPayment(
                    'in',
                    now()->toDateString(),
                    min($paidAmount, $total),
                    $cashAccountId,
                    (int) $contact->id,
                    'sales_invoice',
                    (int) $payment->id,
                    optional($request->user())->id
                );
            }
            return $posSale;
        }, 3);

        return redirect()->route('admin.pos.show', $posSale)->with('success', 'تم تسجيل عملية نقاط البيع بنجاح.');
    }

    public function history()
    {
        $sales = PosSale::query()
            ->with(['warehouse', 'contact', 'salesInvoice'])
            ->latest()
            ->paginate(25);

        return view('admin.pos.history', compact('sales'));
    }

    public function show(PosSale $sale)
    {
        $sale->load(['warehouse', 'contact', 'salesInvoice', 'items.product']);
        return view('admin.pos.show', compact('sale'));
    }

    public function receipt(PosSale $sale)
    {
        $sale->load(['warehouse', 'contact', 'items.product']);
        return view('admin.pos.receipt', compact('sale'));
    }

    private function resolveCustomerContact(array $data): FinanceContact
    {
        if (($data['customer_mode'] ?? 'walkin') === 'registered' && !empty($data['contact_id'])) {
            $contact = FinanceContact::query()->find((int) $data['contact_id']);
            if ($contact) {
                return $contact;
            }
        }

        $name = trim((string) ($data['customer_name'] ?? 'عميل نقدي'));
        $phone = trim((string) ($data['customer_phone'] ?? ''));

        $contact = null;
        if ($phone !== '') {
            $contact = FinanceContact::query()->where('phone', $phone)->first();
        }

        if (!$contact) {
            $contact = FinanceContact::create([
                'type' => 'customer',
                'name' => $name !== '' ? $name : 'عميل نقدي',
                'phone' => $phone !== '' ? $phone : null,
                'is_active' => true,
            ]);
        }

        // Keep customer master in sync for operational reports.
        if ($phone !== '') {
            Customer::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $contact->name,
                    'phone' => $phone,
                    'is_active' => true,
                ]
            );
        }

        return $contact;
    }

    private function resolveCashAccountId(string $paymentMethod): int
    {
        $preferredCode = $paymentMethod === 'cash'
            ? AccountingService::ACCOUNT_CASH
            : AccountingService::ACCOUNT_BANK;

        $id = FinanceAccount::query()->where('code', $preferredCode)->value('id');
        if ($id) {
            return (int) $id;
        }

        $fallback = FinanceAccount::query()->whereIn('code', [
            AccountingService::ACCOUNT_CASH,
            AccountingService::ACCOUNT_BANK,
        ])->value('id');

        if ($fallback) {
            return (int) $fallback;
        }

        throw ValidationException::withMessages([
            'payment_method' => ['لا يوجد حساب نقدية/بنك معرف في شجرة الحسابات.'],
        ]);
    }
}
