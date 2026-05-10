@extends('admin.layouts.app')

@section('page-title', 'أرصدة المخزون')
@section('page-subtitle', 'الرصيد الحالي لكل منتج داخل كل مخزن')

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
        <label class="block text-xs text-slate-500 mb-1">المنتج</label>
        <select name="product_id" class="select-premium">
            <option value="">كل المنتجات</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected((int)$productId === (int)$p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button class="btn-primary w-full">تطبيق</button>
        <a href="{{ route('admin.inventory.stocks') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr><th>المخزن</th><th>المنتج</th><th>الكمية</th><th>متوسط التكلفة</th><th>القيمة</th></tr>
            </thead>
            <tbody>
            @forelse($stocks as $s)
                <tr>
                    <td>{{ $s->warehouse?->name }}</td>
                    <td>{{ $s->product?->name }}</td>
                    <td>{{ number_format($s->qty, 2) }}</td>
                    <td>{{ number_format($s->avg_cost, 4) }}</td>
                    <td>{{ number_format((float)$s->qty * (float)$s->avg_cost, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state">لا توجد أرصدة.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $stocks->links() }}</div>
</section>
@endsection
