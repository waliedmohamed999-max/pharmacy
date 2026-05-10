@extends('admin.layouts.app')

@section('page-title', 'مخزون الصيدلية')
@section('page-subtitle', 'مركز تشغيل متكامل لإدارة الأرصدة، الحركات، الجرد، النواقص، الراكدة، والتكلفة المحاسبية')

@section('page-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.inventory.receive.form') }}" class="btn-primary">سند استلام</a>
    <a href="{{ route('admin.inventory.issue.form') }}" class="btn-secondary">سند صرف</a>
    <a href="{{ route('admin.inventory.transfer.form') }}" class="btn-secondary">تحويل مخزني</a>
    <a href="{{ route('admin.inventory.adjustment.form') }}" class="btn-secondary">تسوية مخزون</a>
    <a href="{{ route('admin.inventory.export.overview') }}" class="btn-secondary">خطة إعادة الطلب CSV</a>
    <form method="POST" action="{{ route('admin.inventory.sync-products') }}" onsubmit="return confirm('سيتم ربط أرصدة المخزن الرئيسي بكميات المنتجات الحالية. متابعة؟')">
        @csrf
        <button class="btn-secondary">مزامنة كميات المنتجات</button>
    </form>
</div>
@endsection

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2) . ' ج.م';
    $qty = fn ($value) => number_format((float) $value, 2);
    $avgUnitCost = $stockQty > 0 ? $stockValue / $stockQty : 0;
@endphp

@if($stockRowsCount < 1)
    <div class="mb-4 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-sm font-bold leading-7 text-amber-800">
        لا توجد أرصدة تفصيلية داخل جدول المخازن حتى الآن، لذلك تعرض اللوحة رصيد المنتجات العام مؤقتًا. أنشئ مخزنًا أو سند استلام حتى يبدأ النظام في تتبع المخزون حسب الفروع والمخازن.
    </div>
@endif

<section class="card-premium mb-5 overflow-hidden p-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-sm font-black text-emerald-600">Pharmacy Inventory Control</div>
            <h2 class="text-2xl font-black text-slate-950">لوحة قيادة المخزون</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">كل أدوات المخزون الأساسية، مع مراقبة النواقص والتكلفة وجودة البيانات.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.inventory.counts.create') }}" class="btn-primary">بدء جرد فعلي</a>
            <a href="{{ route('admin.inventory.alerts') }}" class="btn-secondary">تنبيهات النواقص</a>
            <a href="{{ route('admin.inventory.stock-card') }}" class="btn-secondary">كارت صنف</a>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($quickTools as $tool)
            <a href="{{ $tool['route'] }}" class="rounded-3xl border border-slate-200 bg-white/80 p-4 transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl">
                <div class="font-black text-slate-900">{{ $tool['label'] }}</div>
                <div class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $tool['desc'] }}</div>
            </a>
        @endforeach
    </div>
</section>

<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <div class="kpi-card">
        <div class="text-sm text-slate-500">المخازن النشطة</div>
        <div class="kpi-value">{{ number_format($activeWarehousesCount) }} / {{ number_format($warehousesCount) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">الأصناف النشطة</div>
        <div class="kpi-value">{{ number_format($productsCount) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">إجمالي الكمية</div>
        <div class="kpi-value">{{ $qty($stockQty) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">قيمة المخزون</div>
        <div class="kpi-value">{{ $money($stockValue) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">متوسط تكلفة الوحدة: {{ $money($avgUnitCost) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">نواقص تحت حد الطلب</div>
        <div class="kpi-value text-rose-700">{{ number_format($lowStockCount) }}</div>
        <div class="mt-2 text-xs font-bold text-slate-500">قيمة طلب مقترحة: {{ $money($reorderValue) }}</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">جلسات الجرد</div>
        <div class="kpi-value">{{ number_format($openCounts) }} مفتوحة</div>
        <div class="mt-2 text-xs font-bold text-slate-500">معتمدة: {{ number_format($postedCounts) }}</div>
    </div>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-4">
    <section class="card-premium p-4 xl:col-span-3">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black">مراقبة الحركة آخر 30 يوم</h2>
                <p class="text-sm font-semibold text-slate-500">كمية وقيمة كل الحركات الواردة والصادرة والتحويلات</p>
            </div>
            <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary">كل الحركات</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-black text-slate-500">إجمالي الكميات المتحركة</div>
                <div class="mt-2 text-2xl font-black">{{ $qty($movementQty30) }}</div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-black text-slate-500">قيمة الحركات</div>
                <div class="mt-2 text-2xl font-black">{{ $money($movementValue30) }}</div>
            </div>
            @forelse($movementTypes as $type)
                <div class="rounded-3xl border border-slate-200 bg-white p-4">
                    <div class="text-xs font-black text-slate-500">{{ $type->type }}</div>
                    <div class="mt-2 text-2xl font-black">{{ number_format($type->count) }}</div>
                    <div class="text-xs font-bold text-slate-400">كمية: {{ $qty($type->qty) }}</div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-4 text-sm font-bold text-slate-500">لا توجد حركات خلال آخر 30 يوم.</div>
            @endforelse
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="mb-3 text-xl font-black">صحة بيانات المخزون</h2>
        <div class="space-y-2">
            @foreach($dataQualityIssues as $issue)
                <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-3">
                    <span class="text-sm font-black text-slate-700">{{ $issue['label'] }}</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $issue['tone'] === 'success' ? 'bg-emerald-100 text-emerald-700' : ($issue['tone'] === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">{{ number_format($issue['value']) }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black">خطة إعادة الطلب</h2>
                <p class="text-sm font-semibold text-slate-500">أصناف وصلت أو نزلت تحت حد الطلب</p>
            </div>
            <a href="{{ route('admin.inventory.export.overview') }}" class="btn-secondary">تصدير الخطة</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>المخزن</th>
                        <th>الصنف</th>
                        <th>الرصيد</th>
                        <th>حد الطلب</th>
                        <th>المقترح</th>
                        <th>تكلفة تقديرية</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($lowStockRows->take(10) as $row)
                    <tr>
                        <td>{{ $row->warehouse_name }}</td>
                        <td>{{ $row->product_name }}</td>
                        <td class="font-black text-rose-700">{{ $qty($row->qty) }}</td>
                        <td>{{ $qty($row->reorder_level) }}</td>
                        <td class="font-black text-emerald-700">{{ $qty($row->suggested_qty) }}</td>
                        <td>{{ $money($row->estimated_cost) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">لا توجد نواقص حالياً.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black">ملخص المخازن</h2>
                <p class="text-sm font-semibold text-slate-500">توزيع الكميات والقيمة حسب المخزن</p>
            </div>
            <a href="{{ route('admin.inventory.warehouses') }}" class="btn-secondary">إدارة المخازن</a>
        </div>
        <div class="space-y-2">
            @forelse($warehouseSummary as $warehouse)
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <div>
                            <div class="font-black">{{ $warehouse->name }}</div>
                            <div class="text-xs font-bold text-slate-400">{{ $warehouse->code }} | {{ $warehouse->products_count }} صنف</div>
                        </div>
                        <span class="badge {{ $warehouse->is_active ? 'badge-success' : 'badge-warning' }}">{{ $warehouse->is_active ? 'نشط' : 'متوقف' }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm font-bold text-slate-600">
                        <div>الكمية: {{ $qty($warehouse->qty) }}</div>
                        <div>القيمة: {{ $money($warehouse->value) }}</div>
                    </div>
                </div>
            @empty
                <div class="empty-state">لا توجد أرصدة موزعة على مخازن حتى الآن.</div>
            @endforelse
        </div>
    </section>
</div>

<div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">الأصناف الأسرع حركة</h2>
            <a href="{{ route('admin.inventory.stock-card') }}" class="btn-secondary">تحليل صنف</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الصنف</th><th>الحركات</th><th>الكمية</th><th>القيمة</th></tr></thead>
                <tbody>
                @forelse($fastMovingProducts as $row)
                    <tr>
                        <td>{{ $row->product_name }}</td>
                        <td>{{ number_format($row->movements_count) }}</td>
                        <td class="font-black">{{ $qty($row->qty) }}</td>
                        <td>{{ $money($row->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد أصناف منصرفة خلال آخر 30 يوم.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-xl font-black">أصناف راكدة تحتاج قرار</h2>
            <a href="{{ route('admin.products.index') }}" class="btn-secondary">مراجعة المنتجات</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>المخزن</th><th>الصنف</th><th>الرصيد</th><th>القيمة</th></tr></thead>
                <tbody>
                @forelse($deadStockProducts as $row)
                    <tr>
                        <td>{{ $row->warehouse_name }}</td>
                        <td>{{ $row->product_name }}</td>
                        <td>{{ $qty($row->qty) }}</td>
                        <td class="font-black text-amber-700">{{ $money($row->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد أصناف راكدة حسب آخر 90 يوم.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card-premium p-4">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-xl font-black">آخر حركات المخزون</h2>
        <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary">عرض كل الحركات</a>
    </div>
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>رقم الحركة</th>
                    <th>النوع</th>
                    <th>المخزن</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>التكلفة</th>
                    <th>التاريخ</th>
                    <th>مستند</th>
                </tr>
            </thead>
            <tbody>
            @forelse($latestMovements as $m)
                <tr>
                    <td>{{ $m->number }}</td>
                    <td><span class="badge-soft">{{ $m->type }}</span></td>
                    <td>{{ $m->warehouse?->name }}</td>
                    <td>{{ $m->product?->name }}</td>
                    <td>{{ $qty($m->qty) }}</td>
                    <td>{{ $money($m->line_total) }}</td>
                    <td>{{ optional($m->movement_date)->format('Y-m-d') }}</td>
                    <td><a href="{{ route('admin.inventory.movements.pdf', $m) }}" class="btn-secondary">PDF</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state">لا توجد حركات مخزون.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
