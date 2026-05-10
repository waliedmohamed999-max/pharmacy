@extends('admin.layouts.app')

@section('page-title', 'طباعة ملصقات الباركود')
@section('page-subtitle', 'اختر الأصناف وحدد عدد النسخ لكل صنف')

@section('content')
<form method="POST" action="{{ route('admin.products.labels.print') }}" target="_blank" class="space-y-4">
    @csrf

    <section class="card-premium p-4">
        <div class="flex flex-wrap gap-2 mb-3">
            <input type="text" id="labelSearch" class="input-premium" placeholder="بحث بالاسم / SKU / الباركود">
            <button type="button" id="addLabelRow" class="btn-secondary">إضافة سطر</button>
        </div>

        <div id="rowsWrap" class="space-y-2"></div>
    </section>

    <div class="flex justify-end">
        <button class="btn-primary">فتح صفحة الطباعة</button>
    </div>
</form>

<template id="labelRowTpl">
    <div class="grid md:grid-cols-[1fr_140px_auto] gap-2 label-row">
        <select name="product_id[]" class="select-premium product-select" required>
            <option value="">اختر المنتج</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-search="{{ strtolower($product->name . ' ' . ($product->sku ?? '') . ' ' . ($product->barcode ?? '')) }}">
                    {{ $product->name }} | SKU: {{ $product->sku ?: '-' }} | BAR: {{ $product->barcode ?: '-' }}
                </option>
            @endforeach
        </select>
        <input type="number" min="1" max="200" value="1" name="copies[]" class="input-premium" placeholder="عدد النسخ" required>
        <button type="button" class="btn-danger remove-row">حذف</button>
    </div>
</template>

<script>
    const rowsWrap = document.getElementById('rowsWrap');
    const tpl = document.getElementById('labelRowTpl');
    const addBtn = document.getElementById('addLabelRow');
    const searchInput = document.getElementById('labelSearch');

    function addRow() {
        const row = tpl.content.firstElementChild.cloneNode(true);
        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
        rowsWrap.appendChild(row);
    }

    function applySearch() {
        const q = (searchInput.value || '').trim().toLowerCase();
        rowsWrap.querySelectorAll('.product-select').forEach((select) => {
            Array.from(select.options).forEach((opt, idx) => {
                if (idx === 0) return;
                const s = opt.dataset.search || '';
                opt.hidden = q !== '' && !s.includes(q);
            });
        });
    }

    addBtn.addEventListener('click', addRow);
    searchInput.addEventListener('input', applySearch);

    addRow();
    addRow();
</script>
@endsection

