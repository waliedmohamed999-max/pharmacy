@extends('store.layouts.app')

@section('content')
<h1 class="text-2xl md:text-3xl font-black mb-4">إتمام الطلب</h1>

<div class="grid lg:grid-cols-3 gap-4">
    <form method="POST" action="{{ route('store.checkout.store') }}" class="neo-card p-6 grid gap-3 lg:col-span-2">
        @csrf

        <input name="customer_name" class="neo-input" placeholder="الاسم بالكامل" value="{{ old('customer_name') }}">
        @error('customer_name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

        <input name="phone" class="neo-input" placeholder="رقم الهاتف" value="{{ old('phone') }}">
        @error('phone')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

        <input name="city" class="neo-input" placeholder="المدينة" value="{{ old('city') }}">
        @error('city')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

        <textarea name="address" class="neo-input" placeholder="العنوان">{{ old('address') }}</textarea>
        @error('address')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

        <textarea name="notes" class="neo-input" placeholder="ملاحظات">{{ old('notes') }}</textarea>

        <button class="neo-btn">تأكيد الطلب</button>
    </form>

    <div class="neo-card p-4 h-fit">
        <h2 class="text-xl font-black mb-3">ملخص السلة</h2>

        <div class="space-y-3 max-h-72 overflow-auto">
            @foreach($items as $item)
                <div class="flex items-start justify-between gap-2 text-sm border-b pb-2">
                    <div class="min-w-0">
                        <div class="font-bold line-clamp-1">{{ $item['name'] }}</div>
                        <div class="text-xs text-gray-600">{{ $item['qty'] }} × {{ number_format($item['price'], 2) }} ج.م</div>
                    </div>
                    <div class="font-bold whitespace-nowrap">{{ number_format($item['line_total'], 2) }} ج.م</div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 pt-3 border-t space-y-2 text-sm">
            <div class="flex justify-between"><span>عدد الأصناف</span><span>{{ $distinct_count }}</span></div>
            <div class="flex justify-between"><span>إجمالي الكميات</span><span>{{ $count }}</span></div>
            <div class="flex justify-between"><span>الإجمالي قبل الخصم</span><span>{{ number_format($subtotal, 2) }} ج.م</span></div>
            <div class="flex justify-between text-rose-700"><span>خصم الكوبون</span><span>- {{ number_format($discount, 2) }} ج.م</span></div>
            <div class="flex justify-between text-emerald-700"><span>إجمالي التوفير</span><span>{{ number_format($total_saving, 2) }} ج.م</span></div>
            <div class="flex justify-between text-base font-black border-t pt-2"><span>الإجمالي</span><span>{{ number_format($total, 2) }} ج.م</span></div>
        </div>
    </div>
</div>
@endsection

