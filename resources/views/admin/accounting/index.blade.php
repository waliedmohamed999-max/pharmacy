@extends('admin.layouts.app')

@section('page-title', 'النظام المالي')
@section('page-subtitle', 'إدارة المبيعات والمشتريات والقيود وشجرة الحسابات')

@section('page-actions')
<div class="flex gap-2 flex-wrap">
    <a href="{{ route('admin.accounting.accounts.index') }}" class="btn-secondary">شجرة الحسابات</a>
    <a href="{{ route('admin.accounting.sales.create') }}" class="btn-primary">فاتورة مبيعات</a>
    <a href="{{ route('admin.accounting.purchases.create') }}" class="btn-secondary">فاتورة مشتريات</a>
    <a href="{{ route('admin.accounting.payments.create') }}" class="btn-secondary">سداد/تحصيل</a>
</div>
@endsection

@section('content')
<div class="card-premium p-4 mb-4">
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2">
        <a href="{{ route('admin.accounting.accounts.index') }}" class="btn-secondary text-center">شجرة الحسابات</a>
        <a href="{{ route('admin.accounting.contacts.index') }}" class="btn-secondary text-center">العملاء/الموردون</a>
        <a href="{{ route('admin.accounting.sales.index') }}" class="btn-secondary text-center">المبيعات</a>
        <a href="{{ route('admin.accounting.purchases.index') }}" class="btn-secondary text-center">المشتريات</a>
        <a href="{{ route('admin.accounting.payments.index') }}" class="btn-secondary text-center">المدفوعات</a>
        <a href="{{ route('admin.accounting.journal.index') }}" class="btn-secondary text-center">القيود اليومية</a>
        <a href="{{ route('admin.accounting.reports.ledger') }}" class="btn-secondary text-center">دفتر الأستاذ</a>
        <a href="{{ route('admin.accounting.reports.trial-balance') }}" class="btn-secondary text-center">ميزان المراجعة</a>
        <a href="{{ route('admin.accounting.reports.income-statement') }}" class="btn-secondary text-center">قائمة الدخل</a>
        <a href="{{ route('admin.accounting.reports.balance-sheet') }}" class="btn-secondary text-center">المركز المالي</a>
        <a href="{{ route('admin.accounting.reports.cash-flow') }}" class="btn-secondary text-center">التدفقات النقدية</a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="kpi-card">
        <div class="text-sm text-slate-500">إجمالي المبيعات</div>
        <div class="kpi-value">{{ number_format($salesTotal, 2) }} ج.م</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">إجمالي المشتريات</div>
        <div class="kpi-value">{{ number_format($purchasesTotal, 2) }} ج.م</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">المدينون (عملاء)</div>
        <div class="kpi-value">{{ number_format($receivables, 2) }} ج.م</div>
    </div>
    <div class="kpi-card">
        <div class="text-sm text-slate-500">الدائنون (موردون)</div>
        <div class="kpi-value">{{ number_format($payables, 2) }} ج.م</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <section class="card-premium p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-black">ميزان مراجعة (مبدئي)</h2>
            <a href="{{ route('admin.accounting.journal.index') }}" class="btn-secondary">كل القيود</a>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>الحساب</th>
                        <th>مدين</th>
                        <th>دائن</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($trialBalance as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ number_format($row->total_debit, 2) }}</td>
                        <td>{{ number_format($row->total_credit, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد حركات محاسبية حتى الآن.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-black">آخر القيود</h2>
            <a href="{{ route('admin.accounting.journal.create') }}" class="btn-secondary">قيد جديد</a>
        </div>
        <div class="space-y-2">
            @forelse($latestEntries as $entry)
                <div class="card-premium p-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-bold">{{ $entry->number }}</span>
                        <span class="text-slate-500">{{ optional($entry->entry_date)->format('Y-m-d') }}</span>
                    </div>
                    <div class="text-sm text-slate-600">{{ $entry->description ?: 'بدون وصف' }}</div>
                </div>
            @empty
                <div class="empty-state">لا توجد قيود بعد.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
