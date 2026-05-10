@extends('admin.layouts.app')

@section('page-title', 'تسجيل سداد/تحصيل')
@section('page-subtitle', 'إثبات حركة نقدية وربطها بالعميل/المورد والفاتورة')

@section('content')
<form action="{{ route('admin.accounting.payments.store') }}" method="POST" class="card-premium p-4 grid md:grid-cols-2 gap-3">
    @csrf
    <div>
        <label class="block text-xs text-slate-500 mb-1">التاريخ</label>
        <input type="date" name="payment_date" class="input-premium" value="{{ date('Y-m-d') }}" required>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">نوع العملية</label>
        <select name="direction" class="select-premium" required>
            <option value="in">تحصيل من عميل</option>
            <option value="out">سداد لمورد</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">الجهة</label>
        <select name="contact_id" class="select-premium">
            <option value="">بدون</option>
            @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->type }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">حساب النقدية/البنك</label>
        <select name="account_id" class="select-premium" required>
            @foreach($cashAccounts as $a)
                <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">المبلغ</label>
        <input type="number" step="0.01" min="0.01" name="amount" class="input-premium" required>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">طريقة الدفع</label>
        <input type="text" name="method" class="input-premium" placeholder="نقدي / تحويل بنكي">
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">نوع المرجع</label>
        <select name="reference_type" class="select-premium">
            <option value="">بدون</option>
            <option value="sales_invoice">فاتورة مبيعات</option>
            <option value="purchase_invoice">فاتورة مشتريات</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">رقم المرجع (ID الفاتورة)</label>
        <input type="number" min="1" name="reference_id" class="input-premium" placeholder="مثال: 12">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs text-slate-500 mb-1">ملاحظات</label>
        <textarea name="notes" rows="3" class="input-premium"></textarea>
    </div>
    <div class="md:col-span-2 flex justify-end">
        <button class="btn-primary">حفظ العملية</button>
    </div>
</form>

<section class="card-premium p-4 mt-4">
    <h3 class="font-black mb-2">فواتير عليها رصيد (للرجوع السريع)</h3>
    <div class="grid md:grid-cols-2 gap-3 text-sm">
        <div>
            <div class="font-semibold mb-1">مبيعات</div>
            <div class="max-h-40 overflow-auto space-y-1">
                @forelse($salesInvoices as $inv)
                    <div>#{{ $inv->id }} - {{ $inv->number }} - متبقي {{ number_format($inv->balance, 2) }}</div>
                @empty
                    <div class="text-slate-500">لا يوجد</div>
                @endforelse
            </div>
        </div>
        <div>
            <div class="font-semibold mb-1">مشتريات</div>
            <div class="max-h-40 overflow-auto space-y-1">
                @forelse($purchaseInvoices as $inv)
                    <div>#{{ $inv->id }} - {{ $inv->number }} - متبقي {{ number_format($inv->balance, 2) }}</div>
                @empty
                    <div class="text-slate-500">لا يوجد</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
