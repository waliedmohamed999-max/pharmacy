@extends('admin.layouts.app')

@section('page-title', 'جلسة جرد جديدة')
@section('page-subtitle', 'إنشاء جرد من الرصيد الدفتري الحالي للمخزن')

@section('content')
<form action="{{ route('admin.inventory.counts.store') }}" method="POST" class="card-premium p-4 grid md:grid-cols-2 gap-3">
    @csrf
    <div>
        <label class="block text-xs text-slate-500 mb-1">المخزن</label>
        <select name="warehouse_id" class="select-premium" required>
            <option value="">اختر المخزن</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">تاريخ الجرد</label>
        <input type="date" name="count_date" value="{{ date('Y-m-d') }}" class="input-premium" required>
    </div>
    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="include_zero_stock" value="1">
            تضمين المنتجات ذات الرصيد الصفري
        </label>
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">ملاحظات</label>
        <textarea name="notes" rows="3" class="input-premium"></textarea>
    </div>
    <div class="md:col-span-2 flex justify-end">
        <button class="btn-primary">إنشاء الجلسة</button>
    </div>
</form>
@endsection
