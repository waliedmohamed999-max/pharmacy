<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceJournalEntry;
use App\Models\FinancePayment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        ['invoiceId' => $invoiceId, 'productId' => $productId, 'status' => $status, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo] = $this->filtersFromRequest($request);

        $statusLabels = $this->statusLabels();
        $ordersQuery = $this->applyOrderFilters(Order::query(), $invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels);

        $invoiceCount = (clone $ordersQuery)->count();
        $grossRevenue = (float) (clone $ordersQuery)->sum('total');
        $completedRevenue = (float) (clone $ordersQuery)->where('status', 'completed')->sum('total');
        $cancelledRevenue = (float) (clone $ordersQuery)->where('status', 'cancelled')->sum('total');
        $pendingRevenue = (float) (clone $ordersQuery)->whereIn('status', ['new', 'preparing', 'shipped'])->sum('total');
        $netRevenue = $grossRevenue - $cancelledRevenue;
        $averageInvoice = $invoiceCount > 0 ? $grossRevenue / $invoiceCount : 0;

        $invoices = (clone $ordersQuery)
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $topProducts = $this->applyOrderItemFilters(
            OrderItem::query(),
            $invoiceId,
            $productId,
            $status,
            $dateFrom,
            $dateTo,
            $statusLabels
        )
            ->selectRaw('order_items.product_id')
            ->selectRaw('MAX(order_items.product_name_snapshot) as product_name')
            ->selectRaw('SUM(order_items.qty) as qty_sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as invoices_count')
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->limit(12)
            ->get();

        $monthlyStats = (clone $ordersQuery)
            ->selectRaw($this->monthKeyExpression('created_at') . ' as month_key')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(total) as gross_total')
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END) as cancelled_total")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as completed_total")
            ->groupBy('month_key')
            ->orderByDesc('month_key')
            ->limit(6)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($row) => [
                'label' => date('m/Y', strtotime($row->month_key . '-01')),
                'invoices_count' => (int) $row->invoices_count,
                'gross_total' => (float) $row->gross_total,
                'cancelled_total' => (float) $row->cancelled_total,
                'completed_total' => (float) $row->completed_total,
                'net_total' => (float) $row->gross_total - (float) $row->cancelled_total,
            ]);

        $products = Product::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $selectedProduct = $productId ? Product::query()->select('id', 'name')->find($productId) : null;

        return view('admin.finance.index', array_merge([
            'statusLabels' => $statusLabels,
            'invoiceId' => $invoiceId,
            'productId' => $productId,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedProduct' => $selectedProduct,
            'products' => $products,
            'invoices' => $invoices,
            'topProducts' => $topProducts,
            'monthlyStats' => $monthlyStats,
            'invoiceCount' => $invoiceCount,
            'grossRevenue' => $grossRevenue,
            'completedRevenue' => $completedRevenue,
            'pendingRevenue' => $pendingRevenue,
            'cancelledRevenue' => $cancelledRevenue,
            'netRevenue' => $netRevenue,
            'averageInvoice' => $averageInvoice,
        ], $this->accountingSnapshot($dateFrom, $dateTo)));
    }

    public function export(Request $request): StreamedResponse
    {
        $statusLabels = $this->statusLabels();
        ['invoiceId' => $invoiceId, 'productId' => $productId, 'status' => $status, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo] = $this->filtersFromRequest($request);
        $type = $request->string('type')->value() ?: 'invoices';

        return match ($type) {
            'products' => $this->exportProductsCsv($invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels),
            'monthly' => $this->exportMonthlyCsv($invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels),
            'payments' => $this->exportPaymentsCsv($dateFrom, $dateTo),
            'accounts' => $this->exportAccountsCsv($dateFrom, $dateTo),
            default => $this->exportInvoicesCsv($invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels),
        };
    }

    private function exportInvoicesCsv(?int $invoiceId, ?int $productId, ?string $status, ?string $dateFrom, ?string $dateTo, array $statusLabels): StreamedResponse
    {
        $orders = $this->applyOrderFilters(Order::query(), $invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels)
            ->withCount('items')
            ->latest()
            ->get();

        $filename = 'finance-invoices-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($orders, $statusLabels) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Invoice ID', 'Customer', 'Phone', 'Status', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Created At']);

            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->id,
                    $order->customer_name,
                    $order->phone,
                    $statusLabels[$order->status] ?? $order->status,
                    $order->items_count,
                    (float) $order->subtotal,
                    (float) $order->discount,
                    (float) $order->shipping,
                    (float) $order->total,
                    optional($order->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportProductsCsv(?int $invoiceId, ?int $productId, ?string $status, ?string $dateFrom, ?string $dateTo, array $statusLabels): StreamedResponse
    {
        $rows = $this->applyOrderItemFilters(OrderItem::query(), $invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels)
            ->selectRaw('order_items.product_id')
            ->selectRaw('MAX(order_items.product_name_snapshot) as product_name')
            ->selectRaw('SUM(order_items.qty) as qty_sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as invoices_count')
            ->groupBy('order_items.product_id')
            ->orderByDesc('revenue')
            ->get();

        $filename = 'finance-products-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Product ID', 'Product Name', 'Qty Sold', 'Invoices Count', 'Revenue']);

            foreach ($rows as $row) {
                fputcsv($output, [$row->product_id, $row->product_name, (int) $row->qty_sold, (int) $row->invoices_count, (float) $row->revenue]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportMonthlyCsv(?int $invoiceId, ?int $productId, ?string $status, ?string $dateFrom, ?string $dateTo, array $statusLabels): StreamedResponse
    {
        $rows = $this->applyOrderFilters(Order::query(), $invoiceId, $productId, $status, $dateFrom, $dateTo, $statusLabels)
            ->selectRaw($this->monthKeyExpression('created_at') . ' as month_key')
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw('SUM(total) as gross_total')
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END) as cancelled_total")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as completed_total")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $filename = 'finance-monthly-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Month', 'Invoices Count', 'Gross Total', 'Cancelled Total', 'Completed Total', 'Net Total']);

            foreach ($rows as $row) {
                $gross = (float) $row->gross_total;
                $cancelled = (float) $row->cancelled_total;
                fputcsv($output, [$row->month_key, (int) $row->invoices_count, $gross, $cancelled, (float) $row->completed_total, $gross - $cancelled]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportPaymentsCsv(?string $dateFrom, ?string $dateTo): StreamedResponse
    {
        $payments = $this->dateFilter(FinancePayment::query(), 'payment_date', $dateFrom, $dateTo)
            ->with(['contact', 'account'])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($payments) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Number', 'Date', 'Direction', 'Contact', 'Account', 'Method', 'Amount', 'Reference', 'Notes']);

            foreach ($payments as $payment) {
                fputcsv($output, [
                    $payment->number,
                    optional($payment->payment_date)->format('Y-m-d'),
                    $payment->direction,
                    $payment->contact?->name,
                    trim(($payment->account?->code ?? '') . ' ' . ($payment->account?->name ?? '')),
                    $payment->method,
                    (float) $payment->amount,
                    trim(($payment->reference_type ?? '') . ' #' . ($payment->reference_id ?? '')),
                    $payment->notes,
                ]);
            }

            fclose($output);
        }, 'finance-payments-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportAccountsCsv(?string $dateFrom, ?string $dateTo): StreamedResponse
    {
        $rows = $this->accountBalances($dateFrom, $dateTo);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Code', 'Account', 'Type', 'Debit', 'Credit', 'Balance']);

            foreach ($rows as $row) {
                fputcsv($output, [$row->code, $row->name, $row->type, (float) $row->debit, (float) $row->credit, (float) $row->balance]);
            }

            fclose($output);
        }, 'finance-accounts-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function accountingSnapshot(?string $dateFrom, ?string $dateTo): array
    {
        $salesBase = $this->dateFilter(SalesInvoice::query(), 'invoice_date', $dateFrom, $dateTo);
        $purchaseBase = $this->dateFilter(PurchaseInvoice::query(), 'invoice_date', $dateFrom, $dateTo);
        $paymentBase = $this->dateFilter(FinancePayment::query(), 'payment_date', $dateFrom, $dateTo);

        $salesTotal = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('total');
        $salesSubtotal = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('subtotal');
        $salesDiscount = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('discount');
        $salesTax = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('tax');
        $salesPaid = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('paid_amount');
        $receivables = (float) (clone $salesBase)->where('status', '!=', 'cancelled')->sum('balance');

        $purchaseTotal = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('total');
        $purchaseSubtotal = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('subtotal');
        $purchaseDiscount = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('discount');
        $purchaseTax = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('tax');
        $purchasePaid = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('paid_amount');
        $payables = (float) (clone $purchaseBase)->where('status', '!=', 'cancelled')->sum('balance');

        $collections = (float) (clone $paymentBase)->where('direction', 'in')->sum('amount');
        $disbursements = (float) (clone $paymentBase)->where('direction', 'out')->sum('amount');
        $accountBalances = $this->accountBalances($dateFrom, $dateTo);
        $cashAccounts = $accountBalances->filter(fn ($row) => in_array($row->code, ['1110', '1120'], true))->values();
        $cogs = $this->accountBalanceByCode('5110', $dateFrom, $dateTo, true);
        $revenueByAccounts = $this->signedAccountTotal('revenue', $dateFrom, $dateTo);
        $expenseByAccounts = $this->signedAccountTotal('expense', $dateFrom, $dateTo);

        $overdueSales = (clone $salesBase)->where('status', '!=', 'cancelled')->where('balance', '>', 0)->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
        $overduePurchases = (clone $purchaseBase)->where('status', '!=', 'cancelled')->where('balance', '>', 0)->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());

        return [
            'salesTotal' => $salesTotal,
            'salesSubtotal' => $salesSubtotal,
            'salesDiscount' => $salesDiscount,
            'salesTax' => $salesTax,
            'salesPaid' => $salesPaid,
            'purchaseTotal' => $purchaseTotal,
            'purchaseSubtotal' => $purchaseSubtotal,
            'purchaseDiscount' => $purchaseDiscount,
            'purchaseTax' => $purchaseTax,
            'purchasePaid' => $purchasePaid,
            'receivables' => $receivables,
            'payables' => $payables,
            'collections' => $collections,
            'disbursements' => $disbursements,
            'cashBalance' => (float) $cashAccounts->sum('balance'),
            'cashAccounts' => $cashAccounts,
            'taxDue' => $salesTax - $purchaseTax,
            'inventoryValue' => $this->inventoryValue(),
            'cogs' => $cogs,
            'grossProfit' => $salesSubtotal - $salesDiscount - $cogs,
            'netProfit' => $revenueByAccounts - $expenseByAccounts,
            'revenueByAccounts' => $revenueByAccounts,
            'expenseByAccounts' => $expenseByAccounts,
            'accountBalances' => $accountBalances->take(14),
            'recentPayments' => FinancePayment::query()->with(['contact', 'account'])->latest()->limit(10)->get(),
            'latestEntries' => FinanceJournalEntry::query()->with('lines.account')->latest()->limit(8)->get(),
            'topExpenseAccounts' => $this->topExpenseAccounts($dateFrom, $dateTo),
            'unpaidSales' => (clone $salesBase)->with('contact')->where('status', '!=', 'cancelled')->where('balance', '>', 0)->orderByDesc('balance')->limit(8)->get(),
            'unpaidPurchases' => (clone $purchaseBase)->with('contact')->where('status', '!=', 'cancelled')->where('balance', '>', 0)->orderByDesc('balance')->limit(8)->get(),
            'salesAging' => $this->agingBuckets((clone $salesBase)->where('status', '!=', 'cancelled')->where('balance', '>', 0)->get(['due_date', 'invoice_date', 'balance'])),
            'purchaseAging' => $this->agingBuckets((clone $purchaseBase)->where('status', '!=', 'cancelled')->where('balance', '>', 0)->get(['due_date', 'invoice_date', 'balance'])),
            'overdueSalesCount' => (clone $overdueSales)->count(),
            'overdueSalesAmount' => (float) (clone $overdueSales)->sum('balance'),
            'overduePurchasesCount' => (clone $overduePurchases)->count(),
            'overduePurchasesAmount' => (float) (clone $overduePurchases)->sum('balance'),
            'accountantTools' => $this->accountantTools(),
        ];
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'invoiceId' => $request->integer('invoice_id') ?: null,
            'productId' => $request->integer('product_id') ?: null,
            'status' => $request->string('status')->value() ?: null,
            'dateFrom' => $this->normalizeDate($request->string('date_from')->value()),
            'dateTo' => $this->normalizeDate($request->string('date_to')->value()),
        ];
    }

    private function applyOrderFilters($query, ?int $invoiceId, ?int $productId, ?string $status, ?string $dateFrom, ?string $dateTo, array $statusLabels)
    {
        return $query
            ->when($invoiceId, fn ($q) => $q->whereKey($invoiceId))
            ->when($status && array_key_exists($status, $statusLabels), fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($productId, fn ($q) => $q->whereHas('items', fn ($items) => $items->where('product_id', $productId)));
    }

    private function applyOrderItemFilters($query, ?int $invoiceId, ?int $productId, ?string $status, ?string $dateFrom, ?string $dateTo, array $statusLabels)
    {
        return $query
            ->when($invoiceId, fn ($q) => $q->where('order_items.order_id', $invoiceId))
            ->when($productId, fn ($q) => $q->where('order_items.product_id', $productId))
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($status && array_key_exists($status, $statusLabels), fn ($q) => $q->where('orders.status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('orders.created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('orders.created_at', '<=', $dateTo));
    }

    private function dateFilter($query, string $column, ?string $dateFrom, ?string $dateTo)
    {
        return $query
            ->when($dateFrom, fn ($q) => $q->whereDate($column, '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate($column, '<=', $dateTo));
    }

    private function statusLabels(): array
    {
        return [
            'new' => 'جديد',
            'preparing' => 'جاري التحضير',
            'shipped' => 'تم الشحن',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ];
    }

    private function accountBalances(?string $dateFrom, ?string $dateTo)
    {
        return DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->when($dateFrom, fn ($q) => $q->where(fn ($inner) => $inner->whereNull('e.entry_date')->orWhereDate('e.entry_date', '>=', $dateFrom)))
            ->when($dateTo, fn ($q) => $q->where(fn ($inner) => $inner->whereNull('e.entry_date')->orWhereDate('e.entry_date', '<=', $dateTo)))
            ->selectRaw('a.id, a.code, a.name, a.type, COALESCE(SUM(l.debit),0) as debit, COALESCE(SUM(l.credit),0) as credit')
            ->selectRaw("CASE WHEN a.type IN ('asset','expense') THEN COALESCE(SUM(l.debit - l.credit),0) ELSE COALESCE(SUM(l.credit - l.debit),0) END as balance")
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();
    }

    private function signedAccountTotal(string $type, ?string $dateFrom, ?string $dateTo): float
    {
        return (float) DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', $type)
            ->when($dateFrom, fn ($q) => $q->whereDate('e.entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('e.entry_date', '<=', $dateTo))
            ->selectRaw($type === 'expense' ? 'COALESCE(SUM(l.debit - l.credit),0) as total' : 'COALESCE(SUM(l.credit - l.debit),0) as total')
            ->value('total');
    }

    private function accountBalanceByCode(string $code, ?string $dateFrom, ?string $dateTo, bool $debitNature = true): float
    {
        return (float) DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.code', $code)
            ->when($dateFrom, fn ($q) => $q->whereDate('e.entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('e.entry_date', '<=', $dateTo))
            ->selectRaw($debitNature ? 'COALESCE(SUM(l.debit - l.credit),0) as total' : 'COALESCE(SUM(l.credit - l.debit),0) as total')
            ->value('total');
    }

    private function inventoryValue(): float
    {
        $stockValue = (float) DB::table('product_stocks')->selectRaw('COALESCE(SUM(qty * avg_cost),0) as total')->value('total');

        return $stockValue > 0
            ? $stockValue
            : (float) Product::query()->selectRaw('COALESCE(SUM(quantity * avg_cost),0) as total')->value('total');
    }

    private function topExpenseAccounts(?string $dateFrom, ?string $dateTo)
    {
        return DB::table('finance_accounts as a')
            ->leftJoin('finance_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('a.type', 'expense')
            ->when($dateFrom, fn ($q) => $q->whereDate('e.entry_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('e.entry_date', '<=', $dateTo))
            ->selectRaw('a.code, a.name, COALESCE(SUM(l.debit - l.credit),0) as amount')
            ->groupBy('a.code', 'a.name')
            ->havingRaw('COALESCE(SUM(l.debit - l.credit),0) > 0')
            ->orderByDesc('amount')
            ->limit(8)
            ->get();
    }

    private function agingBuckets($invoices): array
    {
        $buckets = [
            'current' => ['label' => 'غير مستحق', 'amount' => 0, 'count' => 0],
            'd30' => ['label' => '1-30 يوم', 'amount' => 0, 'count' => 0],
            'd60' => ['label' => '31-60 يوم', 'amount' => 0, 'count' => 0],
            'd90' => ['label' => '61-90 يوم', 'amount' => 0, 'count' => 0],
            'over90' => ['label' => 'أكثر من 90 يوم', 'amount' => 0, 'count' => 0],
        ];

        foreach ($invoices as $invoice) {
            $date = $invoice->due_date ?: $invoice->invoice_date;
            $days = $date ? Carbon::parse($date)->diffInDays(now(), false) : 0;
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => 'd30',
                $days <= 60 => 'd60',
                $days <= 90 => 'd90',
                default => 'over90',
            };

            $buckets[$key]['amount'] += (float) $invoice->balance;
            $buckets[$key]['count']++;
        }

        return $buckets;
    }

    private function accountantTools(): array
    {
        return [
            ['label' => 'شجرة الحسابات', 'desc' => 'أصول، التزامات، حقوق ملكية، إيرادات ومصروفات', 'route' => route('admin.accounting.accounts.index')],
            ['label' => 'العملاء والموردون', 'desc' => 'إدارة الذمم وأرصدة الافتتاح', 'route' => route('admin.accounting.contacts.index')],
            ['label' => 'فاتورة مبيعات', 'desc' => 'إنشاء فاتورة وترحيل قيد تلقائي', 'route' => route('admin.accounting.sales.create')],
            ['label' => 'فاتورة مشتريات', 'desc' => 'إدخال مشتريات ومخزون وموردين', 'route' => route('admin.accounting.purchases.create')],
            ['label' => 'تحصيل / سداد', 'desc' => 'حركة خزينة أو بنك مرتبطة بعميل أو مورد', 'route' => route('admin.accounting.payments.create')],
            ['label' => 'قيد يومي', 'desc' => 'قيود تسوية ومصروفات ومراجعات محاسبية', 'route' => route('admin.accounting.journal.create')],
            ['label' => 'دفتر الأستاذ', 'desc' => 'كشف حساب تفصيلي مع Excel و PDF', 'route' => route('admin.accounting.reports.ledger')],
            ['label' => 'ميزان المراجعة', 'desc' => 'مطابقة إجمالي المدين والدائن', 'route' => route('admin.accounting.reports.trial-balance')],
            ['label' => 'قائمة الدخل', 'desc' => 'الإيرادات والمصروفات وصافي الربح', 'route' => route('admin.accounting.reports.income-statement')],
            ['label' => 'المركز المالي', 'desc' => 'الأصول والالتزامات وحقوق الملكية', 'route' => route('admin.accounting.reports.balance-sheet')],
            ['label' => 'التدفقات النقدية', 'desc' => 'تحصيلات ومدفوعات وصافي النقد', 'route' => route('admin.accounting.reports.cash-flow')],
        ];
    }

    private function monthKeyExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlsrv' => "FORMAT({$column}, 'yyyy-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function normalizeDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
