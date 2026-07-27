@extends('admin.layouts.app')

@section('title', 'مركز التقارير')
@section('page-title', 'مركز التقارير')
@section('page-subtitle', 'لوحة موحدة للتقارير المالية، المحاسبية، المخزون، المبيعات، العملاء والواجهة الخارجية')

@section('page-actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">لوحة التحكم</a>
        <a href="{{ route('admin.accounting.reports.income-statement') }}" class="btn-secondary">قائمة الدخل</a>
        <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn-primary">المركز المالي</a>
    </div>
@endsection

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $growth = function ($current, $previous) {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / abs($previous)) * 100;
    };
    $revenueGrowth = $growth($summary['revenue'], $previousSummary['revenue']);
    $ordersGrowth = $growth($summary['orders'], $previousSummary['orders']);
    $avgGrowth = $growth($summary['average_order'], $previousSummary['average_order']);
    $maxRevenue = max(1, (float) $monthlySales->max('revenue'));
@endphp

<section class="rounded-[28px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="grid gap-5 xl:grid-cols-[1fr_520px] xl:items-end">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Reporting Command Center</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">مركز تقارير احترافي موحد</h1>
            <p class="mt-2 max-w-3xl text-sm font-semibold leading-7 text-slate-500">
                راقب أداء الفترة، افتح التقارير التنفيذية، صدّر الملفات، وتابع مؤشرات المخزون والمبيعات من مكان واحد.
            </p>
        </div>
        <form method="GET" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_1fr_auto_auto]">
            <label class="grid gap-1">
                <span class="text-xs font-black text-slate-500">من تاريخ</span>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-premium">
            </label>
            <label class="grid gap-1">
                <span class="text-xs font-black text-slate-500">إلى تاريخ</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="input-premium">
            </label>
            <button class="btn-primary self-end" type="submit">تطبيق</button>
            <a href="{{ route('admin.reports.index') }}" class="btn-secondary self-end text-center">إعادة</a>
        </form>
    </div>
</section>

<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-black text-slate-500">إيراد الفترة</div>
            <span class="rounded-full {{ $revenueGrowth >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-black">
                {{ $revenueGrowth >= 0 ? '+' : '' }}{{ number_format($revenueGrowth, 1) }}%
            </span>
        </div>
        <div class="mt-3 text-3xl font-black text-slate-950">{{ $money($summary['revenue']) }}</div>
        <div class="mt-1 text-xs font-bold text-slate-400">مقارنة بالفترة السابقة</div>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-black text-slate-500">طلبات الفترة</div>
            <span class="rounded-full {{ $ordersGrowth >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-black">
                {{ $ordersGrowth >= 0 ? '+' : '' }}{{ number_format($ordersGrowth, 1) }}%
            </span>
        </div>
        <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['orders']) }}</div>
        <div class="mt-1 text-xs font-bold text-slate-400">ملغي: {{ number_format($summary['cancelled_orders']) }}</div>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-black text-slate-500">متوسط الطلب</div>
            <span class="rounded-full {{ $avgGrowth >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-black">
                {{ $avgGrowth >= 0 ? '+' : '' }}{{ number_format($avgGrowth, 1) }}%
            </span>
        </div>
        <div class="mt-3 text-3xl font-black text-slate-950">{{ $money($summary['average_order']) }}</div>
        <div class="mt-1 text-xs font-bold text-slate-400">قيمة الطلب الواحد</div>
    </div>
    <div class="rounded-3xl border border-rose-100 bg-rose-50 p-5 shadow-sm">
        <div class="text-sm font-black text-rose-700">نواقص المخزون</div>
        <div class="mt-3 text-3xl font-black text-rose-800">{{ number_format($summary['low_stock']) }}</div>
        <div class="mt-1 text-xs font-bold text-rose-600">من أصل {{ number_format($summary['products']) }} منتج</div>
    </div>
</section>

<section class="grid gap-4 xl:grid-cols-[1.4fr_.9fr]">
    <div class="card-premium p-5">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="text-right">
                <div class="text-sm font-black text-emerald-600">Sales Trend</div>
                <h2 class="text-2xl font-black text-slate-950">أداء آخر 6 أشهر</h2>
            </div>
            <span class="badge-success">{{ number_format($summary['stock_movements']) }} حركة مخزون بالفترة</span>
        </div>
        <div class="grid gap-3 md:grid-cols-6">
            @forelse($monthlySales->reverse() as $row)
                @php
                    $percent = min(100, max(8, ((float) $row->revenue / $maxRevenue) * 100));
                @endphp
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <div class="mb-3 text-sm font-black text-slate-500">{{ $row->month_key }}</div>
                    <div class="flex h-32 items-end rounded-2xl bg-white p-2">
                        <div class="w-full rounded-xl bg-gradient-to-t from-emerald-700 to-teal-400" style="height: {{ $percent }}%"></div>
                    </div>
                    <div class="mt-3 text-sm font-black text-slate-900">{{ $money($row->revenue) }}</div>
                    <div class="text-xs font-bold text-slate-500">{{ number_format((int) $row->orders_count) }} طلب</div>
                </div>
            @empty
                <div class="empty-state md:col-span-6">لا توجد بيانات شهرية بعد.</div>
            @endforelse
        </div>
    </div>

    <div class="card-premium p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-black text-slate-950">حالات الطلبات</h2>
            <span class="badge-success">{{ number_format($summary['orders']) }} طلب</span>
        </div>
        <div class="grid gap-2">
            @forelse($statusBreakdown as $status)
                @php
                    $countPercent = $summary['orders'] > 0 ? min(100, ((int) $status->total / $summary['orders']) * 100) : 0;
                    $label = $statusLabels[$status->status] ?? ($status->status ?: 'غير محدد');
                @endphp
                <div class="rounded-2xl border border-slate-100 bg-white p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-black text-slate-800">{{ $label }}</span>
                        <span class="text-xs font-black text-slate-500">{{ number_format((int) $status->total) }} · {{ $money($status->revenue) }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-600" style="width: {{ $countPercent }}%"></div>
                    </div>
                </div>
            @empty
                <div class="empty-state">لا توجد طلبات في هذه الفترة.</div>
            @endforelse
        </div>
    </div>
</section>

<section class="grid gap-4 xl:grid-cols-[.9fr_1.3fr]">
    <div class="card-premium p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-black text-slate-950">نواقص تحتاج متابعة</h2>
            <a href="{{ route('admin.inventory.alerts') }}" class="btn-secondary">فتح النواقص</a>
        </div>
        <div class="grid gap-2">
            @forelse($lowStockProducts as $product)
                <a href="{{ route('admin.products.edit', $product) }}" class="rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-rose-200 hover:bg-rose-50">
                    <div class="flex items-center justify-between gap-3">
                        <span class="truncate text-sm font-black text-slate-900">{{ $product->name }}</span>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700">{{ number_format((float) $product->quantity) }}</span>
                    </div>
                    <div class="mt-1 text-xs font-bold text-slate-500">SKU: {{ $product->sku ?: '-' }} · حد الطلب: {{ number_format((float) $product->reorder_level) }}</div>
                </a>
            @empty
                <div class="empty-state">لا توجد نواقص حرجة حالياً.</div>
            @endforelse
        </div>
    </div>

    <div class="card-premium p-5">
        <div class="mb-4 text-right">
            <div class="text-sm font-black text-emerald-600">Quick Audit Scope</div>
            <h2 class="text-2xl font-black text-slate-950">مسار مراجعة يومي مختصر</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">افتح أهم التقارير بنفس ترتيب المراجعة: مالية، نقدية، مخزون، ثم مبيعات.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <a href="{{ route('admin.accounting.reports.trial-balance') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                <div class="text-sm font-black text-slate-900">1. ميزان المراجعة</div>
                <div class="mt-1 text-xs font-bold text-slate-500">تأكد من توازن القيود</div>
            </a>
            <a href="{{ route('admin.accounting.reports.cash-flow') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                <div class="text-sm font-black text-slate-900">2. التدفقات النقدية</div>
                <div class="mt-1 text-xs font-bold text-slate-500">راجع الداخل والخارج</div>
            </a>
            <a href="{{ route('admin.inventory.stocks') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                <div class="text-sm font-black text-slate-900">3. أرصدة المخزون</div>
                <div class="mt-1 text-xs font-bold text-slate-500">قارن الرصيد الفعلي</div>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                <div class="text-sm font-black text-slate-900">4. الطلبات</div>
                <div class="mt-1 text-xs font-bold text-slate-500">تابع التنفيذ والتحصيل</div>
            </a>
        </div>
    </div>
</section>

<section class="grid gap-4 xl:grid-cols-3">
    @foreach($reportGroups as $group)
        @php
            $tone = [
                'emerald' => 'from-emerald-700 to-teal-500',
                'blue' => 'from-blue-700 to-cyan-500',
                'cyan' => 'from-cyan-700 to-sky-500',
                'slate' => 'from-slate-800 to-slate-600',
                'amber' => 'from-amber-700 to-orange-500',
            ][$group['accent']] ?? 'from-emerald-700 to-teal-500';
        @endphp
        <section class="card-premium overflow-hidden p-0">
            <div class="bg-gradient-to-br {{ $tone }} p-5 text-white">
                <h2 class="text-xl font-black">{{ $group['title'] }}</h2>
                <p class="mt-2 text-sm font-bold leading-6 text-white/75">{{ $group['description'] }}</p>
            </div>
            <div class="grid gap-2 p-4">
                @foreach($group['reports'] as $report)
                    <div class="group rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ $report['route'] }}" class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-black text-slate-900">{{ $report['label'] }}</span>
                                <span class="mt-1 block truncate text-xs font-bold text-slate-500">{{ $report['desc'] }}</span>
                            </a>
                            <a href="{{ $report['route'] }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-emerald-600 group-hover:text-white">↗</a>
                        </div>
                        @if(!empty($report['export']))
                            <a href="{{ $report['export'] }}" target="_blank" class="mt-2 inline-flex rounded-xl bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-600 transition hover:bg-white">PDF</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</section>
@endsection
