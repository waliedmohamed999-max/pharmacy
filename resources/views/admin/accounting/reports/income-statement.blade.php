@extends('admin.layouts.app')

@section('page-title', 'قائمة الدخل')
@section('page-subtitle', 'الإيرادات والمصروفات وصافي الربح خلال الفترة')

@section('content')
@php
    $margin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
@endphp

<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Income Statement</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">قائمة الدخل</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">تحليل الإيرادات والمصروفات وصافي الربح.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-right">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">الإيرادات</div><div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($totalRevenue, 2) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">المصروفات</div><div class="mt-1 text-sm font-black text-rose-700">{{ number_format($totalExpense, 2) }}</div></div>
            <div class="rounded-2xl border {{ $netProfit >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} px-4 py-3"><div class="text-xs font-bold">صافي الربح</div><div class="mt-1 text-sm font-black">{{ number_format($netProfit, 2) }}</div></div>
        </div>
    </div>
</section>

<form class="card-premium grid gap-3 p-5 md:grid-cols-[1fr_1fr_auto_auto]">
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">من تاريخ</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-premium"></label>
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">إلى تاريخ</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium"></label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.reports.income-statement') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.reports.income-statement.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.income-statement.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
    <span class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-black text-slate-600">هامش الربح: {{ number_format($margin, 1) }}%</span>
</div>

<div class="grid gap-4 xl:grid-cols-2">
    <section class="card-premium overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-slate-950">الإيرادات</h2></div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
                <tbody>
                @forelse($revenues as $row)
                    <tr><td class="font-black">{{ $row->code }}</td><td>{{ $row->name }}</td><td class="font-black text-emerald-700">{{ number_format($row->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="3"><div class="empty-state">لا توجد إيرادات.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="card-premium overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-slate-950">المصروفات</h2></div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
                <tbody>
                @forelse($expenses as $row)
                    <tr><td class="font-black">{{ $row->code }}</td><td>{{ $row->name }}</td><td class="font-black text-rose-700">{{ number_format($row->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="3"><div class="empty-state">لا توجد مصروفات.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
