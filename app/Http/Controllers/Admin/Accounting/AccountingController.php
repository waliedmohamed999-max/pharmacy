<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceJournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index()
    {
        $salesTotal = (float) SalesInvoice::query()->sum('total');
        $purchasesTotal = (float) PurchaseInvoice::query()->sum('total');
        $receivables = (float) SalesInvoice::query()->sum('balance');
        $payables = (float) PurchaseInvoice::query()->sum('balance');

        $trialBalance = DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->selectRaw('a.id, a.code, a.name, a.type, COALESCE(SUM(l.debit),0) as total_debit, COALESCE(SUM(l.credit),0) as total_credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->limit(20)
            ->get();

        $latestEntries = FinanceJournalEntry::query()
            ->with('lines.account')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.accounting.index', [
            'salesTotal' => $salesTotal,
            'purchasesTotal' => $purchasesTotal,
            'receivables' => $receivables,
            'payables' => $payables,
            'trialBalance' => $trialBalance,
            'latestEntries' => $latestEntries,
        ]);
    }
}
