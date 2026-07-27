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
use App\Services\ZatcaQrService;
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

    public function show(PurchaseInvoice $purchase, ProductBarcodeService $barcodeService, ZatcaQrService $zatcaQr)
    {
        return $this->invoiceView($purchase, $barcodeService, $zatcaQr, false);
    }

    public function print(PurchaseInvoice $purchase, ProductBarcodeService $barcodeService, ZatcaQrService $zatcaQr)
    {
        return $this->invoiceView($purchase, $barcodeService, $zatcaQr, true);
    }

    public function store(Request $request, AccountingService $accounting, InventoryService $inventory, ProductBarcodeService $barcodeService, ZatcaQrService $zatcaQr)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'exists:finance_contacts,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255'],
            'supplier_tax_number' => ['nullable', 'string', 'max:32'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
        $taxRate = (float) ($data['tax_rate'] ?? 15);
        $rows = [];
        $subtotal = 0.0;

        foreach ($data['description'] as $i => $description) {
            $qty = (float) ($data['qty'][$i] ?? 0);
            $unitCost = (float) ($data['unit_cost'][$i] ?? 0);
            $lineTotal = round($qty * $unitCost, 2);
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

        $taxableAmount = max(0, round($subtotal - $discount, 2));
        $tax = round($taxableAmount * ($taxRate / 100), 2);
        $total = max(0, round($taxableAmount + $tax, 2));

        $invoice = DB::transaction(function () use ($data, $accounting, $subtotal, $discount, $taxRate, $taxableAmount, $tax, $total, $rows, $request, $inventory, $barcodeService, $zatcaQr) {
            $contact = FinanceContact::query()->findOrFail((int) $data['contact_id']);
            $supplierTaxNumber = preg_replace('/\D+/', '', (string) ($data['supplier_tax_number'] ?: $contact->tax_number ?: '')) ?: null;

            if ($supplierTaxNumber && $contact->tax_number !== $supplierTaxNumber) {
                $contact->forceFill(['tax_number' => $supplierTaxNumber])->save();
            }

            $invoice = PurchaseInvoice::create([
                'number' => $accounting->nextNumber('purchase_invoices', 'number', 'PI-'),
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'contact_id' => $data['contact_id'],
                'warehouse_id' => $data['warehouse_id'],
                'supplier_tax_number' => $supplierTaxNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'posted',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'taxable_amount' => $taxableAmount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
                'notes' => $data['notes'] ?? null,
                'zatca_status' => 'ready',
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

            $invoice->load('contact');
            $invoice->forceFill([
                'zatca_qr_payload' => $zatcaQr->purchaseInvoicePayload($invoice),
            ])->save();

            $accounting->postPurchaseInvoice($invoice, optional($request->user())->id);

            return $invoice;
        });

        return redirect()
            ->route('admin.accounting.purchases.show', $invoice)
            ->with('success', 'تم إنشاء فاتورة المشتريات الضريبية وتجهيز QR زاتكا.');
    }

    private function invoiceView(PurchaseInvoice $invoice, ProductBarcodeService $barcodeService, ZatcaQrService $zatcaQr, bool $printMode)
    {
        $invoice->load(['contact', 'warehouse', 'items.product']);

        return view('admin.accounting.purchases.show', [
            'invoice' => $invoice,
            'barcodeSvg' => $barcodeService->svg($invoice->number, 52),
            'zatcaQrSvg' => $zatcaQr->svg((string) $invoice->zatca_qr_payload, 190),
            'printMode' => $printMode,
        ]);
    }
}
