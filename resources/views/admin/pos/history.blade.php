@extends('admin.layouts.app')

@section('page-title', 'سجل نقاط البيع')
@section('page-subtitle', 'كل عمليات البيع المباشر')

@section('page-actions')
    <a href="{{ route('admin.pos.index') }}" class="btn-primary">فاتورة POS جديدة</a>
@endsection

@section('content')
<div class="card-premium overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-slate-500 border-b">
                <th class="text-right p-3">رقم العملية</th>
                <th class="text-right p-3">التاريخ</th>
                <th class="text-right p-3">المخزن</th>
                <th class="text-right p-3">العميل</th>
                <th class="text-right p-3">الإجمالي</th>
                <th class="text-right p-3">المدفوع</th>
                <th class="text-right p-3">الحالة</th>
                <th class="text-right p-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr class="border-b">
                    <td class="p-3 font-bold">{{ $sale->number }}</td>
                    <td class="p-3">{{ $sale->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="p-3">{{ $sale->warehouse?->name }}</td>
                    <td class="p-3">{{ $sale->customer_name ?: ($sale->contact?->name ?? '-') }}</td>
                    <td class="p-3">{{ number_format((float) $sale->total, 2) }} ج.م</td>
                    <td class="p-3">{{ number_format((float) $sale->paid_amount, 2) }} ج.م</td>
                    <td class="p-3">{{ $sale->status }}</td>
                    <td class="p-3">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.pos.show', $sale) }}" class="btn-secondary">تفاصيل</a>
                            <a href="{{ route('admin.pos.receipt', $sale) }}" target="_blank" class="btn-secondary">إيصال</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-4 text-center text-slate-500">لا توجد عمليات POS.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $sales->links() }}
</div>
@endsection
