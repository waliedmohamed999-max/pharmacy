@extends('admin.layouts.app')

@section('page-title', 'إنشاء فاتورة مشتريات')
@section('page-subtitle', 'إضافة فاتورة مورد وترحيل قيدها المحاسبي تلقائيًا')

@section('content')
<form action="{{ route('admin.accounting.purchases.store') }}" method="POST" class="space-y-4">
    @csrf
    <section class="card-premium p-4 grid md:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs text-slate-500 mb-1">المورد</label>
            <select name="contact_id" class="select-premium" required>
                <option value="">اختر المورد</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
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
            <label class="block text-xs text-slate-500 mb-1">تاريخ الفاتورة</label>
            <input type="date" name="invoice_date" class="input-premium" value="{{ date('Y-m-d') }}" required>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">تاريخ الاستحقاق</label>
            <input type="date" name="due_date" class="input-premium">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">خصم</label>
            <input type="number" step="0.01" name="discount" class="input-premium" value="0">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">ضريبة</label>
            <input type="number" step="0.01" name="tax" class="input-premium" value="0">
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="font-black mb-3">بنود الفاتورة</h2>
        @for($i = 0; $i < 3; $i++)
            <div class="grid md:grid-cols-5 gap-3 mb-3 purchase-row">
                <select name="product_id[]" class="select-premium product-select">
                    <option value="">منتج اختياري</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-barcode="{{ $p->barcode }}" data-sku="{{ $p->sku }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="barcode[]" class="input-premium barcode-input" placeholder="باركود الصنف (اختياري)">
                <input type="text" name="description[]" class="input-premium" placeholder="وصف البند" required>
                <input type="number" step="0.01" min="0.01" name="qty[]" class="input-premium" placeholder="الكمية" required>
                <input type="number" step="0.01" min="0" name="unit_cost[]" class="input-premium" placeholder="تكلفة الوحدة" required>
            </div>
        @endfor
    </section>

    <section class="card-premium p-4">
        <label class="block text-xs text-slate-500 mb-1">ملاحظات</label>
        <textarea name="notes" class="input-premium" rows="3"></textarea>
    </section>

    <div class="flex justify-end">
        <button class="btn-primary">حفظ الفاتورة</button>
    </div>
</form>

<script>
    document.querySelectorAll('.purchase-row').forEach((row) => {
        const select = row.querySelector('.product-select');
        const barcodeInput = row.querySelector('.barcode-input');
        select?.addEventListener('change', () => {
            const opt = select.options[select.selectedIndex];
            if (!opt) return;
            if (barcodeInput.value.trim() !== '') return;
            barcodeInput.value = (opt.dataset.barcode || opt.dataset.sku || '').toUpperCase();
        });
    });
</script>
@endsection

