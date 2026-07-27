@extends('admin.layouts.app')

@section('page-title', 'المدفوعات والتحصيلات')
@section('page-subtitle', 'سجل حركات النقدية والبنك مع الترحيل المحاسبي وربط الفواتير')

@section('page-actions')
<a href="{{ route('admin.accounting.payments.create') }}" class="btn-primary">عملية سداد/تحصيل جديدة</a>
@endsection

@section('content')
<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Payments Register</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">سجل السداد والتحصيل</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">تابع الداخل والخارج وصافي الحركة مع فلاتر سريعة حسب التاريخ والجهة والحساب.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">عدد العمليات</div>
                <div class="mt-1 text-sm font-black text-slate-950">{{ number_format($summary['count']) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">تحصيل</div>
                <div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($summary['cash_in'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">سداد</div>
                <div class="mt-1 text-sm font-black text-rose-700">{{ number_format($summary['cash_out'], 2) }}</div>
            </div>
            <div class="rounded-2xl border {{ $summary['net'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} px-4 py-3">
                <div class="text-xs font-bold text-slate-500">الصافي</div>
                <div class="mt-1 text-sm font-black {{ $summary['net'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format($summary['net'], 2) }}</div>
            </div>
        </div>
    </div>
</section>

<form method="GET" class="card-premium grid gap-3 p-5 lg:grid-cols-[1fr_1fr_1fr_1fr_1fr_auto_auto]">
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">من تاريخ</span>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="input-premium">
    </label>
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">إلى تاريخ</span>
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="input-premium">
    </label>
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">نوع العملية</span>
        <select name="direction" class="select-premium">
            <option value="">الكل</option>
            <option value="in" @selected(($filters['direction'] ?? '') === 'in')>تحصيل</option>
            <option value="out" @selected(($filters['direction'] ?? '') === 'out')>سداد</option>
        </select>
    </label>
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">الجهة</span>
        <select name="contact_id" class="select-premium">
            <option value="">كل الجهات</option>
            @foreach($contacts as $contact)
                <option value="{{ $contact->id }}" @selected((string) ($filters['contact_id'] ?? '') === (string) $contact->id)>{{ $contact->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">الحساب</span>
        <select name="account_id" class="select-premium">
            <option value="">كل الحسابات</option>
            @foreach($cashAccounts as $account)
                <option value="{{ $account->id }}" @selected((string) ($filters['account_id'] ?? '') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
            @endforeach
        </select>
    </label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.payments.index') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<section class="card-premium overflow-hidden p-0">
    <div class="border-b border-slate-100 p-5">
        <h2 class="text-xl font-black text-slate-950">الحركات المسجلة</h2>
    </div>
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الرقم</th>
                <th>التاريخ</th>
                <th>النوع</th>
                <th>الجهة</th>
                <th>الحساب النقدي</th>
                <th>الطريقة</th>
                <th>المبلغ</th>
                <th>المرجع</th>
                <th>ملاحظات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                @php
                    $referenceLabel = match ($payment->reference_type) {
                        'sales_invoice' => 'فاتورة مبيعات',
                        'purchase_invoice' => 'فاتورة مشتريات',
                        default => null,
                    };
                @endphp
                <tr>
                    <td class="font-black text-slate-900">{{ $payment->number }}</td>
                    <td>{{ optional($payment->payment_date)->format('Y-m-d') }}</td>
                    <td>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $payment->direction === 'in' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ $payment->direction === 'in' ? 'تحصيل' : 'سداد' }}
                        </span>
                    </td>
                    <td>{{ $payment->contact?->name ?: '-' }}</td>
                    <td>{{ $payment->account?->code }} - {{ $payment->account?->name }}</td>
                    <td>{{ $payment->method ?: '-' }}</td>
                    <td class="font-black {{ $payment->direction === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $referenceLabel ? $referenceLabel . ' #' . $payment->reference_id : '-' }}</td>
                    <td class="max-w-xs truncate">{{ $payment->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty-state">لا توجد عمليات سداد أو تحصيل.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $payments->links() }}</div>
</section>
@endsection
