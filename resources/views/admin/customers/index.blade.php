@extends('admin.layouts.app')

@section('page-title', 'العملاء')
@section('page-subtitle', 'إدارة بيانات العملاء مع الاستيراد والتصدير')

@section('page-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.customers.export') }}" class="btn-secondary">تصدير Excel (CSV)</a>
    <form method="POST" action="{{ route('admin.customers.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" class="input-premium !py-2 !px-2 w-56" required>
        <button class="btn-primary">استيراد</button>
    </form>
</div>
@endsection

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-4 gap-3">
    <input name="search" class="input-premium" placeholder="بحث بالاسم أو الهاتف أو البريد" value="{{ request('search') }}">
    <div></div>
    <div></div>
    <button class="btn-primary">تطبيق</button>
</form>

<div class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>المدينة</th>
                <th>عدد الطلبات</th>
                <th>إجمالي الإنفاق</th>
                <th>الحالة</th>
            </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>#{{ $customer->id }}</td>
                    <td>
                        <div class="font-semibold">{{ $customer->name }}</div>
                        @if($customer->email)
                            <div class="text-xs text-slate-500">{{ $customer->email }}</div>
                        @endif
                    </td>
                    <td>{{ $customer->phone ?: '—' }}</td>
                    <td>{{ $customer->city ?: '—' }}</td>
                    <td>{{ $customer->orders_count }}</td>
                    <td>{{ number_format((float)($customer->orders_sum_total ?? 0), 2) }} ج.م</td>
                    <td><span class="{{ $customer->is_active ? 'badge-success' : 'badge-danger' }}">{{ $customer->is_active ? 'نشط' : 'غير نشط' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد بيانات عملاء حتى الآن.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
</div>
@endsection
