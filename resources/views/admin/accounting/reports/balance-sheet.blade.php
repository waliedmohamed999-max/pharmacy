@extends('admin.layouts.app')

@section('page-title', 'المركز المالي')
@section('page-subtitle', 'الأصول مقابل الالتزامات وحقوق الملكية حتى تاريخ محدد')

@section('content')
@php
    $difference = (float) $assetTotal - (float) $liabilitiesAndEquity;
    $balanced = abs($difference) < 0.005;
@endphp

<section class="rounded-[26px] border {{ $balanced ? 'border-emerald-100 from-emerald-50' : 'border-amber-100 from-amber-50' }} bg-gradient-to-l via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide {{ $balanced ? 'text-emerald-600' : 'text-amber-600' }}">Balance Sheet</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">المركز المالي</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">صورة مالية مختصرة حتى تاريخ {{ $filters['date_to'] ?: now()->toDateString() }}.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 text-right">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">الأصول</div><div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($assetTotal, 2) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">الالتزامات + الملكية</div><div class="mt-1 text-sm font-black text-slate-950">{{ number_format($liabilitiesAndEquity, 2) }}</div></div>
            <div class="rounded-2xl border {{ $balanced ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3"><div class="text-xs font-bold">الفرق</div><div class="mt-1 text-sm font-black">{{ number_format($difference, 2) }}</div></div>
        </div>
    </div>
</section>

<form class="card-premium grid gap-3 p-5 md:grid-cols-[1fr_auto_auto]">
    <label class="grid gap-1"><span class="text-xs font-black text-slate-500">حتى تاريخ</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium"></label>
    <button class="btn-primary self-end">تطبيق</button>
    <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn-secondary self-end text-center">إعادة</a>
</form>

<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.accounting.reports.balance-sheet.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.balance-sheet.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
</div>

<div class="grid gap-4 xl:grid-cols-2">
    <section class="card-premium overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-slate-950">الأصول</h2></div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>الرصيد</th></tr></thead>
                <tbody>
                @forelse($assets as $row)
                    <tr><td class="font-black">{{ $row->code }}</td><td>{{ $row->name }}</td><td class="font-black text-emerald-700">{{ number_format($row->balance, 2) }}</td></tr>
                @empty
                    <tr><td colspan="3"><div class="empty-state">لا توجد أصول.</div></td></tr>
                @endforelse
                <tr><th colspan="2">الإجمالي</th><th>{{ number_format($assetTotal, 2) }}</th></tr>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card-premium overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-slate-950">الالتزامات وحقوق الملكية</h2></div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>الرصيد</th></tr></thead>
                <tbody>
                @foreach($liabilities as $row)
                    <tr><td class="font-black">{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->balance, 2) }}</td></tr>
                @endforeach
                @foreach($equity as $row)
                    <tr><td class="font-black">{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->balance, 2) }}</td></tr>
                @endforeach
                @if($liabilities->isEmpty() && $equity->isEmpty())
                    <tr><td colspan="3"><div class="empty-state">لا توجد التزامات أو حقوق ملكية.</div></td></tr>
                @endif
                <tr><th colspan="2">الإجمالي</th><th>{{ number_format($liabilitiesAndEquity, 2) }}</th></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
