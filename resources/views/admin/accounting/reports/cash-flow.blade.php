@extends('admin.layouts.app')

@section('page-title', 'التدفقات النقدية')
@section('page-subtitle', 'حركات الصندوق والبنك خلال فترة')

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
        <a href="{{ route('admin.accounting.reports.cash-flow') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<div class="card-premium p-4 mb-4 flex gap-2">
    <a href="{{ route('admin.accounting.reports.cash-flow.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.cash-flow.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
    <div class="kpi-card"><div class="text-sm text-slate-500">التدفقات الداخلة</div><div class="kpi-value">{{ number_format($cashIn, 2) }}</div></div>
    <div class="kpi-card"><div class="text-sm text-slate-500">التدفقات الخارجة</div><div class="kpi-value">{{ number_format($cashOut, 2) }}</div></div>
    <div class="kpi-card"><div class="text-sm text-slate-500">صافي النقدية</div><div class="kpi-value">{{ number_format($netCash, 2) }}</div></div>
</div>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
                <tr><th>التاريخ</th><th>رقم القيد</th><th>الوصف</th><th>الحساب</th><th>داخل</th><th>خارج</th></tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->entry_date }}</td>
                    <td>{{ $row->number }}</td>
                    <td>{{ $row->description }}</td>
                    <td>{{ $row->code }} - {{ $row->name }}</td>
                    <td>{{ number_format($row->debit, 2) }}</td>
                    <td>{{ number_format($row->credit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد حركات نقدية.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
