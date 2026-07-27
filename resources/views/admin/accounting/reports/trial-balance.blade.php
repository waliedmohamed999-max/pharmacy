@extends('admin.layouts.app')

@section('page-title', 'ميزان المراجعة التفصيلي')
@section('page-subtitle', 'إجماليات كل حساب: مدين، دائن، وصافي الحركة')

@section('content')
@php
    $difference = (float) $totals['debit'] - (float) $totals['credit'];
    $balanced = abs($difference) < 0.005;
@endphp

<section class="rounded-[26px] border {{ $balanced ? 'border-emerald-100 from-emerald-50' : 'border-amber-100 from-amber-50' }} bg-gradient-to-l via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide {{ $balanced ? 'text-emerald-600' : 'text-amber-600' }}">Trial Balance</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">ميزان المراجعة</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">مطابقة إجمالي المدين والدائن خلال الفترة المحددة.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-right">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">إجمالي المدين</div>
                <div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($totals['debit'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">إجمالي الدائن</div>
                <div class="mt-1 text-sm font-black text-rose-700">{{ number_format($totals['credit'], 2) }}</div>
            </div>
            <div class="rounded-2xl border {{ $balanced ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3">
                <div class="text-xs font-bold {{ $balanced ? 'text-emerald-700' : 'text-amber-700' }}">الفرق</div>
                <div class="mt-1 text-sm font-black {{ $balanced ? 'text-emerald-700' : 'text-amber-700' }}">{{ number_format($difference, 2) }}</div>
            </div>
        </div>
    </div>
</section>

<form class="card-premium grid gap-3 p-5 md:grid-cols-[1.3fr_1fr_1fr_auto_auto]">
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">الحساب</span>
        <select name="account_id" class="select-premium">
            <option value="">كل الحسابات</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected((int) $filters['account_id'] === (int) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">من تاريخ</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-premium"></label>
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">إلى تاريخ</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium"></label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.reports.trial-balance') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.reports.trial-balance.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.trial-balance.pdf', request()->query()) }}" target="_blank" class="btn-secondary">PDF</a>
</div>

<section class="card-premium overflow-hidden p-0">
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>الكود</th><th>الحساب</th><th>النوع</th><th>مدين</th><th>دائن</th><th>الصافي</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php($net = (float) $row->total_debit - (float) $row->total_credit)
                <tr>
                    <td class="font-black text-slate-800">{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->type }}</td>
                    <td class="font-black text-emerald-700">{{ number_format($row->total_debit, 2) }}</td>
                    <td class="font-black text-rose-700">{{ number_format($row->total_credit, 2) }}</td>
                    <td class="font-black {{ $net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format($net, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد بيانات.</div></td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">الإجمالي</th>
                    <th>{{ number_format($totals['debit'], 2) }}</th>
                    <th>{{ number_format($totals['credit'], 2) }}</th>
                    <th>{{ number_format($difference, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="p-4">{{ $rows->links() }}</div>
</section>
@endsection
