@extends('admin.layouts.app')

@section('page-title', 'تسوية مخزون')
@section('page-subtitle', 'زيادة/نقص مخزون مع ترحيل القيد المالي تلقائيًا')

@section('content')
<form action="{{ route('admin.inventory.adjustment.store') }}" method="POST" class="card-premium p-4 grid md:grid-cols-2 gap-3">
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
            <option value="">اختر المنتج</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">فرق الكمية</label>
        <input type="number" step="0.01" name="qty_diff" class="input-premium" placeholder="+5 أو -3" required>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">تكلفة الوحدة</label>
        <input type="number" step="0.0001" min="0" name="unit_cost" class="input-premium" required>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">التاريخ</label>
        <input type="date" name="movement_date" value="{{ date('Y-m-d') }}" class="input-premium" required>
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">ملاحظات</label>
        <textarea name="notes" class="input-premium" rows="3"></textarea>
    </div>
    <div class="md:col-span-2 flex justify-end">
        <button class="btn-primary">تنفيذ التسوية</button>
    </div>
</form>
@endsection
