@extends('admin.layouts.app')

@section('page-title', 'ميزان المراجعة التفصيلي')
@section('page-subtitle', 'إجماليات كل حساب (مدين/دائن/صافي)')

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
        <a href="{{ route('admin.accounting.reports.trial-balance') }}" class="btn-secondary w-full text-center">إعادة</a>
    </div>
</form>

<div class="card-premium p-4 mb-4 flex gap-2 flex-wrap">
    <a href="{{ route('admin.accounting.reports.trial-balance.excel', request()->query()) }}" class="btn-secondary">Excel</a>
    <a href="{{ route('admin.accounting.reports.trial-balance.pdf', request()->query()) }}" target="_blank" class="btn-secondary">PDF</a>
</div>

<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الكود</th>
                <th>الحساب</th>
                <th>النوع</th>
                <th>مدين</th>
                <th>دائن</th>
                <th>الصافي</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php($net = (float) $row->total_debit - (float) $row->total_credit)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->type }}</td>
                    <td>{{ number_format($row->total_debit, 2) }}</td>
                    <td>{{ number_format($row->total_credit, 2) }}</td>
                    <td>{{ number_format($net, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد بيانات.</div></td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">الإجمالي</th>
                    <th>{{ number_format($totals['debit'], 2) }}</th>
                    <th>{{ number_format($totals['credit'], 2) }}</th>
                    <th>{{ number_format($totals['debit'] - $totals['credit'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="mt-4">{{ $rows->links() }}</div>
</section>
@endsection
