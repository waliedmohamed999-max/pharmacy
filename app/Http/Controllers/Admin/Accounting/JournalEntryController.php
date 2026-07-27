<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceJournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function index()
    {
        $entries = FinanceJournalEntry::query()
            ->with('lines.account')
            ->latest()
            ->paginate(20);

        return view('admin.accounting.journal.index', [
            'entries' => $entries,
        ]);
    }

    public function create()
    {
        $accounts = FinanceAccount::query()->where('is_active', true)->orderBy('code')->get();

        return view('admin.accounting.journal.create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request, AccountingService $accounting)
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'max:2000'],
            'account_id' => ['required', 'array', 'min:2'],
            'account_id.*' => ['nullable', 'exists:finance_accounts,id'],
            'debit' => ['required', 'array'],
            'debit.*' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['required', 'array'],
            'credit.*' => ['nullable', 'numeric', 'min:0'],
            'line_description' => ['nullable', 'array'],
            'line_description.*' => ['nullable', 'max:255'],
        ]);

        $lines = [];
        foreach ($data['account_id'] as $i => $accountId) {
            $debit = (float) ($data['debit'][$i] ?? 0);
            $credit = (float) ($data['credit'][$i] ?? 0);

            if (empty($accountId) && $debit == 0.0 && $credit == 0.0) {
                continue;
            }

            if (empty($accountId)) {
                return back()->withInput()->with('error', 'يجب اختيار حساب لكل سطر يحتوي على مبلغ.');
            }

            if ($debit > 0 && $credit > 0) {
                return back()->withInput()->with('error', 'لا يمكن أن يحتوي نفس السطر على مدين ودائن معاً.');
            }

            if ($debit == 0.0 && $credit == 0.0) {
                continue;
            }

            $lines[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'line_description' => $data['line_description'][$i] ?? null,
            ];
        }

        if (count($lines) < 2) {
            return back()->withInput()->with('error', 'لا بد من إدخال طرفي القيد على الأقل.');
        }

        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->with('error', 'القيد غير متوازن: إجمالي المدين يجب أن يساوي إجمالي الدائن.');
        }

        $accounting->createJournalEntry(
            [
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
                'created_by' => optional($request->user())->id,
            ],
            $lines
        );

        return redirect()->route('admin.accounting.journal.index')->with('success', 'تم تسجيل القيد اليومي.');
    }
}
