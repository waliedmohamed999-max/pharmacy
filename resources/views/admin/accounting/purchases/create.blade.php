@extends('admin.layouts.app')

@section('page-title', 'إنشاء فاتورة مشتريات ضريبية')
@section('page-subtitle', 'فاتورة مورد احترافية مع ضريبة قيمة مضافة وبيانات QR بصيغة زاتكا الأساسية')

@section('content')
<form action="{{ route('admin.accounting.purchases.store') }}" method="POST" class="space-y-4" id="purchaseInvoiceForm">
    @csrf

    <section class="card-premium overflow-hidden p-0">
        <div class="bg-gradient-to-l from-emerald-700 via-teal-600 to-slate-900 p-5 text-white">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase text-emerald-100">ZATCA Ready Purchase Invoice</div>
                    <h2 class="mt-2 text-3xl font-black">بيانات الفاتورة والمورد</h2>
                    <p class="mt-2 text-sm font-bold text-white/75">سيتم توليد QR زاتكا من اسم المورد، الرقم الضريبي، وقت الفاتورة، الإجمالي والضريبة.</p>
                </div>
                <div class="rounded-2xl bg-white/12 px-4 py-3 text-sm font-black ring-1 ring-white/15">
                    ضريبة افتراضية 15%
                </div>
            </div>
        </div>

        <div class="grid gap-3 p-5 md:grid-cols-4">
            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">المورد</span>
                <select name="contact_id" id="contactSelect" class="select-premium" required>
                    <option value="">اختر المورد</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" data-tax-number="{{ $v->tax_number }}">{{ $v->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">الرقم الضريبي للمورد</span>
                <input type="text" name="supplier_tax_number" id="supplierTaxNumber" class="input-premium" placeholder="مثال: 300000000000003" inputmode="numeric">
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">رقم فاتورة المورد</span>
                <input type="text" name="supplier_invoice_number" class="input-premium" placeholder="اختياري">
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">المخزن</span>
                <select name="warehouse_id" class="select-premium" required>
                    <option value="">اختر المخزن</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">تاريخ الفاتورة</span>
                <input type="date" name="invoice_date" class="input-premium" value="{{ date('Y-m-d') }}" required>
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">تاريخ الاستحقاق</span>
                <input type="date" name="due_date" class="input-premium">
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">الخصم</span>
                <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="input-premium js-total-input" value="0">
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-black text-slate-500">نسبة الضريبة</span>
                <div class="relative">
                    <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="taxRateInput" class="input-premium js-total-input pl-10" value="15">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400">%</span>
                </div>
            </label>
        </div>
    </section>

    <section class="card-premium overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-white p-5">
            <div>
                <h2 class="text-xl font-black text-slate-950">بنود الفاتورة</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">اختر منتجاً لربطه بالمخزون أو اكتب بنداً يدوياً.</p>
            </div>
            <button type="button" id="addRowBtn" class="btn-secondary">إضافة بند</button>
        </div>

        <div class="overflow-x-auto p-5">
            <div class="min-w-[980px] space-y-3" id="purchaseRows">
                <div class="grid grid-cols-[1.2fr_1fr_1.35fr_.65fr_.75fr_.8fr_auto] gap-2 px-2 text-xs font-black text-slate-500">
                    <span>المنتج</span>
                    <span>باركود الصنف</span>
                    <span>وصف البند</span>
                    <span>الكمية</span>
                    <span>تكلفة الوحدة</span>
                    <span>الإجمالي</span>
                    <span></span>
                </div>

                @for($i = 0; $i < 3; $i++)
                    <div class="purchase-row grid grid-cols-[1.2fr_1fr_1.35fr_.65fr_.75fr_.8fr_auto] gap-2">
                        <select name="product_id[]" class="select-premium product-select">
                            <option value="">منتج اختياري</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" data-barcode="{{ $p->barcode }}" data-sku="{{ $p->sku }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="barcode[]" class="input-premium barcode-input" placeholder="اختياري">
                        <input type="text" name="description[]" class="input-premium description-input" placeholder="وصف البند" required>
                        <input type="number" step="0.01" min="0.01" name="qty[]" class="input-premium qty-input js-total-input" placeholder="1" required>
                        <input type="number" step="0.01" min="0" name="unit_cost[]" class="input-premium unit-cost-input js-total-input" placeholder="0.00" required>
                        <div class="grid h-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-black text-slate-700 row-total">0.00</div>
                        <button type="button" class="remove-row grid h-11 w-11 place-items-center rounded-xl border border-rose-100 bg-rose-50 text-rose-700" aria-label="حذف البند">×</button>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1fr_360px]">
        <div class="card-premium p-5">
            <label class="block text-xs font-black text-slate-500 mb-2">ملاحظات</label>
            <textarea name="notes" class="input-premium min-h-32" rows="4" placeholder="شروط السداد، ملاحظات المورد، رقم أمر الشراء..."></textarea>
        </div>

        <div class="card-premium overflow-hidden p-0">
            <div class="bg-slate-950 p-5 text-white">
                <div class="text-xs font-black uppercase text-emerald-200">Invoice Summary</div>
                <div class="mt-1 text-2xl font-black">ملخص الضريبة</div>
            </div>
            <div class="space-y-3 p-5 text-sm font-bold">
                <div class="flex justify-between gap-3"><span class="text-slate-500">الإجمالي قبل الخصم</span><span id="subtotalText">0.00</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">الخصم</span><span id="discountText">0.00</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">الوعاء الضريبي</span><span id="taxableText">0.00</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">ضريبة القيمة المضافة</span><span id="taxText">0.00</span></div>
                <div class="border-t border-slate-100 pt-3">
                    <div class="flex items-end justify-between gap-3">
                        <span class="text-base font-black text-slate-900">الإجمالي شامل الضريبة</span>
                        <span class="text-2xl font-black text-emerald-700" id="totalText">0.00</span>
                    </div>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-3 text-xs font-bold leading-6 text-emerald-800">
                    QR زاتكا سيُولّد بعد الحفظ من بيانات الفاتورة النهائية.
                </div>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.accounting.purchases.index') }}" class="btn-secondary">إلغاء</a>
        <button class="btn-primary">حفظ الفاتورة</button>
    </div>
</form>

<template id="purchaseRowTemplate">
    <div class="purchase-row grid grid-cols-[1.2fr_1fr_1.35fr_.65fr_.75fr_.8fr_auto] gap-2">
        <select name="product_id[]" class="select-premium product-select">
            <option value="">منتج اختياري</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" data-barcode="{{ $p->barcode }}" data-sku="{{ $p->sku }}">{{ $p->name }}</option>
            @endforeach
        </select>
        <input type="text" name="barcode[]" class="input-premium barcode-input" placeholder="اختياري">
        <input type="text" name="description[]" class="input-premium description-input" placeholder="وصف البند" required>
        <input type="number" step="0.01" min="0.01" name="qty[]" class="input-premium qty-input js-total-input" placeholder="1" required>
        <input type="number" step="0.01" min="0" name="unit_cost[]" class="input-premium unit-cost-input js-total-input" placeholder="0.00" required>
        <div class="grid h-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-black text-slate-700 row-total">0.00</div>
        <button type="button" class="remove-row grid h-11 w-11 place-items-center rounded-xl border border-rose-100 bg-rose-50 text-rose-700" aria-label="حذف البند">×</button>
    </div>
</template>

<script>
    const money = new Intl.NumberFormat('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const rowsContainer = document.getElementById('purchaseRows');
    const template = document.getElementById('purchaseRowTemplate');

    function toNumber(value) {
        return Number.parseFloat(value || '0') || 0;
    }

    function bindRow(row) {
        const select = row.querySelector('.product-select');
        const barcodeInput = row.querySelector('.barcode-input');
        const descriptionInput = row.querySelector('.description-input');

        select?.addEventListener('change', () => {
            const opt = select.options[select.selectedIndex];
            if (!opt) return;
            if (barcodeInput.value.trim() === '') {
                barcodeInput.value = (opt.dataset.barcode || opt.dataset.sku || '').toUpperCase();
            }
            if (descriptionInput.value.trim() === '' && opt.value) {
                descriptionInput.value = opt.textContent.trim();
            }
        });
    }

    function recalculate() {
        let subtotal = 0;
        document.querySelectorAll('.purchase-row').forEach((row) => {
            const qty = toNumber(row.querySelector('.qty-input')?.value);
            const cost = toNumber(row.querySelector('.unit-cost-input')?.value);
            const rowTotal = qty * cost;
            subtotal += rowTotal;
            row.querySelector('.row-total').textContent = money.format(rowTotal);
        });

        const discount = toNumber(document.getElementById('discountInput').value);
        const taxRate = toNumber(document.getElementById('taxRateInput').value);
        const taxable = Math.max(0, subtotal - discount);
        const tax = taxable * (taxRate / 100);
        const total = taxable + tax;

        document.getElementById('subtotalText').textContent = money.format(subtotal);
        document.getElementById('discountText').textContent = money.format(discount);
        document.getElementById('taxableText').textContent = money.format(taxable);
        document.getElementById('taxText').textContent = money.format(tax);
        document.getElementById('totalText').textContent = money.format(total);
    }

    document.getElementById('contactSelect')?.addEventListener('change', (event) => {
        const option = event.target.options[event.target.selectedIndex];
        const input = document.getElementById('supplierTaxNumber');
        if (input && input.value.trim() === '') {
            input.value = option?.dataset?.taxNumber || '';
        }
    });

    document.getElementById('addRowBtn')?.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.purchase-row');
        rowsContainer.appendChild(fragment);
        bindRow(row);
    });

    document.addEventListener('input', (event) => {
        if (event.target.classList.contains('js-total-input')) {
            recalculate();
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-row');
        if (!button) return;
        const rows = document.querySelectorAll('.purchase-row');
        if (rows.length <= 1) return;
        button.closest('.purchase-row')?.remove();
        recalculate();
    });

    document.querySelectorAll('.purchase-row').forEach(bindRow);
    recalculate();
</script>
@endsection
