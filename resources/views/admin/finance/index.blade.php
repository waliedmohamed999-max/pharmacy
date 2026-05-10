@extends('admin.layouts.app')

@section('page-title', 'المركز المالي')
@section('page-subtitle', 'نظام مالي متكامل للمحاسب: خزائن، ذمم، ضرائب، قيود، فواتير، ربحية وتقارير')

@section('page-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.sales.create') }}" class="btn-primary">فاتورة مبيعات</a>
    <a href="{{ route('admin.accounting.purchases.create') }}" class="btn-secondary">فاتورة مشتريات</a>
    <a href="{{ route('admin.accounting.payments.create') }}" class="btn-secondary">تحصيل / سداد</a>
    <a href="{{ route('admin.accounting.journal.create') }}" class="btn-secondary">قيد يومي</a>
</div>
@endsection

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2) . ' ج.م';
    $percent = $salesTotal > 0 ? ($grossProfit / max(1, $salesTotal)) * 100 : 0;
@endphp

<form class="card-premium mb-4 grid gap-3 p-4 lg:grid-cols-6">
    <div>
        <label class="mb-1 block text-xs font-black text-slate-500">رقم طلب المتجر</label>
        <input type="number" min="1" name="invoice_id" value="{{ $invoiceId }}" placeholder="مثال: 1203" class="input-premium">
    </div>
    <div>
        <label class="mb-1 block text-xs font-black text-slate-500">حالة الطلب</label>
        <select name="status" class="select-premium">
            <option value="">كل الحالات</option>
            @foreach($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-black text-slate-500">المنتج</label>
        <select name="product_id" class="select-premium">
            <option value="">كل المنتجات</option>
            @if($selectedProduct)
                <option value="{{ $selectedProduct->id }}" selected>{{ $selectedProduct->name }}</option>
            @endif
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((int) $productId === (int) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-black text-slate-500">من تاريخ</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-premium">
    </div>
    <div>
        <label class="mb-1 block text-xs font-black text-slate-500">إلى تاريخ</label>
        <input type="date" name="date_to" value="{{ $dateTo }}" class="input-premium">
    </div>
    <div class="flex items-end gap-2">
        <button class="btn-primary flex-1">تطبيق</button>
        <a href="{{ route('admin.finance.index') }}" class="btn-secondary flex-1 text-center">إعادة</a>
    </div>
</form>

<section class="card-premium mb-5 overflow-hidden p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-sm font-black text-emerald-600">Accounting Command Center</div>
            <h2 class="text-2xl font-black text-slate-950">لوحة المحاسب التشغيلية</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">كل أدوات المحاسب الأساسية في مكان واحد، والبيانات مرتبطة بالفواتير والقيود والمخزون.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.finance.export', array_merge(request()->query(), ['type' => 'invoices'])) }}" class="btn-secondary">تصدير الطلبات CSV</a>
            <a href="{{ route('admin.finance.export', array_merge(request()->query(), ['type' => 'payments'])) }}" class="btn-secondary">تصدير المدفوعات CSV</a>
            <a href="{{ route('admin.finance.export', array_merge(request()->query(), ['type' => 'accounts'])) }}" class="btn-secondary">تصدير الحسابات CSV</a>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($accountantTools as $tool)
            <a href="{{ $tool['route'] }}" class="rounded-3xl border border-slate-200 bg-white/80 p-4 transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl">
                <div class="font-black text-slate-900">{{ $tool['label'] }}</div>
                <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $tool['desc'] }}</div>
            </a>
        @endforeach
    </div>
</section>

<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="kpi-card">
        <div class="text-sm text-slate-500">إجمالي المبيعات المحاسبية</div>
        <div class="kpi-value">{{ $money($salesTotal) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">ضريبة مبيعات: {{ $money($salesTax) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">إجمالي المشتريات</div>
        <div class="kpi-value">{{ $money($purchaseTotal) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">ضريبة مشتريات: {{ $money($purchaseTax) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">النقدية والبنوك</div>
        <div class="kpi-value">{{ $money($cashBalance) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">تحصيل: {{ $money($collections) }} | سداد: {{ $money($disbursements) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">صافي الربح المحاسبي</div>
        <div class="kpi-value {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $money($netProfit) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">هامش إجمالي تقريبي: {{ number_format($percent, 1) }}%</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">العملاء المدينون</div>
        <div class="kpi-value">{{ $money($receivables) }}</div>
        <div class="mt-2 text-xs font-bold text-rose-600">متأخر: {{ $overdueSalesCount }} / {{ $money($overdueSalesAmount) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">الموردون الدائنون</div>
        <div class="kpi-value">{{ $money($payables) }}</div>
        <div class="mt-2 text-xs font-bold text-amber-700">مستحق متأخر: {{ $overduePurchasesCount }} / {{ $money($overduePurchasesAmount) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">قيمة المخزون</div>
        <div class="kpi-value">{{ $money($inventoryValue) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">تكلفة البضاعة المباعة: {{ $money($cogs) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">صافي الضريبة</div>
        <div class="kpi-value {{ $taxDue >= 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $money(abs($taxDue)) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">{{ $taxDue >= 0 ? 'مستحق للسداد' : 'رصيد لصالح الصيدلية' }}</div>
    </div>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
    <section class="card-premium p-4 xl:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black">آخر طلبات المتجر</h2>
                <p class="text-sm font-semibold text-slate-500">متوسط طلب المتجر: {{ $money($averageInvoice) }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn-secondary">كل الطلبات</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                        <th>البنود</th>
                        <th>التاريخ</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>#{{ $invoice->id }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td><span class="badge-status-{{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></td>
                        <td>{{ $money($invoice->total) }}</td>
                        <td>{{ $invoice->items_count }}</td>
                        <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.orders.show', $invoice) }}" class="btn-secondary">عرض</a>
                                <a href="{{ route('admin.finance.index', ['invoice_id' => $invoice->id]) }}" class="btn-secondary">تحليل</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">لا توجد طلبات تطابق الفلاتر الحالية.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </section>

    <section class="card-premium p-4">
        <h2 class="mb-3 text-xl font-black">مراقبة مالية سريعة</h2>
        <div class="space-y-3">
            <div class="rounded-2xl bg-rose-50 p-3">
                <div class="text-sm font-black text-rose-700">مديونيات عملاء متأخرة</div>
                <div class="mt-1 text-2xl font-black text-rose-700">{{ $money($overdueSalesAmount) }}</div>
                <div class="text-xs font-bold text-rose-500">{{ $overdueSalesCount }} فاتورة تحتاج متابعة</div>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3">
                <div class="text-sm font-black text-amber-700">التزامات موردين متأخرة</div>
                <div class="mt-1 text-2xl font-black text-amber-700">{{ $money($overduePurchasesAmount) }}</div>
                <div class="text-xs font-bold text-amber-600">{{ $overduePurchasesCount }} فاتورة مستحقة</div>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-3">
                <div class="text-sm font-black text-emerald-700">الربح الإجمالي</div>
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ $money($grossProfit) }}</div>
                <div class="text-xs font-bold text-emerald-600">مبيعات صافية ناقص تكلفة البضاعة</div>
            </div>
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أعمار ديون العملاء</h2>
            <a href="{{ route('admin.accounting.sales.index') }}" class="btn-secondary">فواتير البيع</a>
        </div>
        <div class="grid gap-2 sm:grid-cols-5">
            @foreach($salesAging as $bucket)
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="text-xs font-black text-slate-500">{{ $bucket['label'] }}</div>
                    <div class="mt-2 text-lg font-black">{{ $money($bucket['amount']) }}</div>
                    <div class="text-xs font-bold text-slate-400">{{ $bucket['count'] }} فاتورة</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أعمار التزامات الموردين</h2>
            <a href="{{ route('admin.accounting.purchases.index') }}" class="btn-secondary">فواتير الشراء</a>
        </div>
        <div class="grid gap-2 sm:grid-cols-5">
            @foreach($purchaseAging as $bucket)
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="text-xs font-black text-slate-500">{{ $bucket['label'] }}</div>
                    <div class="mt-2 text-lg font-black">{{ $money($bucket['amount']) }}</div>
                    <div class="text-xs font-bold text-slate-400">{{ $bucket['count'] }} فاتورة</div>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
    <section class="card-premium p-4">
        <h2 class="mb-3 text-xl font-black">الخزائن والبنوك</h2>
        <div class="space-y-2">
            @forelse($cashAccounts as $account)
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-3">
                    <div>
                        <div class="font-black">{{ $account->name }}</div>
                        <div class="text-xs font-bold text-slate-400">{{ $account->code }}</div>
                    </div>
                    <div class="font-black text-emerald-700">{{ $money($account->balance) }}</div>
                </div>
            @empty
                <div class="empty-state !p-6">لا توجد حسابات خزينة أو بنك معرفة.</div>
            @endforelse
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="mb-3 text-xl font-black">أكبر المصروفات</h2>
        <div class="space-y-2">
            @forelse($topExpenseAccounts as $expense)
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-3">
                    <div>
                        <div class="font-black">{{ $expense->name }}</div>
                        <div class="text-xs font-bold text-slate-400">{{ $expense->code }}</div>
                    </div>
                    <div class="font-black text-rose-700">{{ $money($expense->amount) }}</div>
                </div>
            @empty
                <div class="empty-state !p-6">لا توجد مصروفات مرحّلة في الفترة.</div>
            @endforelse
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="mb-3 text-xl font-black">آخر 6 أشهر</h2>
        <div class="space-y-2">
            @forelse($monthlyStats as $month)
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-black">{{ $month['label'] }}</span>
                        <span class="text-slate-500">{{ $month['invoices_count'] }} طلب</span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-slate-500">
                        <span>إجمالي: {{ $money($month['gross_total']) }}</span>
                        <span>صافي: {{ $money($month['net_total']) }}</span>
                    </div>
                </div>
            @empty
                <div class="empty-state !p-6">لا توجد بيانات شهرية حتى الآن.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أرصدة الحسابات</h2>
            <a href="{{ route('admin.accounting.reports.trial-balance') }}" class="btn-secondary">ميزان المراجعة</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>الحساب</th>
                        <th>النوع</th>
                        <th>مدين</th>
                        <th>دائن</th>
                        <th>الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accountBalances as $account)
                    <tr>
                        <td>{{ $account->code }}</td>
                        <td>{{ $account->name }}</td>
                        <td>{{ $account->type }}</td>
                        <td>{{ $money($account->debit) }}</td>
                        <td>{{ $money($account->credit) }}</td>
                        <td class="font-black">{{ $money($account->balance) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">لا توجد حسابات بعد.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">آخر القيود اليومية</h2>
            <a href="{{ route('admin.accounting.journal.index') }}" class="btn-secondary">كل القيود</a>
        </div>
        <div class="space-y-2">
            @forelse($latestEntries as $entry)
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-black">{{ $entry->number }}</span>
                        <span class="text-slate-500">{{ optional($entry->entry_date)->format('Y-m-d') }}</span>
                    </div>
                    <div class="text-sm font-semibold text-slate-600">{{ $entry->description ?: 'بدون وصف' }}</div>
                </div>
            @empty
                <div class="empty-state">لا توجد قيود بعد.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أعلى مديونيات العملاء</h2>
            <a href="{{ route('admin.accounting.contacts.index') }}" class="btn-secondary">العملاء</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الفاتورة</th><th>العميل</th><th>الاستحقاق</th><th>الرصيد</th></tr></thead>
                <tbody>
                @forelse($unpaidSales as $invoice)
                    <tr>
                        <td>{{ $invoice->number }}</td>
                        <td>{{ $invoice->contact?->name }}</td>
                        <td>{{ optional($invoice->due_date)->format('Y-m-d') ?: '-' }}</td>
                        <td class="font-black text-rose-700">{{ $money($invoice->balance) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد مديونيات عملاء.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أعلى التزامات الموردين</h2>
            <a href="{{ route('admin.accounting.contacts.index') }}" class="btn-secondary">الموردون</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الفاتورة</th><th>المورد</th><th>الاستحقاق</th><th>الرصيد</th></tr></thead>
                <tbody>
                @forelse($unpaidPurchases as $invoice)
                    <tr>
                        <td>{{ $invoice->number }}</td>
                        <td>{{ $invoice->contact?->name }}</td>
                        <td>{{ optional($invoice->due_date)->format('Y-m-d') ?: '-' }}</td>
                        <td class="font-black text-amber-700">{{ $money($invoice->balance) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد التزامات موردين.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card-premium p-4">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-xl font-black">ربحية المنتجات من طلبات المتجر</h2>
        <span class="text-sm font-semibold text-slate-500">ربط مباشر مع بنود الطلبات</span>
    </div>
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية المباعة</th>
                    <th>عدد الطلبات</th>
                    <th>الإيراد</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
            @forelse($topProducts as $row)
                <tr>
                    <td class="font-semibold">{{ $row->product_name }}</td>
                    <td>{{ number_format($row->qty_sold) }}</td>
                    <td>{{ number_format($row->invoices_count) }}</td>
                    <td>{{ $money($row->revenue) }}</td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.finance.index', ['product_id' => $row->product_id]) }}" class="btn-secondary">تحليل</a>
                            @if($row->product_id)
                                <a href="{{ route('admin.products.edit', $row->product_id) }}" class="btn-secondary">المنتج</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state">لا توجد بيانات مبيعات للمنتجات حسب الفلاتر.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
