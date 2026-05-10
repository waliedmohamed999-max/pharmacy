<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\StoreSetting;
use App\Services\AccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function ledger(Request $request)
    {
        $filters = $this->filters($request);
        $accounts = FinanceAccount::query()->orderBy('code')->get();
        $lines = $this->ledgerQuery($filters)->paginate(50)->withQueryString();

        return view('admin.accounting.reports.ledger', compact('accounts', 'lines', 'filters'));
    }

    public function trialBalance(Request $request)
    {
        $filters = $this->filters($request);
        $accounts = FinanceAccount::query()->orderBy('code')->get();
        $rows = $this->trialBalanceQuery($filters)->paginate(50)->withQueryString();
        $totals = [
            'debit' => (float) collect($rows->items())->sum('total_debit'),
            'credit' => (float) collect($rows->items())->sum('total_credit'),
        ];

        return view('admin.accounting.reports.trial-balance', compact('accounts', 'rows', 'filters', 'totals'));
    }

    public function incomeStatement(Request $request)
    {
        $filters = $this->filters($request);

        $revenues = DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', 'revenue')
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('a.code, a.name, COALESCE(SUM(l.credit - l.debit),0) as amount')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->get();

        $expenses = DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', 'expense')
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('a.code, a.name, COALESCE(SUM(l.debit - l.credit),0) as amount')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->get();

        $totalRevenue = (float) $revenues->sum('amount');
        $totalExpense = (float) $expenses->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        return view('admin.accounting.reports.income-statement', compact(
            'filters',
            'revenues',
            'expenses',
            'totalRevenue',
            'totalExpense',
            'netProfit'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $filters = $this->filters($request);

        $assets = $this->statementBalanceByType('asset', $filters['date_to']);
        $liabilities = $this->statementBalanceByType('liability', $filters['date_to']);
        $equity = $this->statementBalanceByType('equity', $filters['date_to']);

        $assetTotal = (float) $assets->sum('balance');
        $liabilityTotal = (float) $liabilities->sum('balance');
        $equityTotal = (float) $equity->sum('balance');
        $liabilitiesAndEquity = $liabilityTotal + $equityTotal;

        return view('admin.accounting.reports.balance-sheet', compact(
            'filters',
            'assets',
            'liabilities',
            'equity',
            'assetTotal',
            'liabilityTotal',
            'equityTotal',
            'liabilitiesAndEquity'
        ));
    }

    public function cashFlow(Request $request)
    {
        $filters = $this->filters($request);
        $cashAccounts = [
            AccountingService::ACCOUNT_CASH,
            AccountingService::ACCOUNT_BANK,
        ];

        $rows = DB::table('finance_journal_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->whereIn('a.code', $cashAccounts)
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('e.entry_date, e.number, e.description, a.code, a.name, l.debit, l.credit')
            ->orderBy('e.entry_date')
            ->orderBy('l.id')
            ->get();

        $cashIn = (float) $rows->sum('debit');
        $cashOut = (float) $rows->sum('credit');
        $netCash = $cashIn - $cashOut;

        return view('admin.accounting.reports.cash-flow', compact('filters', 'rows', 'cashIn', 'cashOut', 'netCash'));
    }

    public function ledgerExcel(Request $request): StreamedResponse
    {
        $rows = $this->ledgerQuery($this->filters($request))->get();

        return $this->htmlTableDownload(
            'ledger-' . now()->format('Ymd-His') . '.xls',
            ['Date', 'Entry', 'Account Code', 'Account', 'Contact', 'Description', 'Debit', 'Credit'],
            $rows->map(fn ($row) => [
                $row->entry_date,
                $row->entry_number,
                $row->account_code,
                $row->account_name,
                $row->contact_name ?? '',
                $row->line_description ?? '',
                number_format((float) $row->debit, 2, '.', ''),
                number_format((float) $row->credit, 2, '.', ''),
            ])->all()
        );
    }

    public function trialBalanceExcel(Request $request): StreamedResponse
    {
        $rows = $this->trialBalanceQuery($this->filters($request))->get();

        return $this->htmlTableDownload(
            'trial-balance-' . now()->format('Ymd-His') . '.xls',
            ['Code', 'Account', 'Type', 'Debit', 'Credit', 'Net'],
            $rows->map(function ($row) {
                $net = (float) $row->total_debit - (float) $row->total_credit;
                return [
                    $row->code,
                    $row->name,
                    $row->type,
                    number_format((float) $row->total_debit, 2, '.', ''),
                    number_format((float) $row->total_credit, 2, '.', ''),
                    number_format($net, 2, '.', ''),
                ];
            })->all()
        );
    }

    public function incomeStatementExcel(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $revenues = $this->incomeRows('revenue', $filters);
        $expenses = $this->incomeRows('expense', $filters);

        $rows = [];
        $rows[] = ['Revenues', '', ''];
        foreach ($revenues as $r) {
            $rows[] = [$r->code, $r->name, number_format((float) $r->amount, 2, '.', '')];
        }
        $rows[] = ['Expenses', '', ''];
        foreach ($expenses as $e) {
            $rows[] = [$e->code, $e->name, number_format((float) $e->amount, 2, '.', '')];
        }

        return $this->htmlTableDownload(
            'income-statement-' . now()->format('Ymd-His') . '.xls',
            ['Code', 'Name', 'Amount'],
            $rows
        );
    }

    public function balanceSheetExcel(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $assets = $this->statementBalanceByType('asset', $filters['date_to']);
        $liabilities = $this->statementBalanceByType('liability', $filters['date_to']);
        $equity = $this->statementBalanceByType('equity', $filters['date_to']);

        $rows = [];
        $rows[] = ['Assets', '', ''];
        foreach ($assets as $r) {
            $rows[] = [$r->code, $r->name, number_format((float) $r->balance, 2, '.', '')];
        }
        $rows[] = ['Liabilities', '', ''];
        foreach ($liabilities as $r) {
            $rows[] = [$r->code, $r->name, number_format((float) $r->balance, 2, '.', '')];
        }
        $rows[] = ['Equity', '', ''];
        foreach ($equity as $r) {
            $rows[] = [$r->code, $r->name, number_format((float) $r->balance, 2, '.', '')];
        }

        return $this->htmlTableDownload(
            'balance-sheet-' . now()->format('Ymd-His') . '.xls',
            ['Code', 'Name', 'Balance'],
            $rows
        );
    }

    public function cashFlowExcel(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $cashAccounts = [AccountingService::ACCOUNT_CASH, AccountingService::ACCOUNT_BANK];
        $rows = DB::table('finance_journal_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->whereIn('a.code', $cashAccounts)
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('e.entry_date, e.number, e.description, a.code, a.name, l.debit, l.credit')
            ->orderBy('e.entry_date')
            ->orderBy('l.id')
            ->get();

        return $this->htmlTableDownload(
            'cash-flow-' . now()->format('Ymd-His') . '.xls',
            ['Date', 'Entry', 'Description', 'Account Code', 'Account', 'Cash In', 'Cash Out'],
            $rows->map(fn ($r) => [
                $r->entry_date,
                $r->number,
                $r->description,
                $r->code,
                $r->name,
                number_format((float) $r->debit, 2, '.', ''),
                number_format((float) $r->credit, 2, '.', ''),
            ])->all()
        );
    }

    public function ledgerPdf(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->ledgerQuery($filters)->get();
        $branding = $this->pdfBranding();
        $reportTitle = 'دفتر الأستاذ';
        $generatedAt = now()->format('Y-m-d H:i');
        return Pdf::loadView('admin.accounting.reports.ledger-pdf', compact('rows', 'filters', 'branding', 'reportTitle', 'generatedAt'))
            ->setPaper('a4', 'landscape')
            ->download('ledger-' . now()->format('Ymd-His') . '.pdf');
    }

    public function trialBalancePdf(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->trialBalanceQuery($filters)->get();
        $branding = $this->pdfBranding();
        $reportTitle = 'ميزان المراجعة';
        $generatedAt = now()->format('Y-m-d H:i');
        return Pdf::loadView('admin.accounting.reports.trial-balance-pdf', compact('rows', 'filters', 'branding', 'reportTitle', 'generatedAt'))
            ->setPaper('a4', 'portrait')
            ->download('trial-balance-' . now()->format('Ymd-His') . '.pdf');
    }

    public function incomeStatementPdf(Request $request)
    {
        $filters = $this->filters($request);
        $revenues = $this->incomeRows('revenue', $filters);
        $expenses = $this->incomeRows('expense', $filters);
        $totalRevenue = (float) $revenues->sum('amount');
        $totalExpense = (float) $expenses->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;
        $branding = $this->pdfBranding();
        $reportTitle = 'قائمة الدخل';
        $generatedAt = now()->format('Y-m-d H:i');

        return Pdf::loadView('admin.accounting.reports.income-statement-pdf', compact(
            'filters',
            'revenues',
            'expenses',
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'branding',
            'reportTitle',
            'generatedAt'
        ))->download('income-statement-' . now()->format('Ymd-His') . '.pdf');
    }

    public function balanceSheetPdf(Request $request)
    {
        $filters = $this->filters($request);
        $assets = $this->statementBalanceByType('asset', $filters['date_to']);
        $liabilities = $this->statementBalanceByType('liability', $filters['date_to']);
        $equity = $this->statementBalanceByType('equity', $filters['date_to']);
        $assetTotal = (float) $assets->sum('balance');
        $liabilityTotal = (float) $liabilities->sum('balance');
        $equityTotal = (float) $equity->sum('balance');
        $liabilitiesAndEquity = $liabilityTotal + $equityTotal;
        $branding = $this->pdfBranding();
        $reportTitle = 'المركز المالي';
        $generatedAt = now()->format('Y-m-d H:i');

        return Pdf::loadView('admin.accounting.reports.balance-sheet-pdf', compact(
            'filters',
            'assets',
            'liabilities',
            'equity',
            'assetTotal',
            'liabilityTotal',
            'equityTotal',
            'liabilitiesAndEquity',
            'branding',
            'reportTitle',
            'generatedAt'
        ))->download('balance-sheet-' . now()->format('Ymd-His') . '.pdf');
    }

    public function cashFlowPdf(Request $request)
    {
        $filters = $this->filters($request);
        $cashAccounts = [AccountingService::ACCOUNT_CASH, AccountingService::ACCOUNT_BANK];
        $rows = DB::table('finance_journal_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->whereIn('a.code', $cashAccounts)
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('e.entry_date, e.number, e.description, a.code, a.name, l.debit, l.credit')
            ->orderBy('e.entry_date')
            ->orderBy('l.id')
            ->get();
        $cashIn = (float) $rows->sum('debit');
        $cashOut = (float) $rows->sum('credit');
        $netCash = $cashIn - $cashOut;
        $branding = $this->pdfBranding();
        $reportTitle = 'التدفقات النقدية';
        $generatedAt = now()->format('Y-m-d H:i');

        return Pdf::loadView('admin.accounting.reports.cash-flow-pdf', compact(
            'filters',
            'rows',
            'cashIn',
            'cashOut',
            'netCash',
            'branding',
            'reportTitle',
            'generatedAt'
        ))->download('cash-flow-' . now()->format('Ymd-His') . '.pdf');
    }

    private function ledgerQuery(array $filters)
    {
        return DB::table('finance_journal_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->leftJoin('finance_contacts as c', 'c.id', '=', 'l.contact_id')
            ->when($filters['account_id'], fn ($q) => $q->where('l.account_id', $filters['account_id']))
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('e.entry_date, e.number as entry_number, e.description as entry_description, a.code as account_code, a.name as account_name, c.name as contact_name, l.line_description, l.debit, l.credit')
            ->orderBy('e.entry_date')
            ->orderBy('l.id');
    }

    private function trialBalanceQuery(array $filters)
    {
        return DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->when($filters['account_id'], fn ($q) => $q->where('a.id', $filters['account_id']))
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw('a.id, a.code, a.name, a.type, COALESCE(SUM(l.debit),0) as total_debit, COALESCE(SUM(l.credit),0) as total_credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code');
    }

    private function incomeRows(string $type, array $filters)
    {
        $expr = $type === 'revenue' ? 'COALESCE(SUM(l.credit - l.debit),0)' : 'COALESCE(SUM(l.debit - l.credit),0)';
        return DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', $type)
            ->when($filters['date_from'], fn ($q) => $q->whereDate('e.entry_date', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('e.entry_date', '<=', $filters['date_to']))
            ->selectRaw("a.code, a.name, {$expr} as amount")
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->get();
    }

    private function statementBalanceByType(string $type, ?string $toDate)
    {
        $expr = match ($type) {
            'asset', 'expense' => 'COALESCE(SUM(l.debit - l.credit),0)',
            default => 'COALESCE(SUM(l.credit - l.debit),0)',
        };

        return DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', $type)
            ->when($toDate, fn ($q) => $q->whereDate('e.entry_date', '<=', $toDate))
            ->selectRaw("a.code, a.name, {$expr} as balance")
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->get();
    }

    private function filters(Request $request): array
    {
        return [
            'account_id' => $request->integer('account_id') ?: null,
            'date_from' => $request->string('date_from')->value() ?: null,
            'date_to' => $request->string('date_to')->value() ?: null,
        ];
    }

    private function htmlTableDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            echo '<table border="1"><thead><tr>';
            foreach ($headers as $h) {
                echo '<th>' . e($h) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . e((string) $cell) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function pdfBranding(): array
    {
        $companyName = StoreSetting::getValue('footer_brand_title', config('app.name'));
        $contactPhone = StoreSetting::getValue('footer_contact_phone');
        $contactEmail = StoreSetting::getValue('footer_contact_email');
        $logoDataUri = $this->loadLogoDataUri();

        return [
            'company_name' => $companyName ?: config('app.name'),
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
            'logo_data_uri' => $logoDataUri,
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
