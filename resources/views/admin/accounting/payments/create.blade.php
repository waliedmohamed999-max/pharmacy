@extends('admin.layouts.app')

@section('page-title', 'تسجيل سداد/تحصيل')
@section('page-subtitle', 'إثبات حركة نقدية وربطها بالعميل أو المورد والفاتورة')

@section('content')
@php
    $salesPayload = $salesInvoices->map(fn ($invoice) => [
        'id' => $invoice->id,
        'number' => $invoice->number,
        'contact_id' => $invoice->contact_id,
        'contact_name' => optional($invoice->contact)->name,
        'date' => optional($invoice->invoice_date)->format('Y-m-d'),
        'total' => (float) $invoice->total,
        'balance' => (float) $invoice->balance,
    ])->values();

    $purchasePayload = $purchaseInvoices->map(fn ($invoice) => [
        'id' => $invoice->id,
        'number' => $invoice->number,
        'contact_id' => $invoice->contact_id,
        'contact_name' => optional($invoice->contact)->name,
        'date' => optional($invoice->invoice_date)->format('Y-m-d'),
        'total' => (float) $invoice->total,
        'balance' => (float) $invoice->balance,
    ])->values();
@endphp

<form id="payment-form" action="{{ route('admin.accounting.payments.store') }}" method="POST" class="space-y-5">
    @csrf

    <section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="text-right">
                <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Cash Movement</div>
                <h1 class="mt-1 text-3xl font-black text-slate-950">تسجيل حركة مالية</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">اختر نوع العملية والفاتورة، وسيتم تجهيز الطرف والمبلغ المتبقي تلقائياً.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">العملية</div>
                    <div id="summary-direction" class="mt-1 text-sm font-black text-slate-900">تحصيل</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">المبلغ</div>
                    <div id="summary-amount" class="mt-1 text-sm font-black text-emerald-700">0.00</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">المرجع</div>
                    <div id="summary-reference" class="mt-1 text-sm font-black text-slate-900">بدون</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div class="text-xs font-bold text-slate-500">الحساب</div>
                    <div id="summary-account" class="mt-1 truncate text-sm font-black text-slate-900">-</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="grid gap-4 xl:grid-cols-[1.1fr_1.1fr_.9fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h2 class="mb-4 text-right text-lg font-black text-slate-950">بيانات العملية</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">التاريخ</label>
                        <input type="date" name="payment_date" class="input-premium" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">نوع العملية</label>
                        <select id="direction" name="direction" class="select-premium" required>
                            <option value="in" @selected(old('direction') === 'in')>تحصيل من عميل</option>
                            <option value="out" @selected(old('direction') === 'out')>سداد لمورد</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-black text-slate-500">الجهة</label>
                        <select id="contact-id" name="contact_id" class="select-premium">
                            <option value="">بدون جهة محددة</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" @selected((string) old('contact_id') === (string) $contact->id)>
                                    {{ $contact->name }} - {{ $contact->type === 'customer' ? 'عميل' : 'مورد' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">المبلغ</label>
                        <input id="amount" type="number" step="0.01" min="0.01" name="amount" class="input-premium" value="{{ old('amount') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">طريقة الدفع</label>
                        <select id="method" name="method" class="select-premium">
                            <option value="نقدي" @selected(old('method') === 'نقدي')>نقدي</option>
                            <option value="تحويل بنكي" @selected(old('method') === 'تحويل بنكي')>تحويل بنكي</option>
                            <option value="مدى" @selected(old('method') === 'مدى')>مدى</option>
                            <option value="شيك" @selected(old('method') === 'شيك')>شيك</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <h2 class="mb-4 text-right text-lg font-black text-slate-950">ربط الفاتورة</h2>
                <div class="grid gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">نوع المرجع</label>
                        <select id="reference-type" name="reference_type" class="select-premium">
                            <option value="">بدون مرجع</option>
                            <option value="sales_invoice" @selected(old('reference_type') === 'sales_invoice')>فاتورة مبيعات</option>
                            <option value="purchase_invoice" @selected(old('reference_type') === 'purchase_invoice')>فاتورة مشتريات</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black text-slate-500">الفاتورة</label>
                        <select id="reference-id" name="reference_id" class="select-premium">
                            <option value="">اختر فاتورة مفتوحة</option>
                        </select>
                    </div>
                    <div id="invoice-preview" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-right text-sm text-slate-500">
                        لم يتم اختيار فاتورة بعد.
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                <h2 class="mb-4 text-right text-lg font-black text-slate-950">حساب النقدية/البنك</h2>
                <select id="account-id" name="account_id" class="select-premium" required>
                    @foreach($cashAccounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>
                            {{ $account->code }} - {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                <div class="mt-4 rounded-2xl bg-white p-4 text-right text-sm font-semibold leading-7 text-slate-600">
                    عند الحفظ سيتم إنشاء حركة الدفع، ترحيل القيد المحاسبي، وتحديث رصيد الفاتورة المرتبطة إن وجدت.
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1 block text-xs font-black text-slate-500">ملاحظات</label>
            <textarea name="notes" rows="3" class="input-premium">{{ old('notes') }}</textarea>
        </div>

        <div class="mt-5 flex justify-end">
            <button class="btn-primary px-7">حفظ العملية</button>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 flex flex-col gap-1 text-right">
            <h2 class="text-xl font-black text-slate-950">فواتير عليها رصيد</h2>
            <p class="text-sm font-semibold text-slate-500">استخدم الاختيار السريع لربط العملية بالفاتورة وتعبئة البيانات.</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <div class="mb-2 text-right text-sm font-black text-slate-700">مبيعات</div>
                <div class="grid max-h-72 gap-2 overflow-auto pr-1">
                    @forelse($salesInvoices as $invoice)
                        <button type="button" class="quick-invoice rounded-2xl border border-slate-200 bg-white p-3 text-right transition hover:border-emerald-300 hover:bg-emerald-50" data-type="sales_invoice" data-id="{{ $invoice->id }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-black text-emerald-600">متبقي {{ number_format($invoice->balance, 2) }}</span>
                                <span class="font-black text-slate-950">{{ $invoice->number }}</span>
                            </div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ optional($invoice->contact)->name ?? 'بدون عميل' }} · إجمالي {{ number_format($invoice->total, 2) }}</div>
                        </button>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm font-semibold text-slate-500">لا توجد فواتير مبيعات مفتوحة</div>
                    @endforelse
                </div>
            </div>
            <div>
                <div class="mb-2 text-right text-sm font-black text-slate-700">مشتريات</div>
                <div class="grid max-h-72 gap-2 overflow-auto pr-1">
                    @forelse($purchaseInvoices as $invoice)
                        <button type="button" class="quick-invoice rounded-2xl border border-slate-200 bg-white p-3 text-right transition hover:border-emerald-300 hover:bg-emerald-50" data-type="purchase_invoice" data-id="{{ $invoice->id }}">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-black text-emerald-600">متبقي {{ number_format($invoice->balance, 2) }}</span>
                                <span class="font-black text-slate-950">{{ $invoice->number }}</span>
                            </div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ optional($invoice->contact)->name ?? 'بدون مورد' }} · إجمالي {{ number_format($invoice->total, 2) }}</div>
                        </button>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm font-semibold text-slate-500">لا توجد فواتير مشتريات مفتوحة</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</form>

<script>
    (() => {
        const invoices = {
            sales_invoice: @json($salesPayload),
            purchase_invoice: @json($purchasePayload),
        };

        const direction = document.getElementById('direction');
        const contact = document.getElementById('contact-id');
        const amount = document.getElementById('amount');
        const account = document.getElementById('account-id');
        const referenceType = document.getElementById('reference-type');
        const referenceId = document.getElementById('reference-id');
        const preview = document.getElementById('invoice-preview');
        const summaryDirection = document.getElementById('summary-direction');
        const summaryAmount = document.getElementById('summary-amount');
        const summaryReference = document.getElementById('summary-reference');
        const summaryAccount = document.getElementById('summary-account');

        const money = (value) => Number(value || 0).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const selectedInvoice = () => (invoices[referenceType.value] || []).find((invoice) => String(invoice.id) === String(referenceId.value));

        const fillReferences = (selected = '') => {
            referenceId.innerHTML = '<option value="">اختر فاتورة مفتوحة</option>';
            (invoices[referenceType.value] || []).forEach((invoice) => {
                const option = document.createElement('option');
                option.value = invoice.id;
                option.textContent = `${invoice.number} - ${invoice.contact_name || 'بدون جهة'} - متبقي ${money(invoice.balance)}`;
                option.selected = String(invoice.id) === String(selected);
                referenceId.appendChild(option);
            });
        };

        const updateSummary = () => {
            const invoice = selectedInvoice();
            summaryDirection.textContent = direction.value === 'in' ? 'تحصيل' : 'سداد';
            summaryAmount.textContent = money(amount.value);
            summaryReference.textContent = invoice ? invoice.number : 'بدون';
            summaryAccount.textContent = account.options[account.selectedIndex]?.textContent?.trim() || '-';
        };

        const syncFromInvoice = () => {
            const invoice = selectedInvoice();
            if (!invoice) {
                preview.textContent = 'لم يتم اختيار فاتورة بعد.';
                updateSummary();
                return;
            }
            if (invoice.contact_id) contact.value = invoice.contact_id;
            amount.value = Number(invoice.balance || 0).toFixed(2);
            preview.innerHTML = `
                <div class="font-black text-slate-950">${invoice.number}</div>
                <div class="mt-1 font-semibold text-slate-600">${invoice.contact_name || 'بدون جهة'}</div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-white p-3"><div class="text-xs text-slate-500">الإجمالي</div><div class="font-black text-slate-900">${money(invoice.total)}</div></div>
                    <div class="rounded-xl bg-white p-3"><div class="text-xs text-slate-500">المتبقي</div><div class="font-black text-emerald-700">${money(invoice.balance)}</div></div>
                </div>
            `;
            updateSummary();
        };

        referenceType.addEventListener('change', () => {
            if (referenceType.value === 'sales_invoice') direction.value = 'in';
            if (referenceType.value === 'purchase_invoice') direction.value = 'out';
            fillReferences();
            syncFromInvoice();
        });
        referenceId.addEventListener('change', syncFromInvoice);
        [direction, contact, amount, account].forEach((element) => element.addEventListener('input', updateSummary));

        document.querySelectorAll('.quick-invoice').forEach((button) => {
            button.addEventListener('click', () => {
                referenceType.value = button.dataset.type;
                direction.value = button.dataset.type === 'sales_invoice' ? 'in' : 'out';
                fillReferences(button.dataset.id);
                syncFromInvoice();
                document.getElementById('payment-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        fillReferences(@json(old('reference_id', '')));
        syncFromInvoice();
        updateSummary();
    })();
</script>
@endsection
