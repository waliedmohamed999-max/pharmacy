@extends('admin.layouts.app')

@section('page-title', 'كارت صنف')
@section('page-subtitle', 'تتبع حركة منتج داخل مخزن مع رصيد جاري')

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-5 gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">المخزن</label>
        <select name="warehouse_id" class="select-premium" required>
            <option value="">اختر</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected((int)$warehouseId === (int)$w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">المنتج</label>
        <select name="product_id" class="select-premium" required>
            <option value="">اختر</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected((int)$productId === (int)$p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label class="block text-xs text-slate-500 mb-1">من تاريخ</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="input-premium"></div>
    <div><label class="block text-xs text-slate-500 mb-1">إلى تاريخ</label><input type="date" name="date_to" value="{{ $dateTo }}" class="input-premium"></div>
    <div class="flex items-end gap-2">
        <button class="btn-primary w-full">عرض</button>
        <a href="{{ route('admin.inventory.stock-card') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

@if($warehouseId && $productId)
<div class="kpi-card mb-4">
    <div class="text-sm text-slate-500">رصيد افتتاحي</div>
    <div class="kpi-value">{{ number_format($openingBalance, 2) }}</div>
</div>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>التاريخ</th><th>النوع</th><th>رقم الحركة</th><th>دخول/خروج</th><th>الكمية</th><th>الرصيد الجاري</th><th>المرجع</th></tr></thead>
            <tbody>
            @forelse($movements as $m)
                <tr>
                    <td>{{ optional($m->movement_date)->format('Y-m-d') }}</td>
                    <td>{{ $m->type }}</td>
                    <td>{{ $m->number }}</td>
                    <td class="{{ (float)$m->delta_qty >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ (float)$m->delta_qty >= 0 ? 'دخول' : 'خروج' }}</td>
                    <td>{{ number_format($m->delta_qty, 2) }}</td>
                    <td>{{ number_format($m->running_balance, 2) }}</td>
                    <td>{{ $m->reference_type ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد حركات للفترة المحددة.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
