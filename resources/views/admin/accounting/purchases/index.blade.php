@extends('admin.layouts.app')

@section('page-title', 'فواتير المشتريات')
@section('page-subtitle', 'متابعة فواتير الموردين، ضريبة القيمة المضافة، وQR زاتكا')

@section('page-actions')
<a href="{{ route('admin.accounting.purchases.create') }}" class="btn-primary">فاتورة مشتريات جديدة</a>
@endsection

@section('content')
<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الرقم</th>
                <th>المورد</th>
                <th>المخزن</th>
                <th>التاريخ</th>
                <th>الإجمالي</th>
                <th>الضريبة</th>
                <th>زاتكا</th>
                <th>المسدد</th>
                <th>المتبقي</th>
                <th>الحالة</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td class="font-black">{{ $invoice->number }}</td>
                    <td>{{ $invoice->contact?->name }}</td>
                    <td>{{ $invoice->warehouse?->name ?: '-' }}</td>
                    <td>{{ optional($invoice->invoice_date)->format('Y-m-d') }}</td>
                    <td>{{ number_format($invoice->total, 2) }}</td>
                    <td>{{ number_format($invoice->tax, 2) }}</td>
                    <td>
                        <span class="{{ $invoice->zatca_qr_payload ? 'badge-success' : 'badge-warning' }}">
                            {{ $invoice->zatca_qr_payload ? 'QR جاهز' : 'غير جاهز' }}
                        </span>
                    </td>
                    <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td>{{ number_format($invoice->balance, 2) }}</td>
                    <td>{{ $invoice->status }}</td>
                    <td>
                        <a href="{{ route('admin.accounting.purchases.show', $invoice) }}" class="btn-secondary text-xs">عرض</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11"><div class="empty-state">لا توجد فواتير مشتريات.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
</section>
@endsection
