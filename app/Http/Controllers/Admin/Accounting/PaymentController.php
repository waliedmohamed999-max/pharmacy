<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceContact;
use App\Models\FinancePayment;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = FinancePayment::query()
            ->with(['contact', 'account'])
            ->latest()
            ->paginate(20);

        return view('admin.accounting.payments.index', [
            'payments' => $payments,
        ]);
    }

    public function create()
    {
        $contacts = FinanceContact::query()->where('is_active', true)->orderBy('name')->get();
        $cashAccounts = FinanceAccount::query()
            ->whereIn('code', [AccountingService::ACCOUNT_CASH, AccountingService::ACCOUNT_BANK])
            ->orderBy('code')
            ->get();

        $salesInvoices = SalesInvoice::query()->where('balance', '>', 0)->latest()->limit(100)->get();
        $purchaseInvoices = PurchaseInvoice::query()->where('balance', '>', 0)->latest()->limit(100)->get();

        return view('admin.accounting.payments.create', [
            'contacts' => $contacts,
            'cashAccounts' => $cashAccounts,
            'salesInvoices' => $salesInvoices,
            'purchaseInvoices' => $purchaseInvoices,
        ]);
    }

    public function store(Request $request, AccountingService $accounting)
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'direction' => ['required', 'in:in,out'],
            'contact_id' => ['nullable', 'exists:finance_contacts,id'],
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'max:100'],
            'reference_type' => ['nullable', 'in:sales_invoice,purchase_invoice'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $request, $accounting) {
            $payment = FinancePayment::create([
                'number' => $accounting->nextNumber('finance_payments', 'number', 'PAY-'),
                'payment_date' => $data['payment_date'],
                'direction' => $data['direction'],
                'contact_id' => $data['contact_id'] ?? null,
                'account_id' => $data['account_id'],
                'amount' => $data['amount'],
                'method' => $data['method'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (($data['reference_type'] ?? null) === 'sales_invoice' && !empty($data['reference_id'])) {
                $invoice = SalesInvoice::query()->find($data['reference_id']);
                if ($invoice) {
                    $invoice->paid_amount = (float) $invoice->paid_amount + (float) $data['amount'];
                    $invoice->balance = max(0, (float) $invoice->total - (float) $invoice->paid_amount);
                    if ($invoice->balance <= 0) {
                        $invoice->status = 'paid';
                    }
                    $invoice->save();
                }
            }

            if (($data['reference_type'] ?? null) === 'purchase_invoice' && !empty($data['reference_id'])) {
                $invoice = PurchaseInvoice::query()->find($data['reference_id']);
                if ($invoice) {
                    $invoice->paid_amount = (float) $invoice->paid_amount + (float) $data['amount'];
                    $invoice->balance = max(0, (float) $invoice->total - (float) $invoice->paid_amount);
                    if ($invoice->balance <= 0) {
                        $invoice->status = 'paid';
                    }
                    $invoice->save();
                }
            }

            $accounting->postPayment(
                $data['direction'],
                $data['payment_date'],
                (float) $data['amount'],
                (int) $data['account_id'],
                isset($data['contact_id']) ? (int) $data['contact_id'] : null,
                $data['reference_type'] ?? 'payment',
                $payment->id,
                optional($request->user())->id
            );
        });

        return redirect()->route('admin.accounting.payments.index')->with('success', 'تم تسجيل السداد وترحيل القيد.');
    }
}
