<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceContact;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\InventoryService;
use App\Services\ProductBarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $invoices = PurchaseInvoice::query()->with(['contact', 'warehouse'])->latest()->paginate(20);

        return view('admin.accounting.purchases.index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        $vendors = FinanceContact::query()
            ->whereIn('type', ['vendor', 'both'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()->orderBy('name')->limit(200)->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.accounting.purchases.create', [
            'vendors' => $vendors,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request, AccountingService $accounting, InventoryService $inventory, ProductBarcodeService $barcodeService)
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
            'barcode' => ['nullable', 'array'],
            'barcode.*' => ['nullable', 'string', 'max:100'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['required', 'array'],
            'unit_cost.*' => ['required', 'numeric', 'min:0'],
        ]);

        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $rows = [];
        $subtotal = 0.0;

        foreach ($data['description'] as $i => $description) {
            $qty = (float) ($data['qty'][$i] ?? 0);
            $unitCost = (float) ($data['unit_cost'][$i] ?? 0);
            $lineTotal = $qty * $unitCost;
            $subtotal += $lineTotal;
            $rows[] = [
                'product_id' => !empty($data['product_id'][$i]) ? (int) $data['product_id'][$i] : null,
                'barcode' => $barcodeService->normalize((string) ($data['barcode'][$i] ?? '')),
                'description' => $description,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ];
        }

        $total = max(0, $subtotal - $discount + $tax);

        DB::transaction(function () use ($data, $accounting, $subtotal, $discount, $tax, $total, $rows, $request) {
            $invoice = PurchaseInvoice::create([
                'number' => $accounting->nextNumber('purchase_invoices', 'number', 'PI-'),
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

            foreach ($rows as $row) {
                $invoice->items()->create([
                    'product_id' => $row['product_id'],
                    'description' => $row['description'],
                    'qty' => $row['qty'],
                    'unit_cost' => $row['unit_cost'],
                    'line_total' => $row['line_total'],
                ]);

                if (!empty($row['product_id'])) {
                    $product = Product::query()->find((int) $row['product_id']);
                    if ($product) {
                        $barcodeService->assignIfMissing($product, (string) ($row['barcode'] ?: $product->barcode ?: $product->sku));
                    }

                    $inventory->receive(
                        (int) $invoice->warehouse_id,
                        (int) $row['product_id'],
                        (float) $row['qty'],
                        (float) $row['unit_cost'],
                        (string) $invoice->invoice_date,
                        'purchase_invoice',
                        (int) $invoice->id,
                        'استلام من فاتورة مشتريات ' . $invoice->number,
                        optional($request->user())->id
                    );
                }
            }

            $accounting->postPurchaseInvoice($invoice, optional($request->user())->id);
        });

        return redirect()->route('admin.accounting.purchases.index')->with('success', 'تم إنشاء فاتورة المشتريات وترحيل القيد.');
    }
}
