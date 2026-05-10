@extends('admin.layouts.app')

@section('page-title', 'تفاصيل عملية POS')
@section('page-subtitle', $sale->number)

@section('page-actions')
    <div class="flex gap-2">
        <a href="{{ route('admin.pos.receipt', $sale) }}" target="_blank" class="btn-primary">طباعة الإيصال</a>
        <a href="{{ route('admin.pos.history') }}" class="btn-secondary">العودة للسجل</a>
    </div>
@endsection

@section('content')
<div class="grid lg:grid-cols-3 gap-4">
    <section class="card-premium p-4 lg:col-span-2">
        <h2 class="font-black mb-3">الأصناف</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-slate-500 border-b">
                        <th class="text-right py-2">المنتج</th>
                        <th class="text-right py-2">الكمية</th>
                        <th class="text-right py-2">السعر</th>
                        <th class="text-right py-2">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item->description }}</td>
                            <td class="py-2">{{ number_format((float) $item->qty, 2) }}</td>
                            <td class="py-2">{{ number_format((float) $item->unit_price, 2) }} ج.م</td>
                            <td class="py-2 font-bold">{{ number_format((float) $item->line_total, 2) }} ج.م</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="font-black mb-3">ملخص العملية</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span>رقم العملية</span><span>{{ $sale->number }}</span></div>
            <div class="flex justify-between"><span>العميل</span><span>{{ $sale->customer_name ?: ($sale->contact?->name ?? '-') }}</span></div>
            <div class="flex justify-between"><span>الجوال</span><span>{{ $sale->customer_phone ?: '-' }}</span></div>
            <div class="flex justify-between"><span>المخزن</span><span>{{ $sale->warehouse?->name }}</span></div>
            <div class="flex justify-between"><span>طريقة الدفع</span><span>{{ $sale->payment_method }}</span></div>
            <div class="flex justify-between"><span>الحالة</span><span>{{ $sale->status }}</span></div>
            <div class="flex justify-between"><span>SubTotal</span><span>{{ number_format((float) $sale->subtotal, 2) }} ج.م</span></div>
            <div class="flex justify-between"><span>الخصم</span><span>{{ number_format((float) $sale->discount, 2) }} ج.م</span></div>
            <div class="flex justify-between"><span>الضريبة</span><span>{{ number_format((float) $sale->tax, 2) }} ج.م</span></div>
            <div class="flex justify-between font-black text-base border-t pt-2"><span>الإجمالي</span><span>{{ number_format((float) $sale->total, 2) }} ج.م</span></div>
            <div class="flex justify-between"><span>المدفوع</span><span>{{ number_format((float) $sale->paid_amount, 2) }} ج.م</span></div>
            <div class="flex justify-between"><span>الباقي للعميل</span><span>{{ number_format((float) $sale->change_amount, 2) }} ج.م</span></div>
        </div>
    </section>
</div>
@endsection
