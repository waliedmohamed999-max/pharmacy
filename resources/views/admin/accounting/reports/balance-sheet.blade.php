@extends('admin.layouts.app')

@section('page-title', 'المركز المالي')
@section('page-subtitle', 'الأصول مقابل الالتزامات وحقوق الملكية')

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-3 gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">حتى تاريخ</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-premium">
    </div>
    <div class="md:col-span-2 flex items-end gap-2">
        <button class="btn-primary">تطبيق</button>
        <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn-secondary">إعادة</a>
    </div>
</form>

<div class="card-premium p-4 mb-4 flex gap-2">
    <a href="{{ route('admin.accounting.reports.balance-sheet.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.balance-sheet.pdf', request()->query()) }}" class="btn-secondary">PDF</a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <section class="card-premium p-4">
        <h3 class="font-black mb-3">الأصول</h3>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>الرصيد</th></tr></thead>
                <tbody>
                @foreach($assets as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->balance, 2) }}</td></tr>
                @endforeach
                <tr><th colspan="2">الإجمالي</th><th>{{ number_format($assetTotal, 2) }}</th></tr>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card-premium p-4">
        <h3 class="font-black mb-3">الالتزامات وحقوق الملكية</h3>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الكود</th><th>الحساب</th><th>الرصيد</th></tr></thead>
                <tbody>
                @foreach($liabilities as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->balance, 2) }}</td></tr>
                @endforeach
                @foreach($equity as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ number_format($row->balance, 2) }}</td></tr>
                @endforeach
                <tr><th colspan="2">الإجمالي</th><th>{{ number_format($liabilitiesAndEquity, 2) }}</th></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
