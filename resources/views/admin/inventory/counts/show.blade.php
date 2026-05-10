@extends('admin.layouts.app')

@section('page-title', 'جلسة جرد: ' . $count->number)
@section('page-subtitle', 'المخزن: ' . ($count->warehouse?->name ?? '-') . ' | الحالة: ' . ($count->status === 'posted' ? 'معتمد' : 'مسودة'))

@section('page-actions')
<div class="flex gap-2">
    <a href="{{ route('admin.inventory.counts.index') }}" class="btn-secondary">كل الجلسات</a>
    <a href="{{ route('admin.inventory.counts.pdf', $count) }}" class="btn-secondary">تحميل PDF</a>
    @if($count->status === 'draft')
        <form method="POST" action="{{ route('admin.inventory.counts.post', $count) }}">
            @csrf
            <button class="btn-primary" onclick="return confirm('اعتماد الجرد سيولّد تسويات مخزون وقيود مالية. متابعة؟')">اعتماد الجرد</button>
        </form>
    @endif
</div>
@endsection

@section('content')
<div class="card-premium p-4 mb-4">
    <form class="flex gap-2">
        <input type="hidden" name="only_diff" value="1">
        <button class="btn-secondary">عرض الفروقات فقط</button>
        <a href="{{ route('admin.inventory.counts.show', $count) }}" class="btn-secondary">عرض الكل</a>
    </form>
</div>

<form method="POST" action="{{ route('admin.inventory.counts.items.update', $count) }}" class="card-premium p-4">
    @csrf
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الرصيد الدفتري</th>
                    <th>العدد الفعلي</th>
                    <th>الفرق</th>
                    <th>التكلفة</th>
                    <th>قيمة الفرق</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ number_format($item->snapshot_qty, 2) }}</td>
                    <td>
                        @if($count->status === 'posted')
                            {{ number_format($item->counted_qty ?? 0, 2) }}
                        @else
                            <input type="number" step="0.01" min="0" name="counted_qty[{{ $item->id }}]" value="{{ $item->counted_qty }}" class="input-premium">
                        @endif
                    </td>
                    <td>{{ number_format($item->diff_qty, 2) }}</td>
                    <td>{{ number_format($item->unit_cost_snapshot, 4) }}</td>
                    <td>{{ number_format($item->diff_value, 2) }}</td>
                    <td>
                        @if($count->status === 'posted')
                            {{ $item->notes ?: '-' }}
                        @else
                            <input type="text" name="item_notes[{{ $item->id }}]" value="{{ $item->notes }}" class="input-premium">
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد بنود جرد.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-between items-center">
        <div>{{ $items->links() }}</div>
        @if($count->status === 'draft')
            <button class="btn-primary">حفظ العدّ</button>
        @endif
    </div>
</form>
@endsection
