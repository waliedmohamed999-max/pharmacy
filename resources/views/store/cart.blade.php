@extends('store.layouts.app')

@section('content')
@php
    $currency = 'ج.م';
    $freeShippingTarget = 500;
    $remainingForFreeShipping = max(0, $freeShippingTarget - (float) $total);
    $freeShippingProgress = min(100, $freeShippingTarget > 0 ? ((float) $total / $freeShippingTarget) * 100 : 100);
    $checkoutDisabled = count($items) === 0;
@endphp

<section class="relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 p-5 shadow-sm md:p-8">
    <div class="absolute -left-24 top-0 h-64 w-64 rounded-full bg-emerald-200/45 blur-3xl"></div>
    <div class="absolute -right-24 bottom-0 h-64 w-64 rounded-full bg-cyan-200/45 blur-3xl"></div>

    <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_430px] lg:items-end">
        <div>
            <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                سلة الشراء
            </div>
            <h1 class="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">راجع طلبك قبل الإتمام</h1>
            <p class="mt-3 max-w-2xl text-sm font-semibold leading-7 text-slate-600 md:text-base">
                تأكد من المنتجات والكميات، ثم أكمل بيانات التوصيل والدفع لإرسال الطلب للصيدلية مباشرة.
            </p>
        </div>

        <div class="rounded-[1.5rem] border border-white/80 bg-white/80 p-2 shadow-xl shadow-emerald-950/5 backdrop-blur">
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-2xl bg-emerald-600 px-3 py-3 text-white">
                    <div class="text-xs font-bold text-white/75">الخطوة 1</div>
                    <div class="mt-1 text-sm font-black">مراجعة السلة</div>
                </div>
                <div class="rounded-2xl bg-slate-100 px-3 py-3 text-slate-700">
                    <div class="text-xs font-bold text-slate-400">الخطوة 2</div>
                    <div class="mt-1 text-sm font-black">بيانات التوصيل</div>
                </div>
                <div class="rounded-2xl bg-slate-100 px-3 py-3 text-slate-700">
                    <div class="text-xs font-bold text-slate-400">الخطوة 3</div>
                    <div class="mt-1 text-sm font-black">تأكيد الطلب</div>
                </div>
            </div>
        </div>
    </div>
</section>

@if(count($items))
    <section class="mt-6 grid gap-5 xl:grid-cols-[minmax(0,1fr)_430px]">
        <div class="space-y-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">منتجات السلة</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $distinct_count }} صنف، {{ $count }} قطعة داخل الطلب</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('store.products.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-800 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                            إضافة منتجات أخرى
                        </a>
                        <a href="{{ route('store.checkout.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-950 px-4 text-sm font-black text-white transition hover:bg-emerald-700">
                            الانتقال للدفع
                        </a>
                    </div>
                </div>
            </div>

            @foreach($items as $item)
                <article class="group overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-950/10">
                    <div class="grid gap-4 p-4 lg:grid-cols-[150px_minmax(0,1fr)_210px] lg:p-5">
                        <a href="{{ route('store.product.show', $item['product_slug']) }}" class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-50 via-white to-emerald-50 p-3">
                            <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="h-36 w-full object-contain transition duration-500 group-hover:scale-105">
                            @if($item['discount_percent'] > 0)
                                <span class="absolute right-3 top-3 rounded-full bg-rose-600 px-2.5 py-1 text-[11px] font-black text-white">خصم {{ $item['discount_percent'] }}%</span>
                            @endif
                        </a>

                        <div class="min-w-0">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $item['category_name'] ?: 'منتج صيدلي' }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">SKU: {{ $item['sku'] ?: '-' }}</span>
                                @if($item['is_low_stock'])
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">كمية محدودة</span>
                                @endif
                            </div>

                            <a href="{{ route('store.product.show', $item['product_slug']) }}" class="line-clamp-2 text-xl font-black leading-8 text-slate-950 transition hover:text-emerald-700">
                                {{ $item['name'] }}
                            </a>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-bold text-slate-500">السعر</div>
                                    <div class="mt-1 text-lg font-black text-cyan-700">{{ number_format($item['price'], 2) }} {{ $currency }}</div>
                                    @if($item['compare_price'] > $item['price'])
                                        <div class="text-xs font-bold text-slate-400 line-through">{{ number_format($item['compare_price'], 2) }} {{ $currency }}</div>
                                    @endif
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-bold text-slate-500">المتوفر</div>
                                    <div class="mt-1 text-lg font-black {{ $item['is_low_stock'] ? 'text-amber-700' : 'text-emerald-700' }}">{{ $item['available_qty'] }} قطعة</div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <div class="text-xs font-bold text-slate-500">التوفير</div>
                                    <div class="mt-1 text-lg font-black text-emerald-700">{{ number_format($item['line_saving'], 2) }} {{ $currency }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col justify-between gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4">
                            <div>
                                <div class="text-xs font-bold text-slate-500">إجمالي المنتج</div>
                                <div class="mt-1 text-2xl font-black text-slate-950">{{ number_format($item['line_total'], 2) }} {{ $currency }}</div>
                            </div>

                            <div class="rounded-2xl bg-white p-2 shadow-sm">
                                <div class="grid grid-cols-[42px_minmax(0,1fr)_42px] items-center gap-2">
                                    <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="qty" value="{{ min($item['available_qty'], $item['qty'] + 1) }}">
                                        <button type="submit" class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-600 text-lg font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" @disabled(!$item['can_increase'])>+</button>
                                    </form>

                                    <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="qty" min="0" max="{{ max(0, (int) $item['available_qty']) }}" value="{{ $item['qty'] }}" class="h-11 w-full rounded-2xl border border-slate-200 bg-white text-center text-sm font-black text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                        <button class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-800 transition hover:bg-emerald-50 hover:text-emerald-700" type="submit">تحديث الكمية</button>
                                    </form>

                                    <form method="POST" action="{{ route('store.cart.update', $item['rowId']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="qty" value="{{ max(0, $item['qty'] - 1) }}">
                                        <button type="submit" class="grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-800 transition hover:bg-slate-100">-</button>
                                    </form>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('store.cart.remove', $item['rowId']) }}">
                                @csrf
                                @method('DELETE')
                                <button class="w-full rounded-2xl bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:bg-rose-600 hover:text-white" type="submit">
                                    حذف من السلة
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28 xl:self-start">
            <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-950/5">
                <div class="bg-gradient-to-br from-emerald-700 to-teal-500 p-5 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-bold text-white/75">ملخص الطلب</div>
                            <div class="mt-1 text-3xl font-black">{{ number_format($total, 2) }} {{ $currency }}</div>
                        </div>
                        <div class="rounded-2xl bg-white/15 px-3 py-2 text-xs font-black">{{ $count }} قطعة</div>
                    </div>
                    <div class="mt-4 rounded-full bg-white/20 p-1">
                        <div class="h-2 rounded-full bg-white transition-all" style="width: {{ $freeShippingProgress }}%"></div>
                    </div>
                    <p class="mt-2 text-xs font-bold text-white/85">
                        @if($remainingForFreeShipping > 0)
                            أضف {{ number_format($remainingForFreeShipping, 2) }} {{ $currency }} للحصول على شحن مجاني.
                        @else
                            طلبك مؤهل للشحن المجاني.
                        @endif
                    </p>
                </div>

                <div class="p-5">
                    <form method="POST" action="{{ route('store.cart.coupon.apply') }}" class="mb-4">
                        @csrf
                        <label class="mb-2 block text-sm font-black text-slate-700">كود الخصم</label>
                        <div class="flex gap-2">
                            <input type="text" name="code" class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100" placeholder="اكتب كود الخصم" value="{{ old('code') }}">
                            <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white transition hover:bg-emerald-700" type="submit">تطبيق</button>
                        </div>
                    </form>

                    @if($coupon)
                        <div class="mb-4 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm">
                            <span class="font-black text-emerald-800">الكوبون: {{ $coupon['code'] }}</span>
                            <form method="POST" action="{{ route('store.cart.coupon.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button class="font-black text-rose-700" type="submit">إلغاء</button>
                            </form>
                        </div>
                    @endif

                    <div class="space-y-3 text-sm font-bold text-slate-600">
                        <div class="flex justify-between">
                            <span>عدد الأصناف</span>
                            <span class="text-slate-950">{{ $distinct_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>إجمالي الكميات</span>
                            <span class="text-slate-950">{{ $count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>الإجمالي قبل الخصم</span>
                            <span class="text-slate-950">{{ number_format($subtotal, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between text-rose-700">
                            <span>خصم الكوبون</span>
                            <span>- {{ number_format($discount, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-700">
                            <span>إجمالي التوفير</span>
                            <span>{{ number_format($total_saving, 2) }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-4 text-lg font-black text-slate-950">
                            <span>الإجمالي النهائي</span>
                            <span>{{ number_format($total, 2) }} {{ $currency }}</span>
                        </div>
                    </div>

                    @if($low_stock_count > 0)
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-black text-amber-800">
                            يوجد {{ $low_stock_count }} منتج بمخزون منخفض. يفضل إتمام الطلب قبل نفاد الكمية.
                        </div>
                    @endif

                    <a href="{{ route('store.checkout.index') }}" class="mt-5 flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-4 text-base font-black text-white shadow-xl shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">
                        إتمام الطلب
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 rounded-[1.5rem] border border-slate-200 bg-white p-3 text-center shadow-sm">
                <div>
                    <div class="mx-auto grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="mt-2 text-xs font-black text-slate-700">منتجات أصلية</div>
                </div>
                <div>
                    <div class="mx-auto grid h-10 w-10 place-items-center rounded-2xl bg-cyan-50 text-cyan-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7h13v10H3z"/><path d="M16 10h3l2 3v4h-5"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    </div>
                    <div class="mt-2 text-xs font-black text-slate-700">توصيل سريع</div>
                </div>
                <div>
                    <div class="mx-auto grid h-10 w-10 place-items-center rounded-2xl bg-amber-50 text-amber-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    </div>
                    <div class="mt-2 text-xs font-black text-slate-700">دفع آمن</div>
                </div>
            </div>

            <a href="{{ route('store.home') }}" class="flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-4 text-base font-black text-slate-900 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                متابعة التسوق
            </a>
        </aside>
    </section>
@else
    <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-950/5 md:p-12">
        <div class="mx-auto grid h-24 w-24 place-items-center rounded-[2rem] bg-emerald-50 text-emerald-700">
            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
        </div>
        <h2 class="mt-6 text-3xl font-black text-slate-950">السلة فارغة</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm font-semibold leading-7 text-slate-500">
            ابدأ بإضافة الأدوية والمنتجات الصحية إلى السلة، ثم أكمل طلبك بسهولة.
        </p>
        <a href="{{ route('store.products.index') }}" class="mt-6 inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-xl shadow-emerald-700/20 transition hover:bg-emerald-700">
            تصفح المنتجات
        </a>
    </section>
@endif
@endsection
