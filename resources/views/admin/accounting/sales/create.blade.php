@extends('admin.layouts.app')

@section('page-title', 'إنشاء فاتورة مبيعات')
@section('page-subtitle', 'إضافة فاتورة عميل وترحيل قيدها المحاسبي تلقائياً')

@section('content')
@php
    $productsPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'price' => (float) $product->price,
        'quantity' => (float) $product->quantity,
    ])->values();

    $oldDescriptions = old('description', ['', '', '']);
    $oldProducts = old('product_id', ['', '', '']);
    $oldQty = old('qty', ['', '', '']);
    $oldPrices = old('unit_price', ['', '', '']);
    $lineCount = max(3, count($oldDescriptions));
@endphp

<form id="sales-invoice-form" action="{{ route('admin.accounting.sales.store') }}" method="POST" class="space-y-5">
    @csrf

    <section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="text-right">
                <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Sales Invoice</div>
                <h1 class="mt-1 text-3xl font-black text-slate-950">إنشاء فاتورة مبيعات</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">اختر العميل والمخزن، أضف البنود، وسيتم حساب الإجمالي والضريبة والرصيد تلقائياً.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">عدد البنود</div>
                    <div id="summary-lines" class="mt-1 text-sm font-black text-slate-950">0</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">الإجمالي الفرعي</div>
                    <div id="summary-subtotal" class="mt-1 text-sm font-black text-slate-950">0.00</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">الخصم/الضريبة</div>
                    <div id="summary-adjustments" class="mt-1 text-sm font-black text-slate-950">0.00</div>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="text-xs font-bold text-emerald-700">صافي الفاتورة</div>
                    <div id="summary-total" class="mt-1 text-sm font-black text-emerald-700">0.00</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 text-right">
            <h2 class="text-xl font-black text-slate-950">بيانات الفاتورة</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">هذه البيانات تحدد العميل والمخزن وتاريخ الاستحقاق.</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">العميل</label>
                <select name="contact_id" class="select-premium" required>
                    <option value="">اختر العميل</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('contact_id') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('contact_id')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">المخزن</label>
                <select name="warehouse_id" class="select-premium" required>
                    <option value="">اختر المخزن</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                    @endforeach
                </select>
                @error('warehouse_id')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">تاريخ الفاتورة</label>
                <input type="date" name="invoice_date" class="input-premium" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">تاريخ الاستحقاق</label>
                <input type="date" name="due_date" class="input-premium" value="{{ old('due_date') }}">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-black text-slate-500">خصم</label>
                <input id="discount-input" type="number" step="0.01" min="0" name="discount" class="input-premium" value="{{ old('discount', 0) }}">
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-black text-slate-500">ضريبة</label>
                <input id="tax-input" type="number" step="0.01" min="0" name="tax" class="input-premium" value="{{ old('tax', 0) }}">
            </div>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-right">
                <h2 class="text-xl font-black text-slate-950">بنود الفاتورة</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">اختيار المنتج يملأ الوصف والسعر تلقائياً، ويمكن إضافة بند يدوي بدون منتج.</p>
            </div>
            <button id="add-line" type="button" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700 transition hover:bg-emerald-100">إضافة بند</button>
        </div>

        <div class="hidden rounded-2xl bg-slate-50 px-4 py-2 text-xs font-black text-slate-500 xl:grid xl:grid-cols-[1.2fr_1.2fr_.55fr_.65fr_.65fr_44px] xl:gap-3">
            <div>المنتج</div>
            <div>الوصف</div>
            <div>الكمية</div>
            <div>سعر الوحدة</div>
            <div>الإجمالي</div>
            <div></div>
        </div>

        <div id="invoice-lines" class="mt-3 space-y-3">
            @for($i = 0; $i < $lineCount; $i++)
                <div class="invoice-line grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 xl:grid-cols-[1.2fr_1.2fr_.55fr_.65fr_.65fr_44px]">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">المنتج</label>
                        <select name="product_id[]" class="select-premium product-select">
                            <option value="">منتج اختياري</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) ($oldProducts[$i] ?? '') === (string) $product->id)>
                                    {{ $product->name }} | SKU: {{ $product->sku ?: '-' }} | مخزون: {{ number_format((float) $product->quantity) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">الوصف</label>
                        <input type="text" name="description[]" class="input-premium description-input" value="{{ $oldDescriptions[$i] ?? '' }}" placeholder="وصف البند">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">الكمية</label>
                        <input type="number" step="0.01" min="0.01" name="qty[]" class="input-premium qty-input" value="{{ $oldQty[$i] ?? '' }}" placeholder="0">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">سعر الوحدة</label>
                        <input type="number" step="0.01" min="0" name="unit_price[]" class="input-premium price-input" value="{{ $oldPrices[$i] ?? '' }}" placeholder="0.00">
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-3 py-2 text-right">
                        <div class="text-xs font-bold text-slate-500">الإجمالي</div>
                        <div class="line-total mt-1 font-black text-slate-950">0.00</div>
                    </div>
                    <button type="button" class="remove-line flex h-11 w-11 items-center justify-center rounded-2xl border border-red-100 bg-red-50 text-lg font-black text-red-600 transition hover:bg-red-100">×</button>
                </div>
            @endfor
        </div>

        @error('description')<p class="mt-3 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        @error('qty')<p class="mt-3 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
    </section>

    <section class="card-premium p-5">
        <label class="mb-1 block text-xs font-black text-slate-500">ملاحظات</label>
        <textarea name="notes" class="input-premium" rows="3">{{ old('notes') }}</textarea>
    </section>

    <div class="flex justify-end">
        <button class="btn-primary px-7">حفظ الفاتورة</button>
    </div>
</form>

<template id="line-template">
    <div class="invoice-line grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 xl:grid-cols-[1.2fr_1.2fr_.55fr_.65fr_.65fr_44px]">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">المنتج</label>
            <select name="product_id[]" class="select-premium product-select">
                <option value="">منتج اختياري</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} | SKU: {{ $product->sku ?: '-' }} | مخزون: {{ number_format((float) $product->quantity) }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">الوصف</label><input type="text" name="description[]" class="input-premium description-input" placeholder="وصف البند"></div>
        <div><label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">الكمية</label><input type="number" step="0.01" min="0.01" name="qty[]" class="input-premium qty-input" placeholder="0"></div>
        <div><label class="mb-1 block text-xs font-bold text-slate-500 xl:hidden">سعر الوحدة</label><input type="number" step="0.01" min="0" name="unit_price[]" class="input-premium price-input" placeholder="0.00"></div>
        <div class="rounded-2xl bg-slate-50 px-3 py-2 text-right"><div class="text-xs font-bold text-slate-500">الإجمالي</div><div class="line-total mt-1 font-black text-slate-950">0.00</div></div>
        <button type="button" class="remove-line flex h-11 w-11 items-center justify-center rounded-2xl border border-red-100 bg-red-50 text-lg font-black text-red-600 transition hover:bg-red-100">×</button>
    </div>
</template>

<script>
    (() => {
        const products = @json($productsPayload);
        const productMap = new Map(products.map((product) => [String(product.id), product]));
        const lines = document.getElementById('invoice-lines');
        const template = document.getElementById('line-template');
        const addLine = document.getElementById('add-line');
        const discountInput = document.getElementById('discount-input');
        const taxInput = document.getElementById('tax-input');
        const summaryLines = document.getElementById('summary-lines');
        const summarySubtotal = document.getElementById('summary-subtotal');
        const summaryAdjustments = document.getElementById('summary-adjustments');
        const summaryTotal = document.getElementById('summary-total');

        const money = (value) => Number(value || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const numberValue = (input) => Number.parseFloat(input.value || '0') || 0;

        const recalc = () => {
            let subtotal = 0;
            let activeLines = 0;
            lines.querySelectorAll('.invoice-line').forEach((line) => {
                const qty = numberValue(line.querySelector('.qty-input'));
                const price = numberValue(line.querySelector('.price-input'));
                const total = qty * price;
                const hasText = line.querySelector('.description-input').value.trim() !== '' || line.querySelector('.product-select').value !== '';
                if (hasText || qty > 0 || price > 0) activeLines++;
                subtotal += total;
                line.querySelector('.line-total').textContent = money(total);
            });

            const discount = numberValue(discountInput);
            const tax = numberValue(taxInput);
            summaryLines.textContent = activeLines.toLocaleString('ar-SA');
            summarySubtotal.textContent = money(subtotal);
            summaryAdjustments.textContent = money(tax - discount);
            summaryTotal.textContent = money(Math.max(0, subtotal - discount + tax));
        };

        const bindLine = (line) => {
            const productSelect = line.querySelector('.product-select');
            const description = line.querySelector('.description-input');
            const qty = line.querySelector('.qty-input');
            const price = line.querySelector('.price-input');

            productSelect.addEventListener('change', () => {
                const product = productMap.get(String(productSelect.value));
                if (product) {
                    if (!description.value.trim()) description.value = product.name;
                    if (!price.value) price.value = Number(product.price || 0).toFixed(2);
                    if (!qty.value) qty.value = '1';
                }
                recalc();
            });
            [description, qty, price].forEach((input) => input.addEventListener('input', recalc));
            line.querySelector('.remove-line').addEventListener('click', () => {
                if (lines.querySelectorAll('.invoice-line').length > 1) {
                    line.remove();
                    recalc();
                }
            });
        };

        addLine.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            const line = fragment.querySelector('.invoice-line');
            lines.appendChild(fragment);
            bindLine(line);
            recalc();
        });

        [discountInput, taxInput].forEach((input) => input.addEventListener('input', recalc));
        lines.querySelectorAll('.invoice-line').forEach(bindLine);
        recalc();
    })();
</script>
@endsection
