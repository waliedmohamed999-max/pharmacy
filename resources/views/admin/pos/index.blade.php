@extends('admin.layouts.app')

@section('page-title', 'طلبات يدوية من الصيدلية')
@section('page-subtitle', 'بيع مباشر بالباركود مع إظهار بيانات الدواء والسعر والمخزون فوراً')

@section('page-actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.orders.index') }}" class="btn-secondary">كل الطلبات</a>
        <a href="{{ route('admin.pos.history') }}" class="btn-secondary">سجل نقاط البيع</a>
    </div>
@endsection

@section('content')
@php
    $productsPayload = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'barcode' => $product->barcode,
        'price' => (float) $product->price,
        'compare_price' => (float) ($product->compare_price ?? 0),
        'quantity' => (float) $product->quantity,
        'reorder_level' => (float) ($product->reorder_level ?? 0),
        'category' => optional($product->category)->display_name,
        'image' => $product->image_url,
        'stocks' => $product->stocks->mapWithKeys(fn ($stock) => [(string) $stock->warehouse_id => (float) $stock->qty]),
    ])->values();
@endphp

<form action="{{ route('admin.pos.store') }}" method="POST" class="space-y-5" id="posForm">
    @csrf
    <input type="hidden" name="customer_mode" value="walkin">

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)]">
        <div class="space-y-5">
            <div class="card-premium overflow-hidden p-0">
                <div class="bg-gradient-to-br from-emerald-700 to-teal-500 p-5 text-white">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase text-white/70">Barcode Pharmacy POS</p>
                            <h2 class="mt-1 text-2xl font-black">ماسح باركود الدواء</h2>
                            <p class="mt-2 text-sm font-semibold text-white/75">اضرب الباركود أو اكتب SKU/اسم المنتج وسيظهر السعر والبيانات فوراً.</p>
                        </div>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black">بيع مباشر</span>
                    </div>
                </div>

                <div class="grid gap-4 p-4 lg:grid-cols-[1fr_auto]">
                    <div>
                        <label class="mb-2 block text-xs font-black text-slate-500">الباركود / SKU / اسم الدواء</label>
                        <div class="relative">
                            <input type="text" id="barcodeInput" class="input-premium h-14 pr-12 text-lg font-black" placeholder="امسح الباركود هنا..." autocomplete="off" autofocus>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-emerald-600">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5v14"/><path d="M8 5v14"/><path d="M12 5v14"/><path d="M17 5v14"/><path d="M21 5v14"/></svg>
                            </span>
                        </div>
                        <div id="scanMessage" class="mt-2 text-sm font-bold text-slate-500">جاهز للماسح. Enter يضيف المنتج للسلة.</div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" id="addScannedBtn" class="btn-primary h-14">إضافة للسلة</button>
                        <button type="button" id="clearCartBtn" class="btn-secondary h-14">تفريغ</button>
                    </div>
                </div>

                <div class="border-t border-slate-100 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-base font-black text-slate-950">إضافة يدوية بدون باركود</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">ابحث باسم الدواء أو SKU واختر المنتج ثم أضفه للسلة.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Manual Add</span>
                    </div>
                    <div class="grid gap-3 lg:grid-cols-[1fr_1.3fr_auto]">
                        <input type="text" id="manualProductSearch" class="input-premium" placeholder="فلترة المنتجات...">
                        <select id="manualProductSelect" class="select-premium">
                            <option value="">اختر الدواء يدوياً</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-search="{{ \Illuminate\Support\Str::lower($product->name . ' ' . ($product->sku ?? '') . ' ' . ($product->barcode ?? '')) }}">
                                    {{ $product->name }} | SKU: {{ $product->sku ?: '-' }} | BAR: {{ $product->barcode ?: '-' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="addManualBtn" class="btn-secondary">إضافة يدوي</button>
                    </div>
                </div>
            </div>

            <div id="productPreview" class="card-premium p-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center">
                    <div class="grid h-24 w-24 shrink-0 place-items-center rounded-3xl bg-emerald-50 text-emerald-700">
                        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-black text-slate-400">بيانات الدواء</p>
                        <h3 id="previewName" class="mt-1 text-xl font-black text-slate-950">لم يتم اختيار منتج</h3>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold text-slate-500">
                            <span id="previewSku" class="rounded-full bg-slate-100 px-2.5 py-1">SKU: -</span>
                            <span id="previewBarcode" class="rounded-full bg-slate-100 px-2.5 py-1">Barcode: -</span>
                            <span id="previewStock" class="rounded-full bg-slate-100 px-2.5 py-1">المخزون: -</span>
                        </div>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 text-center">
                        <p class="text-xs font-black text-slate-400">السعر</p>
                        <p id="previewPrice" class="mt-1 text-2xl font-black text-emerald-700">0.00 ج.م</p>
                    </div>
                </div>
            </div>

            <section class="card-premium p-4">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">سلة الطلب اليدوي</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">يمكن تعديل الكمية أو السعر قبل حفظ الطلب.</p>
                    </div>
                    <button type="button" id="focusScannerBtn" class="btn-secondary">رجوع للماسح</button>
                </div>

                <div class="table-wrap">
                    <table class="table-premium">
                        <thead>
                        <tr>
                            <th>الدواء</th>
                            <th>السعر</th>
                            <th>الكمية</th>
                            <th>الإجمالي</th>
                            <th>إجراء</th>
                        </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr id="emptyCartRow"><td colspan="5"><div class="empty-state">السلة فارغة. امسح باركود دواء لإضافته.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card-premium p-4">
                <h2 class="text-lg font-black text-slate-950">بيانات العميل</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">للطلب اليدوي نسجل الاسم ورقم الجوال فقط.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">اسم العميل</label>
                        <input type="text" name="customer_name" class="input-premium" placeholder="عميل نقدي">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">رقم الجوال</label>
                        <input type="text" name="customer_phone" class="input-premium" placeholder="01xxxxxxxxx">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">الفرع / المخزن</label>
                        <select name="warehouse_id" id="warehouseSelect" class="select-premium" required>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="card-premium p-4">
                <h2 class="text-lg font-black text-slate-950">الدفع والإجمالي</h2>
                <div class="mt-4 grid gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">طريقة الدفع</label>
                        <select name="payment_method" class="select-premium">
                            <option value="cash">كاش</option>
                            <option value="card">بطاقة</option>
                            <option value="transfer">تحويل</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-black text-slate-500">خصم</label>
                            <input type="number" step="0.01" min="0" name="discount" id="discountInput" value="0" class="input-premium">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-black text-slate-500">نسبة الضريبة</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="taxRateInput" value="0" class="input-premium pl-10">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <div class="flex justify-between text-sm font-bold text-slate-500"><span>الإجمالي الفرعي</span><span id="subtotalPreview">0.00 ج.م</span></div>
                        <div class="mt-2 flex justify-between text-sm font-bold text-slate-500"><span>قيمة الضريبة</span><span id="taxAmountPreview">0.00 ج.م</span></div>
                        <div class="mt-3 flex justify-between text-2xl font-black text-slate-950"><span>الصافي</span><span id="totalPreview">0.00 ج.م</span></div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">المبلغ المدفوع</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" class="input-premium" value="0">
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 text-sm font-black text-emerald-700">
                        الباقي / المتبقي: <span id="changePreview">0.00 ج.م</span>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">ملاحظات</label>
                        <input type="text" name="notes" class="input-premium" placeholder="ملاحظة على الطلب">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center text-base">حفظ الطلب اليدوي</button>
                </div>
            </section>

            <section class="card-premium p-4">
                <h2 class="mb-3 text-lg font-black text-slate-950">آخر عمليات الصيدلية</h2>
                <div class="space-y-2">
                    @forelse($recentSales as $sale)
                        <a href="{{ route('admin.pos.show', $sale) }}" class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-3 transition hover:border-emerald-200 hover:bg-emerald-50">
                            <span>
                                <span class="block text-sm font-black text-slate-900">{{ $sale->number }}</span>
                                <span class="text-xs font-bold text-slate-500">{{ $sale->warehouse?->name }}</span>
                            </span>
                            <span class="font-black text-emerald-700">{{ number_format((float) $sale->total, 2) }}</span>
                        </a>
                    @empty
                        <p class="text-sm font-semibold text-slate-500">لا توجد عمليات بعد.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </section>
</form>

<script>
(() => {
    const products = @json($productsPayload);
    const byId = new Map(products.map((product) => [String(product.id), product]));
    const barcodeInput = document.getElementById('barcodeInput');
    const addScannedBtn = document.getElementById('addScannedBtn');
    const manualProductSearch = document.getElementById('manualProductSearch');
    const manualProductSelect = document.getElementById('manualProductSelect');
    const addManualBtn = document.getElementById('addManualBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const focusScannerBtn = document.getElementById('focusScannerBtn');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const itemsBody = document.getElementById('itemsBody');
    const emptyCartRow = document.getElementById('emptyCartRow');
    const discountInput = document.getElementById('discountInput');
    const taxRateInput = document.getElementById('taxRateInput');
    const paidAmountInput = document.getElementById('paidAmountInput');
    const subtotalPreview = document.getElementById('subtotalPreview');
    const taxAmountPreview = document.getElementById('taxAmountPreview');
    const totalPreview = document.getElementById('totalPreview');
    const changePreview = document.getElementById('changePreview');
    const scanMessage = document.getElementById('scanMessage');
    const cart = new Map();
    let currentProduct = null;

    const money = (value) => `${Number(value || 0).toFixed(2)} ج.م`;
    const numberVal = (value) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    };
    const norm = (value) => String(value || '').trim().toLowerCase();
    const selectedWarehouse = () => String(warehouseSelect.value || '');
    const stockFor = (product) => Number(product?.stocks?.[selectedWarehouse()] ?? product?.quantity ?? 0);

    function findProduct(query) {
        const q = norm(query);
        if (!q) return null;
        return products.find((product) => {
            return norm(product.barcode) === q || norm(product.sku) === q || String(product.id) === q;
        }) || products.find((product) => {
            return norm(product.name).includes(q) || norm(product.barcode).includes(q) || norm(product.sku).includes(q);
        }) || null;
    }

    function setMessage(text, tone = 'muted') {
        scanMessage.textContent = text;
        scanMessage.className = 'mt-2 text-sm font-bold ' + (
            tone === 'error' ? 'text-rose-600' : tone === 'success' ? 'text-emerald-700' : 'text-slate-500'
        );
    }

    function preview(product) {
        currentProduct = product;
        document.getElementById('previewName').textContent = product ? product.name : 'لم يتم اختيار منتج';
        document.getElementById('previewSku').textContent = `SKU: ${product?.sku || '-'}`;
        document.getElementById('previewBarcode').textContent = `Barcode: ${product?.barcode || '-'}`;
        document.getElementById('previewStock').textContent = `المخزون: ${product ? stockFor(product) : '-'}`;
        document.getElementById('previewPrice').textContent = money(product?.price || 0);
    }

    function renderCart() {
        itemsBody.querySelectorAll('[data-cart-row]').forEach((row) => row.remove());
        emptyCartRow.classList.toggle('hidden', cart.size > 0);

        cart.forEach((item, productId) => {
            const product = byId.get(String(productId));
            const row = document.createElement('tr');
            row.dataset.cartRow = productId;
            row.innerHTML = `
                <td>
                    <input type="hidden" name="product_id[]" value="${product.id}">
                    <div class="font-black text-slate-950">${product.name}</div>
                    <div class="mt-1 text-xs font-semibold text-slate-400">SKU: ${product.sku || '-'} · Barcode: ${product.barcode || '-'}</div>
                </td>
                <td><input type="number" name="unit_price[]" step="0.01" min="0" class="input-premium unit-price" value="${Number(item.price).toFixed(2)}"></td>
                <td><input type="number" name="qty[]" step="0.01" min="0.01" class="input-premium qty" value="${Number(item.qty).toFixed(2)}"></td>
                <td class="font-black text-slate-900 line-total">${money(item.qty * item.price)}</td>
                <td><button type="button" class="btn-danger remove-row">حذف</button></td>
            `;

            row.querySelector('.unit-price').addEventListener('input', (event) => {
                item.price = numberVal(event.target.value);
                recalc();
            });
            row.querySelector('.qty').addEventListener('input', (event) => {
                item.qty = Math.max(0.01, numberVal(event.target.value));
                recalc();
            });
            row.querySelector('.remove-row').addEventListener('click', () => {
                cart.delete(productId);
                renderCart();
                recalc();
                barcodeInput.focus();
            });
            itemsBody.appendChild(row);
        });
        recalc();
    }

    function addProduct(product) {
        if (!product) {
            setMessage('لم يتم العثور على دواء بهذا الباركود أو الاسم.', 'error');
            return;
        }
        const available = stockFor(product);
        if (available <= 0) {
            preview(product);
            setMessage('المنتج موجود لكن لا يوجد مخزون في الفرع المحدد.', 'error');
            return;
        }

        const key = String(product.id);
        const item = cart.get(key) || { qty: 0, price: Number(product.price || 0) };
        if (item.qty + 1 > available) {
            preview(product);
            setMessage(`لا يمكن إضافة أكثر من المخزون المتاح (${available}).`, 'error');
            return;
        }
        item.qty += 1;
        item.price = Number(product.price || item.price || 0);
        cart.set(key, item);
        preview(product);
        renderCart();
        barcodeInput.value = '';
        barcodeInput.focus();
        setMessage(`تمت إضافة ${product.name} للسلة.`, 'success');
    }

    function recalc() {
        let subtotal = 0;
        itemsBody.querySelectorAll('[data-cart-row]').forEach((row) => {
            const productId = row.dataset.cartRow;
            const item = cart.get(productId);
            if (!item) return;
            const qty = numberVal(row.querySelector('.qty').value);
            const price = numberVal(row.querySelector('.unit-price').value);
            item.qty = qty;
            item.price = price;
            const line = qty * price;
            row.querySelector('.line-total').textContent = money(line);
            subtotal += line;
        });
        const discount = numberVal(discountInput.value);
        const taxableAmount = Math.max(0, subtotal - discount);
        const taxRate = numberVal(taxRateInput.value);
        const taxAmount = taxableAmount * (taxRate / 100);
        const total = Math.max(0, taxableAmount + taxAmount);
        const paid = numberVal(paidAmountInput.value);
        subtotalPreview.textContent = money(subtotal);
        taxAmountPreview.textContent = money(taxAmount);
        totalPreview.textContent = money(total);
        changePreview.textContent = money(paid - total);
        if (paid === 0 && total > 0) {
            paidAmountInput.value = total.toFixed(2);
            changePreview.textContent = money(0);
        }
    }

    barcodeInput.addEventListener('input', () => {
        const product = findProduct(barcodeInput.value);
        preview(product);
        if (barcodeInput.value.trim() && product) setMessage('تم العثور على الدواء. اضغط Enter للإضافة.', 'success');
        else if (barcodeInput.value.trim()) setMessage('لا يوجد تطابق حتى الآن.', 'error');
        else setMessage('جاهز للماسح. Enter يضيف المنتج للسلة.');
    });

    barcodeInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addProduct(findProduct(barcodeInput.value));
        }
    });

    addScannedBtn.addEventListener('click', () => addProduct(currentProduct || findProduct(barcodeInput.value)));
    addManualBtn.addEventListener('click', () => {
        const product = byId.get(String(manualProductSelect.value || ''));
        addProduct(product || null);
        manualProductSelect.value = '';
    });
    manualProductSelect.addEventListener('change', () => {
        preview(byId.get(String(manualProductSelect.value || '')) || null);
    });
    manualProductSearch.addEventListener('input', () => {
        const q = norm(manualProductSearch.value);
        Array.from(manualProductSelect.options).forEach((option, index) => {
            if (index === 0) return;
            option.hidden = q && !norm(option.dataset.search).includes(q);
        });
    });
    clearCartBtn.addEventListener('click', () => {
        cart.clear();
        renderCart();
        setMessage('تم تفريغ السلة.');
        barcodeInput.focus();
    });
    focusScannerBtn.addEventListener('click', () => barcodeInput.focus());
    warehouseSelect.addEventListener('change', () => {
        preview(currentProduct);
        renderCart();
    });
    discountInput.addEventListener('input', recalc);
    taxRateInput.addEventListener('input', recalc);
    paidAmountInput.addEventListener('input', recalc);

    document.getElementById('posForm').addEventListener('submit', (event) => {
        if (cart.size === 0) {
            event.preventDefault();
            setMessage('أضف دواء واحد على الأقل قبل حفظ الطلب.', 'error');
            barcodeInput.focus();
        }
    });

    preview(null);
    recalc();
})();
</script>
@endsection
