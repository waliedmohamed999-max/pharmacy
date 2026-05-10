@extends('admin.layouts.app')

@section('page-title', 'قائمة الدخل')
@section('page-subtitle', 'الإيرادات والمصروفات وصافي الربح')

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-3 gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">من تاريخ</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-premium">
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">إلى تاريخ</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium">
    </div>
    <div class="flex items-end gap-2">
        <button class="btn-primary w-full">تطبيق</button>
        <a href="{{ route('admin.accounting.reports.income-statement') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<div class="card-premium p-4 mb-4 flex gap-2">
    <a href="{{ route('admin.accounting.reports.income-statement.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.income-statement.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
    <section class="card-premium p-4">
        <h3 class="font-black mb-3">الإيرادات</h3>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
                <tbody>
                @foreach($revenues as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->amount, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
    <section class="card-premium p-4">
        <h3 class="font-black mb-3">المصروفات</h3>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
                <tbody>
                @foreach($expenses as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->amount, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card-premium p-4">
    <div class="grid md:grid-cols-3 gap-3">
        <div class="kpi-card"><div class="text-sm text-slate-500">إجمالي الإيرادات</div><div class="kpi-value">{{ number_format($totalRevenue, 2) }}</div></div>
        <div class="kpi-card"><div class="text-sm text-slate-500">إجمالي المصروفات</div><div class="kpi-value">{{ number_format($totalExpense, 2) }}</div></div>
        <div class="kpi-card"><div class="text-sm text-slate-500">صافي الربح</div><div class="kpi-value">{{ number_format($netProfit, 2) }}</div></div>
    </div>
</section>
@endsection
