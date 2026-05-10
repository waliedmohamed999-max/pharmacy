@extends('admin.layouts.app')

@section('page-title', 'القيود اليومية')
@section('page-subtitle', 'سجل القيود المحاسبية')

@section('page-actions')
<a href="{{ route('admin.accounting.journal.create') }}" class="btn-primary">قيد يومي جديد</a>
@endsection

@section('content')
<section class="card-premium p-4">
    <div class="space-y-3">
        @forelse($entries as $entry)
            <div class="card-premium p-3">
                <div class="flex justify-between mb-2">
                    <div class="font-black">{{ $entry->number }}</div>
                    <div class="text-sm text-slate-500">{{ optional($entry->entry_date)->format('Y-m-d') }}</div>
                </div>
                <div class="text-sm mb-2">{{ $entry->description ?: 'بدون وصف' }}</div>
                <div class="table-wrap">
                    <table class="table-premium">
                        <thead>
                        <tr>
                            <th>الحساب</th>
                            <th>مدين</th>
                            <th>دائن</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entry->lines as $line)
                            <tr>
                                <td>{{ $line->account?->code }} - {{ $line->account?->name }}</td>
                                <td>{{ number_format($line->debit, 2) }}</td>
                                <td>{{ number_format($line->credit, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="empty-state">لا توجد قيود حتى الآن.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
</section>
@endsection
