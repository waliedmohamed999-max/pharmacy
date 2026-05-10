@extends('admin.layouts.app')

@section('page-title', 'تنبيهات نقص المخزون')
@section('page-subtitle', 'منتجات وصلت أو نزلت عن حد الطلب')

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
    <div class="flex items-end gap-2 md:col-span-2">
        <button class="btn-primary">تطبيق</button>
        <a href="{{ route('admin.inventory.alerts') }}" class="btn-secondary">إعادة</a>
    </div>
</form>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>المخزن</th><th>المنتج</th><th>الرصيد</th><th>حد الطلب</th><th>كمية الطلب</th><th>اقتراح</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php($need = max(0, (float)$r->reorder_qty > 0 ? (float)$r->reorder_qty : ((float)$r->reorder_level - (float)$r->qty)))
                <tr>
                    <td>{{ $r->warehouse_name }}</td>
                    <td>{{ $r->product_name }}</td>
                    <td class="text-rose-600 font-bold">{{ number_format($r->qty, 2) }}</td>
                    <td>{{ number_format($r->reorder_level, 2) }}</td>
                    <td>{{ number_format($r->reorder_qty, 2) }}</td>
                    <td>{{ number_format($need, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد نواقص حاليًا.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $rows->links() }}</div>
</section>
@endsection
