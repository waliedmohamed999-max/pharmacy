@extends('admin.layouts.app')

@section('page-title', 'الجرد الفعلي')
@section('page-subtitle', 'جلسات جرد المخزون ومتابعة حالتها')

@section('page-actions')
<a href="{{ route('admin.inventory.counts.create') }}" class="btn-primary">جلسة جرد جديدة</a>
@endsection

@section('content')
<section class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>رقم الجلسة</th>
                    <th>المخزن</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>تاريخ الاعتماد</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
            @forelse($counts as $count)
                <tr>
                    <td>{{ $count->number }}</td>
                    <td>{{ $count->warehouse?->name }}</td>
                    <td>{{ optional($count->count_date)->format('Y-m-d') }}</td>
                    <td>{{ $count->status === 'posted' ? 'معتمد' : 'مسودة' }}</td>
                    <td>{{ optional($count->posted_at)->format('Y-m-d H:i') ?: '-' }}</td>
                    <td><a href="{{ route('admin.inventory.counts.show', $count) }}" class="btn-secondary">فتح</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد جلسات جرد.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $counts->links() }}</div>
</section>
@endsection
