@extends('admin.layouts.app')

@section('page-title', 'المدفوعات والتحصيلات')
@section('page-subtitle', 'سجل السداد مع ترحيل قيوده')

@section('page-actions')
<a href="{{ route('admin.accounting.payments.create') }}" class="btn-primary">عملية سداد جديدة</a>
@endsection

@section('content')
<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الرقم</th>
                <th>التاريخ</th>
                <th>النوع</th>
                <th>الجهة</th>
                <th>الحساب النقدي</th>
                <th>المبلغ</th>
                <th>المرجع</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->number }}</td>
                    <td>{{ optional($payment->payment_date)->format('Y-m-d') }}</td>
                    <td>{{ $payment->direction === 'in' ? 'تحصيل' : 'سداد' }}</td>
                    <td>{{ $payment->contact?->name ?: '-' }}</td>
                    <td>{{ $payment->account?->code }} - {{ $payment->account?->name }}</td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->reference_type ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد عمليات سداد.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</section>
@endsection
