@extends('admin.layouts.app')

@section('page-title', 'دفتر الأستاذ')
@section('page-subtitle', 'تفصيل كل الحركات المدينة والدائنة حسب الحساب والفترة')

@section('content')
@php
    $pageDebit = (float) collect($lines->items())->sum('debit');
    $pageCredit = (float) collect($lines->items())->sum('credit');
@endphp

<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">General Ledger</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">دفتر الأستاذ</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">راجع الحركات حسب الحساب والفترة مع تصدير Excel أو PDF.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-right">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">مدين الصفحة</div>
                <div class="mt-1 text-sm font-black text-slate-950">{{ number_format($pageDebit, 2) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">دائن الصفحة</div>
                <div class="mt-1 text-sm font-black text-slate-950">{{ number_format($pageCredit, 2) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-xs font-bold text-slate-500">الصافي</div>
                <div class="mt-1 text-sm font-black {{ ($pageDebit - $pageCredit) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format($pageDebit - $pageCredit, 2) }}</div>
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
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">من تاريخ</span>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-premium">
    </label>
    <label class="grid gap-1">
        <span class="text-xs font-black text-slate-500">إلى تاريخ</span>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium">
    </label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.reports.ledger') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.reports.ledger.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.ledger.pdf', request()->query()) }}" target="_blank" class="btn-secondary">PDF</a>
</div>

<section class="card-premium overflow-hidden p-0">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>التاريخ</th>
                <th>رقم القيد</th>
                <th>الحساب</th>
                <th>الجهة</th>
                <th>الوصف</th>
                <th>مدين</th>
                <th>دائن</th>
            </tr>
            </thead>
            <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ $line->entry_date }}</td>
                    <td class="font-black text-slate-800">{{ $line->entry_number }}</td>
                    <td>{{ $line->account_code }} - {{ $line->account_name }}</td>
                    <td>{{ $line->contact_name ?: '-' }}</td>
                    <td>{{ $line->line_description ?: $line->entry_description ?: '-' }}</td>
                    <td class="font-black text-emerald-700">{{ number_format($line->debit, 2) }}</td>
                    <td class="font-black text-rose-700">{{ number_format($line->credit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد حركات.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $lines->links() }}</div>
</section>
@endsection
