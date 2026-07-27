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
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'direction' => ['nullable', 'in:in,out'],
            'contact_id' => ['nullable', 'integer', 'exists:finance_contacts,id'],
            'account_id' => ['nullable', 'integer', 'exists:finance_accounts,id'],
        ]);

        $query = FinancePayment::query()
            ->with(['contact', 'account'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payment_date', '<=', $date))
            ->when($filters['direction'] ?? null, fn ($q, $direction) => $q->where('direction', $direction))
            ->when($filters['contact_id'] ?? null, fn ($q, $contactId) => $q->where('contact_id', $contactId))
            ->when($filters['account_id'] ?? null, fn ($q, $accountId) => $q->where('account_id', $accountId));

        $summaryQuery = clone $query;
        $payments = $query->latest('payment_date')->latest('id')->paginate(20)->withQueryString();

        $summary = [
            'cash_in' => (float) (clone $summaryQuery)->where('direction', 'in')->sum('amount'),
            'cash_out' => (float) (clone $summaryQuery)->where('direction', 'out')->sum('amount'),
            'count' => (clone $summaryQuery)->count(),
        ];
        $summary['net'] = $summary['cash_in'] - $summary['cash_out'];

        $contacts = FinanceContact::query()->where('is_active', true)->orderBy('name')->get();
        $cashAccounts = FinanceAccount::query()
            ->whereIn('code', [AccountingService::ACCOUNT_CASH, AccountingService::ACCOUNT_BANK])
            ->orderBy('code')
            ->get();

        return view('admin.accounting.payments.index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $filters,
            'contacts' => $contacts,
            'cashAccounts' => $cashAccounts,
        ]);
    }

    public function create()
    {
        $contacts = FinanceContact::query()->where('is_active', true)->orderBy('name')->get();
        $cashAccounts = FinanceAccount::query()
            ->whereIn('code', [AccountingService::ACCOUNT_CASH, AccountingService::ACCOUNT_BANK])
            ->orderBy('code')
            ->get();

        $salesInvoices = SalesInvoice::query()
            ->with('contact')
            ->where('balance', '>', 0)
            ->latest()
            ->limit(100)
            ->get();

        $purchaseInvoices = PurchaseInvoice::query()
            ->with('contact')
            ->where('balance', '>', 0)
            ->latest()
            ->limit(100)
            ->get();

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

        if (!empty($data['reference_type']) && !empty($data['reference_id'])) {
            if ($data['reference_type'] === 'sales_invoice' && $data['direction'] !== 'in') {
                return back()->withInput()->with('error', 'فاتورة المبيعات يجب أن ترتبط بعملية تحصيل من عميل.');
            }

            if ($data['reference_type'] === 'purchase_invoice' && $data['direction'] !== 'out') {
                return back()->withInput()->with('error', 'فاتورة المشتريات يجب أن ترتبط بعملية سداد لمورد.');
            }

            $invoice = $data['reference_type'] === 'sales_invoice'
                ? SalesInvoice::query()->find($data['reference_id'])
                : PurchaseInvoice::query()->find($data['reference_id']);

            if (!$invoice) {
                return back()->withInput()->with('error', 'الفاتورة المحددة غير موجودة.');
            }

            if ((float) $data['amount'] > (float) $invoice->balance) {
                return back()->withInput()->with('error', 'المبلغ أكبر من الرصيد المتبقي على الفاتورة.');
            }

            $data['contact_id'] = $data['contact_id'] ?? $invoice->contact_id;
        }

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
