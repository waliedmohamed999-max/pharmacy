@extends('admin.layouts.app')

@section('page-title', 'حركات المخزون')
@section('page-subtitle', 'سجل كامل لكل دخول/خروج/تحويل/تسوية')

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-3 gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">المخزن</label>
        <select name="warehouse_id" class="select-premium">
            <option value="">كل المخازن</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected((int)$warehouseId === (int)$w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">النوع</label>
        <select name="type" class="select-premium">
            <option value="">كل الأنواع</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button class="btn-primary w-full">تطبيق</button>
        <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr><th>#</th><th>النوع</th><th>المخزن</th><th>إلى مخزن</th><th>المنتج</th><th>الكمية</th><th>تكلفة الوحدة</th><th>القيمة</th><th>التاريخ</th><th>PDF</th></tr>
            </thead>
            <tbody>
            @forelse($movements as $m)
                <tr>
                    <td>{{ $m->number }}</td>
                    <td>{{ $m->type }}</td>
                    <td>{{ $m->warehouse?->name }}</td>
                    <td>{{ $m->targetWarehouse?->name ?: '-' }}</td>
                    <td>{{ $m->product?->name }}</td>
                    <td>{{ number_format($m->qty, 2) }}</td>
                    <td>{{ number_format($m->unit_cost, 4) }}</td>
                    <td>{{ number_format($m->line_total, 2) }}</td>
                    <td>{{ optional($m->movement_date)->format('Y-m-d') }}</td>
                    <td><a href="{{ route('admin.inventory.movements.pdf', $m) }}" class="btn-secondary">تحميل</a></td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty-state">لا توجد حركات.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</section>
@endsection
