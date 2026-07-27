@extends('admin.layouts.app')

@section('page-title', 'التدفقات النقدية')
@section('page-subtitle', 'حركات الصندوق والبنك خلال فترة محددة')

@section('content')
<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Cash Flow</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">التدفقات النقدية</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">تحليل الداخل والخارج وصافي النقدية للصندوق والبنك.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-right">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">داخل</div><div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($cashIn, 2) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">خارج</div><div class="mt-1 text-sm font-black text-rose-700">{{ number_format($cashOut, 2) }}</div></div>
            <div class="rounded-2xl border {{ $netCash >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} px-4 py-3"><div class="text-xs font-bold">الصافي</div><div class="mt-1 text-sm font-black">{{ number_format($netCash, 2) }}</div></div>
        </div>
    </div>
</section>

<form class="card-premium grid gap-3 p-5 md:grid-cols-[1fr_1fr_auto_auto]">
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">من تاريخ</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-premium"></label>
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">إلى تاريخ</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium"></label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.reports.cash-flow') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.reports.cash-flow.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.cash-flow.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
</div>

<section class="card-premium overflow-hidden p-0">
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>التاريخ</th><th>رقم القيد</th><th>الوصف</th><th>الحساب</th><th>داخل</th><th>خارج</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->entry_date }}</td>
                    <td class="font-black text-slate-800">{{ $row->number }}</td>
                    <td>{{ $row->description ?: '-' }}</td>
                    <td>{{ $row->code }} - {{ $row->name }}</td>
                    <td class="font-black text-emerald-700">{{ number_format($row->debit, 2) }}</td>
                    <td class="font-black text-rose-700">{{ number_format($row->credit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد حركات نقدية.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
