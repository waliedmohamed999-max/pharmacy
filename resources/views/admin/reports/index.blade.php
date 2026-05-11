@extends('admin.layouts.app')

@section('title', 'مركز التقارير')
@section('page-title', 'مركز التقارير')
@section('page-subtitle', 'قسم موحد لكل تقارير الصيدلية: المالية، المركز المالي، المخزون، الطلبات، المبيعات، العملاء والواجهة الخارجية')

@section('page-actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">لوحة التحكم</a>
        <a href="{{ route('admin.finance.index') }}" class="btn-secondary">المركز المالي</a>
        <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn-primary">تقرير المركز المالي</a>
    </div>
@endsection

@section('content')
    <form method="GET" class="card-premium grid gap-3 p-5 md:grid-cols-[1fr_1fr_auto_auto]">
        <label class="grid gap-2">
            <span class="text-xs font-black text-slate-500">من تاريخ</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-emerald-300">
        </label>
        <label class="grid gap-2">
            <span class="text-xs font-black text-slate-500">إلى تاريخ</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-emerald-300">
        </label>
        <button class="btn-primary self-end" type="submit">تطبيق الفترة</button>
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary self-end text-center">إعادة</a>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">طلبات الفترة</div>
            <div class="kpi-value">{{ number_format($summary['orders']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">إيراد الفترة</div>
            <div class="kpi-value">{{ number_format($summary['revenue'], 2) }} ج.م</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">متوسط الطلب</div>
            <div class="kpi-value">{{ number_format($summary['average_order'], 2) }} ج.م</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">المنتجات</div>
            <div class="kpi-value">{{ number_format($summary['products']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">العملاء</div>
            <div class="kpi-value">{{ number_format($summary['customers']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">نواقص المخزون</div>
            <div class="kpi-value text-rose-700">{{ number_format($summary['low_stock']) }}</div>
        </div>
        <div class="kpi-card">
            <div class="text-sm font-black text-slate-500">حركات المخزون</div>
            <div class="kpi-value">{{ number_format($summary['stock_movements']) }}</div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.2fr_.8fr]">
        <div class="card-premium p-5">
            <div class="mb-4">
                <div class="text-sm font-black text-emerald-600">Unified Reports Scope</div>
                <h2 class="text-2xl font-black text-slate-950">قسم التقارير الجديد</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">تم تجميع التقارير المالية والمركز المالي وتقارير التشغيل في مسار واحد، مع بقاء كل شاشات المحاسبة والمالية الأصلية كأدوات تنفيذية بدون تكرار في الشريط الجانبي.</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3">
                <a href="{{ route('admin.finance.index') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                    <div class="text-sm font-black text-slate-900">المركز المالي</div>
                    <div class="mt-1 text-xs font-bold text-slate-500">فواتير، ربحية، ضرائب، ذمم</div>
                </a>
                <a href="{{ route('admin.accounting.index') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                    <div class="text-sm font-black text-slate-900">النظام المحاسبي</div>
                    <div class="mt-1 text-xs font-bold text-slate-500">قيود، حسابات، مدفوعات</div>
                </a>
                <a href="{{ route('admin.inventory.index') }}" class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-emerald-200 hover:bg-emerald-50">
                    <div class="text-sm font-black text-slate-900">مخزون الصيدلية</div>
                    <div class="mt-1 text-xs font-bold text-slate-500">أرصدة، جرد، حركات، نواقص</div>
                </a>
            </div>
        </div>

        <div class="card-premium p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-black text-slate-950">حالات الطلبات</h2>
                <span class="badge-success">{{ number_format($summary['orders']) }} طلب</span>
            </div>
            <div class="grid gap-2">
                @forelse($statusBreakdown as $status)
                    <div class="flex items-center justify-between rounded-2xl border border-slate-100 bg-white p-3">
                        <span class="text-sm font-black text-slate-700">{{ $status->status ?: 'غير محدد' }}</span>
                        <span class="text-xs font-black text-slate-500">{{ number_format((int) $status->total) }} طلب · {{ number_format((float) $status->revenue, 2) }} ج.م</span>
                    </div>
                @empty
                    <div class="empty-state">لا توجد طلبات في هذه الفترة.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card-premium overflow-hidden p-5">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-sm font-black text-emerald-600">Reporting Command Center</div>
                <h2 class="text-2xl font-black text-slate-950">أداء آخر 6 أشهر</h2>
            </div>
            <span class="badge-success">موحد مع المالية والمخزون</span>
        </div>
        <div class="grid gap-3 md:grid-cols-6">
            @forelse($monthlySales->reverse() as $row)
                @php
                    $maxRevenue = max(1, (float) $monthlySales->max('revenue'));
                    $percent = min(100, max(8, ((float) $row->revenue / $maxRevenue) * 100));
                @endphp
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <div class="mb-3 text-sm font-black text-slate-500">{{ $row->month_key }}</div>
                    <div class="h-28 rounded-2xl bg-white p-2">
                        <div class="mt-auto h-full rounded-xl bg-emerald-100">
                            <div class="rounded-xl bg-gradient-to-t from-emerald-700 to-teal-400" style="height: {{ $percent }}%"></div>
                        </div>
                    </div>
                    <div class="mt-3 text-sm font-black text-slate-900">{{ number_format((float) $row->revenue, 2) }} ج.م</div>
                    <div class="text-xs font-bold text-slate-500">{{ number_format((int) $row->orders_count) }} طلب</div>
                </div>
            @empty
                <div class="empty-state md:col-span-6">لا توجد بيانات شهرية بعد.</div>
            @endforelse
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        @foreach($reportGroups as $group)
            @php
                $tone = [
                    'emerald' => 'from-emerald-700 to-teal-500',
                    'blue' => 'from-blue-700 to-cyan-500',
                    'cyan' => 'from-cyan-700 to-sky-500',
                    'slate' => 'from-slate-800 to-slate-600',
                    'violet' => 'from-violet-700 to-fuchsia-500',
                ][$group['accent']] ?? 'from-emerald-700 to-teal-500';
            @endphp
            <section class="card-premium overflow-hidden p-0">
                <div class="bg-gradient-to-br {{ $tone }} p-5 text-white">
                    <h2 class="text-xl font-black">{{ $group['title'] }}</h2>
                    <p class="mt-2 text-sm font-bold text-white/75">{{ $group['description'] }}</p>
                </div>
                <div class="grid gap-2 p-4">
                    @foreach($group['reports'] as $report)
                        <a href="{{ $report['route'] }}" class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-black text-slate-900">{{ $report['label'] }}</span>
                                <span class="block truncate text-xs font-bold text-slate-500">{{ $report['desc'] }}</span>
                            </span>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-emerald-600 group-hover:text-white">↗</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection
