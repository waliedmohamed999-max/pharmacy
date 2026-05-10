@extends('admin.layouts.app')

@section('page-title', 'فواتير المبيعات')
@section('page-subtitle', 'متابعة فواتير العملاء وأرصدة التحصيل')

@section('page-actions')
<a href="{{ route('admin.accounting.sales.create') }}" class="btn-primary">فاتورة مبيعات جديدة</a>
@endsection

@section('content')
<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الرقم</th>
                <th>العميل</th>
                <th>المخزن</th>
                <th>التاريخ</th>
                <th>الإجمالي</th>
                <th>المسدّد</th>
                <th>المتبقي</th>
                <th>الحالة</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->contact?->name }}</td>
                    <td>{{ $invoice->warehouse?->name ?: '-' }}</td>
                    <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                    <td>{{ number_format($invoice->total, 2) }}</td>
                    <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td>{{ number_format($invoice->balance, 2) }}</td>
                    <td>{{ $invoice->status }}</td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state">لا توجد فواتير مبيعات.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
</section>
@endsection
