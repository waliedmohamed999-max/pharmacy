@extends('admin.layouts.app')

@section('page-title', 'دفتر الأستاذ')
@section('page-subtitle', 'تفصيل كل الحركات المدينة والدائنة حسب الفلاتر')

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-4 gap-3">
    <div>
        <label class="block text-xs text-slate-500 mb-1">الحساب</label>
        <select name="account_id" class="select-premium">
            <option value="">كل الحسابات</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected((int) $filters['account_id'] === (int) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
            @endforeach
        </select>
    </div>
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
        <a href="{{ route('admin.accounting.reports.ledger') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<div class="card-premium p-4 mb-4 flex gap-2 flex-wrap">
    <a href="{{ route('admin.accounting.reports.ledger.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.ledger.pdf', request()->query()) }}" target="_blank" class="btn-secondary">PDF</a>
</div>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>التاريخ</th>
                <th>رقم القيد</th>
                <th>الحساب</th>
                <th>الجهة</th>
                <th>الوصف</th>
                <th>مدين</th>
                <th>دائن</th>
            </tr>
            </thead>
            <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ $line->entry_date }}</td>
                    <td>{{ $line->entry_number }}</td>
                    <td>{{ $line->account_code }} - {{ $line->account_name }}</td>
                    <td>{{ $line->contact_name ?: '-' }}</td>
                    <td>{{ $line->line_description ?: '-' }}</td>
                    <td>{{ number_format($line->debit, 2) }}</td>
                    <td>{{ number_format($line->credit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد حركات.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $lines->links() }}</div>
</section>
@endsection
