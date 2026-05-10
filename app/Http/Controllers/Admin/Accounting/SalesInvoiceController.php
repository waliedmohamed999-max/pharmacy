<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceContact;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SalesInvoice::query()->with(['contact', 'warehouse'])->latest()->paginate(20);

        return view('admin.accounting.sales.index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        $customers = FinanceContact::query()
            ->whereIn('type', ['customer', 'both'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()->orderBy('name')->limit(200)->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.accounting.sales.create', [
            'customers' => $customers,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request, AccountingService $accounting, InventoryService $inventory)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:finance_contacts,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'max:2000'],
            'description' => ['required', 'array', 'min:1'],
            'description.*' => ['required', 'max:255'],
            'product_id' => ['nullable', 'array'],
            'product_id.*' => ['nullable', 'exists:products,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
        ]);

        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $rows = [];
        $subtotal = 0.0;

        foreach ($data['description'] as $i => $description) {
            $qty = (float) ($data['qty'][$i] ?? 0);
            $unitPrice = (float) ($data['unit_price'][$i] ?? 0);
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;
            $rows[] = [
                'product_id' => !empty($data['product_id'][$i]) ? (int) $data['product_id'][$i] : null,
                'description' => $description,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        $total = max(0, $subtotal - $discount + $tax);

        DB::transaction(function () use ($data, $accounting, $subtotal, $discount, $tax, $total, $rows, $request) {
            $invoice = SalesInvoice::create([
                'number' => $accounting->nextNumber('sales_invoices', 'number', 'SI-'),
                'contact_id' => $data['contact_id'],
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'posted',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalCost = 0.0;
            foreach ($rows as $row) {
                $invoice->items()->create($row);

                if (!empty($row['product_id'])) {
                    $saleQty = (float) $row['qty'];
                    $product = Product::query()->find($row['product_id']);
                    try {
                        $unitCost = $inventory->issue(
                            (int) $invoice->warehouse_id,
                            (int) $row['product_id'],
                            $saleQty,
                            (string) $invoice->invoice_date,
                            'sales_invoice',
                            (int) $invoice->id,
                            'صرف من فاتورة مبيعات ' . $invoice->number,
                            optional($request->user())->id
                        );
                    } catch (\RuntimeException $e) {
                        throw ValidationException::withMessages([
                            'qty' => ['الكمية غير كافية للمنتج: ' . ($product?->name ?: $row['product_id'])],
                        ]);
                    }
                    $totalCost += ($saleQty * $unitCost);
                }
            }

            $accounting->postSalesInvoice($invoice, optional($request->user())->id);
            $accounting->postSalesCost(
                $invoice->id,
                $invoice->number,
                (string) $invoice->invoice_date,
                $totalCost,
                (int) $invoice->contact_id,
                optional($request->user())->id
            );
        });

        return redirect()->route('admin.accounting.sales.index')->with('success', 'تم إنشاء فاتورة المبيعات وترحيل القيد.');
    }
}
