@extends('store.layouts.app')

@section('content')
<h1 class="text-2xl md:text-3xl font-black mb-4">السلة</h1>

@if(count($items))
    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-3">
            @foreach($items as $item)
                <div class="neo-card p-3 md:p-4">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center">
                        <a href="{{ route('store.product.show', $item['product_slug']) }}" class="block w-full md:w-28 shrink-0">
                            <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-28 object-cover rounded-xl">
                        </a>

                        <div class="flex-1 min-w-0">
                            <a href="{{ route('store.product.show', $item['product_slug']) }}" class="font-extrabold line-clamp-2">{{ $item['name'] }}</a>
                            <div class="text-xs text-gray-600 mt-1">SKU: {{ $item['sku'] ?: '-' }}</div>
                            <div class="text-xs text-gray-600">التصنيف: {{ $item['category_name'] ?: '-' }}</div>
                            <div class="text-xs mt-1 {{ $item['is_low_stock'] ? 'text-amber-700' : 'text-emerald-700' }}">
                                المتاح بالمخزون: {{ $item['available_qty'] }}
                            </div>
                        </div>

                        <div class="md:text-left text-right">
                            <div class="font-extrabold text-cyan-700">{{ number_format($item['price'], 2) }} ج.م</div>
                            @if($item['compare_price'] > $item['price'])
                                <div class="text-xs line-through text-gray-500">{{ number_format($item['compare_price'], 2) }} ج.م</div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3 items-center justify-between">
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="qty" value="{{ max(0, $item['qty'] - 1) }}">
                                <button type="submit" class="neo-btn px-3">-</button>
                            </form>

                            <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="qty" min="0" max="{{ max(0, (int) $item['available_qty']) }}" value="{{ $item['qty'] }}" class="neo-input w-20">
                                <button class="neo-btn" type="submit">تحديث</button>
                            </form>

                            <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="qty" value="{{ min($item['available_qty'], $item['qty'] + 1) }}">
                                <button type="submit" class="neo-btn px-3" @disabled(!$item['can_increase'])>+</button>
                            </form>
                        </div>

                        <div class="flex items-center gap-3">
                            @if($item['line_saving'] > 0)
                                <div class="text-xs text-emerald-700">وفرت {{ number_format($item['line_saving'], 2) }} ج.م</div>
                            @endif

                            <div class="font-extrabold">الإجمالي: {{ number_format($item['line_total'], 2) }} ج.م</div>

                            <form method="POST" action="{{ route('store.cart.remove', $item['rowId']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="neo-btn" type="submit">حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="space-y-3">
            <div class="neo-card p-4">
                <h2 class="text-xl font-black mb-3">ملخص الطلب</h2>

                <form method="POST" action="{{ route('store.cart.coupon.apply') }}" class="flex gap-2 mb-3">
                    @csrf
                    <input type="text" name="code" class="neo-input" placeholder="كود الخصم" value="{{ old('code') }}">
                    <button class="neo-btn" type="submit">تطبيق</button>
                </form>

                @if($coupon)
                    <div class="mb-3 flex items-center justify-between text-sm bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                        <span>الكوبون: <strong>{{ $coupon['code'] }}</strong></span>
                        <form method="POST" action="{{ route('store.cart.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-700 font-bold" type="submit">إلغاء</button>
                        </form>
                    </div>
                @endif

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>عدد الأصناف</span>
                        <span>{{ $distinct_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>إجمالي الكميات</span>
                        <span>{{ $count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>الإجمالي قبل الخصم</span>
                        <span>{{ number_format($subtotal, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between text-rose-700">
                        <span>خصم الكوبون</span>
                        <span>- {{ number_format($discount, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between text-emerald-700">
                        <span>إجمالي التوفير</span>
                        <span>{{ number_format($total_saving, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between font-black text-base border-t pt-2">
                        <span>الإجمالي النهائي</span>
                        <span>{{ number_format($total, 2) }} ج.م</span>
                    </div>
                </div>

                @if($low_stock_count > 0)
                    <div class="mt-3 text-xs text-amber-700">
                        يوجد {{ $low_stock_count }} منتج بمخزون منخفض.
                    </div>
                @endif

                <a href="{{ route('store.checkout.index') }}" class="neo-btn mt-4 inline-flex w-full justify-center">إتمام الطلب</a>
            </div>

            <a href="{{ route('store.home') }}" class="neo-btn inline-flex w-full justify-center">متابعة التسوق</a>
        </div>
    </div>
@else
    <div class="neo-card p-8 text-center">
        <p class="font-bold mb-3">السلة فارغة.</p>
        <a href="{{ route('store.home') }}" class="neo-btn inline-flex">ابدأ التسوق</a>
    </div>
@endif
@endsection

