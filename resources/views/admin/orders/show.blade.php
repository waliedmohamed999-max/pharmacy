@extends('admin.layouts.app')

@section('page-title', 'تفاصيل الطلب #' . $order->id)
@section('page-subtitle', 'عرض بيانات العميل والعناصر وتحديث الحالة')

@section('content')
<div class="grid xl:grid-cols-3 gap-4 mb-4">
    <section class="card-premium p-4 xl:col-span-2">
        <h2 class="text-lg font-black mb-3">بيانات العميل</h2>
        <div class="grid md:grid-cols-2 gap-3 text-sm">
            <div><span class="text-slate-500">الاسم:</span> {{ $order->customer_name }}</div>
            <div><span class="text-slate-500">الهاتف:</span> {{ $order->phone }}</div>
            <div><span class="text-slate-500">المدينة:</span> {{ $order->city }}</div>
            <div><span class="text-slate-500">العنوان:</span> {{ $order->address }}</div>
        </div>
        @if($order->notes)
            <div class="mt-3 p-3 rounded-xl bg-slate-50 text-sm">{{ $order->notes }}</div>
        @endif
    </section>

    <section class="card-premium p-4">
        <h2 class="text-lg font-black mb-3">الحالة والملخص</h2>
        <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="space-y-3">
            @csrf
            @method('PATCH')
            <select name="status" class="select-premium">
                @foreach(['new'=>'جديد','preparing'=>'جاري التحضير','shipped'=>'تم الشحن','completed'=>'مكتمل','cancelled'=>'ملغي'] as $value=>$label)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-primary w-full">تحديث الحالة</button>
        </form>

        <div class="mt-4 text-sm space-y-2">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($order->subtotal,2) }} ج.م</span></div>
            <div class="flex justify-between"><span>Discount</span><span>{{ number_format($order->discount,2) }} ج.م</span></div>
            <div class="flex justify-between"><span>Shipping</span><span>{{ number_format($order->shipping,2) }} ج.م</span></div>
            <div class="flex justify-between font-black text-base"><span>Total</span><span>{{ number_format($order->total,2) }} ج.م</span></div>
        </div>
    </section>
</div>

<section class="card-premium p-4">
    <h2 class="text-lg font-black mb-3">عناصر الطلب</h2>
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
            <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td>{{ $item->product_name_snapshot }}</td>
                    <td>{{ number_format($item->price,2) }} ج.م</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format($item->line_total,2) }} ج.م</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty-state">لا توجد عناصر لهذا الطلب.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
