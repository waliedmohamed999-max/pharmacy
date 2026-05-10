@extends('admin.layouts.app')

@section('page-title', 'سند صرف مخزني')
@section('page-subtitle', 'صرف من المخزن وربطه بقيود مالية')

@section('content')
<form action="{{ route('admin.inventory.issue.store') }}" method="POST" class="card-premium p-4 grid md:grid-cols-2 gap-3">
    @csrf
    <div>
        <label class="block text-xs text-slate-500 mb-1">المخزن</label>
        <select name="warehouse_id" class="select-premium" required>
            <option value="">اختر</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">المنتج</label>
        <select name="product_id" class="select-premium" required>
            <option value="">اختر</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label class="block text-xs text-slate-500 mb-1">الكمية</label><input type="number" step="0.01" min="0.01" name="qty" class="input-premium" required></div>
    <div><label class="block text-xs text-slate-500 mb-1">التاريخ</label><input type="date" name="movement_date" value="{{ date('Y-m-d') }}" class="input-premium" required></div>
    <div class="md:col-span-2"><label class="block text-xs text-slate-500 mb-1">ملاحظات</label><textarea name="notes" rows="3" class="input-premium"></textarea></div>
    <div class="md:col-span-2 flex justify-end"><button class="btn-primary">تسجيل الصرف</button></div>
</form>
@endsection
