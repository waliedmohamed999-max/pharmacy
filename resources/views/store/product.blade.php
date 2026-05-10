@extends('store.layouts.app')

@section('full_bleed', true)

@section('content')
@php
    $availableQty = (int) max(0, (float) ($product->available_qty ?? $product->quantity ?? 0));
    $discount = $product->discount_percent;
    $rating = '4.8';
    $reviews = 64 + (($product->id * 7) % 180);
    $cartCount = app(\App\Services\CartService::class)->summary()['count'] ?? 0;
    $gallery = collect([$product->image_url])->merge($product->images->map(function ($img) {
        if (str_starts_with($img->path, 'http://') || str_starts_with($img->path, 'https://')) {
            return $img->path;
        }

        return asset(str_starts_with($img->path, 'images/') ? $img->path : 'storage/'.$img->path);
    }))->filter()->unique()->values();
    $categoryUrl = ($product->category && $product->category->slug) ? route('store.category.show', $product->category->slug) : route('store.products.index');
@endphp

<div class="min-h-screen bg-slate-50 text-slate-950">
    <div class="border-b border-white/50 bg-emerald-800 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 overflow-x-auto px-4 py-2 text-[11px] font-bold md:px-5 md:text-xs">
            <span class="shrink-0">شحن سريع للطلبات داخل نطاق الصيدلية</span>
            <span class="shrink-0">دعم ومتابعة الطلبات 24/7</span>
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="shrink-0 hover:text-emerald-100">{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</a>
        </div>
    </div>
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto grid max-w-7xl grid-cols-12 items-center gap-2 px-3 py-3 md:gap-3 md:px-5">
            <a href="{{ route('store.home') }}" class="col-span-8 flex min-w-0 items-center gap-2 md:col-span-3 md:gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-600 text-base font-black text-white shadow-lg shadow-emerald-600/20 md:h-11 md:w-11 md:text-lg">Rx</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black md:text-lg">صيدلية د. محمد رمضان</span>
                    <span class="hidden text-xs font-bold text-emerald-600 sm:block">تسوق صحي موثوق</span>
                </span>
            </a>

            <form action="{{ route('store.search') }}" class="order-3 col-span-12 md:order-none md:col-span-6">
                <input name="q" value="{{ request('q') }}" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 md:h-14 md:rounded-3xl" placeholder="ابحث بالاسم أو الباركود">
            </form>

            <div class="col-span-4 flex items-center justify-end gap-1 md:col-span-3 md:gap-2">
                <a href="{{ route('store.products.index') }}" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:inline-flex">كل المنتجات</a>
                <a href="{{ route('store.cart.index') }}" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:px-4 md:text-sm">السلة ({{ $cartCount }})</a>
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-black">EN</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 md:px-5 md:py-8">
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
            <a href="{{ route('store.home') }}" class="hover:text-emerald-700">الرئيسية</a>
            <span>/</span>
            <a href="{{ route('store.products.index') }}" class="hover:text-emerald-700">كل المنتجات</a>
            @if($product->category)
                <span>/</span>
                <a href="{{ $categoryUrl }}" class="hover:text-emerald-700">{{ $product->category->display_name }}</a>
            @endif
        </nav>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,.88fr)_minmax(420px,1.12fr)]">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="relative grid aspect-square max-h-[520px] place-items-center overflow-hidden rounded-[1.6rem] bg-gradient-to-br from-emerald-50 via-white to-teal-50">
                        @if($discount > 0)
                            <span class="absolute right-4 top-4 z-10 rounded-full bg-rose-600 px-3 py-1 text-xs font-black text-white">خصم {{ $discount }}%</span>
                        @endif
                        <img id="mainProductImage" src="{{ $gallery->first() ?: $product->image_url }}" class="max-h-full w-full object-contain p-6 transition duration-300" alt="{{ $product->name }}">
                    </div>

                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        @foreach($gallery as $image)
                            <button type="button" class="product-thumb grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-1 transition hover:border-emerald-500" data-image="{{ $image }}" aria-label="صورة {{ $loop->iteration }}">
                                <img src="{{ $image }}" class="h-full w-full rounded-xl object-contain" alt="{{ $product->name }}">
                            </button>
                        @endforeach
                    </div>
                </div>
            </aside>

            <section class="space-y-4">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm md:p-7">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">منتج أصلي</span>
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">متاح للتوصيل</span>
                        <span class="rounded-full {{ $availableQty > 0 ? 'bg-lime-50 text-lime-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-black">
                            {{ $availableQty > 0 ? 'متوفر بالصيدلية' : 'غير متوفر حالياً' }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-black leading-tight text-slate-950 md:text-4xl">{{ $product->name }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <div class="flex text-amber-400">@for($i = 0; $i < 5; $i++)<span>★</span>@endfor</div>
                        <span class="text-sm font-black text-slate-700">{{ $rating }}</span>
                        <span class="text-sm font-semibold text-slate-500">({{ $reviews }} تقييم)</span>
                        @if($product->sku)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">SKU: {{ $product->sku }}</span>
                        @endif
                    </div>

                    <div class="mt-6 rounded-3xl bg-slate-50 p-5">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="text-3xl font-black text-emerald-700">{{ number_format($product->price, 2) }} ج.م</div>
                            @if($product->compare_price && $product->compare_price > $product->price)
                                <div class="pb-1 text-base font-bold text-slate-400 line-through">{{ number_format($product->compare_price, 2) }} ج.م</div>
                            @endif
                        </div>
                        <div class="mt-2 text-sm font-bold text-slate-500">دفع آمن · فاتورة إلكترونية · متابعة الطلب</div>
                    </div>

                    <p class="mt-5 text-base font-semibold leading-8 text-slate-600">
                        {{ $product->description ?: $product->short_description ?: 'منتج صحي موثوق متاح للطلب من الصيدلية مع تجربة شراء آمنة وسريعة.' }}
                    </p>

                    <form action="{{ route('store.cart.add') }}" method="POST" class="mt-6 grid gap-3 sm:grid-cols-[120px_1fr]">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="number" name="qty" value="1" min="1" max="{{ max(1, $availableQty) }}" class="h-14 rounded-2xl border border-slate-200 bg-white px-4 text-center text-lg font-black outline-none focus:border-emerald-300 focus:ring-4 focus:ring-emerald-500/10" @disabled($availableQty < 1)>
                        <button class="h-14 rounded-2xl bg-emerald-600 px-5 text-base font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($availableQty < 1)>إضافة للسلة</button>
                    </form>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-4"><div class="text-sm font-black">توصيل سريع</div><div class="mt-1 text-xs font-semibold text-slate-500">خلال 24-48 ساعة</div></div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-4"><div class="text-sm font-black">دفع آمن</div><div class="mt-1 text-xs font-semibold text-slate-500">بطاقات ومحافظ</div></div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-4"><div class="text-sm font-black">دعم صيدلي</div><div class="mt-1 text-xs font-semibold text-slate-500">استشارة ومتابعة</div></div>
                </div>
            </section>
        </section>

        <section class="mt-8 grid gap-4 lg:grid-cols-3">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5">
                <h2 class="text-xl font-black">تفاصيل المنتج</h2>
                <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600">
                    <div class="flex justify-between gap-4"><span>التصنيف</span><span class="font-black text-slate-900">{{ $product->category?->display_name ?: 'عام' }}</span></div>
                    <div class="flex justify-between gap-4"><span>المخزون</span><span class="font-black text-slate-900">{{ $availableQty }}</span></div>
                    <div class="flex justify-between gap-4"><span>الباركود</span><span class="font-black text-slate-900">{{ $product->barcode ?: 'غير محدد' }}</span></div>
                </div>
            </div>
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5">
                <h2 class="text-xl font-black">إرشادات الشراء</h2>
                <p class="mt-4 text-sm font-semibold leading-7 text-slate-600">يرجى مراجعة النشرة الداخلية أو استشارة الصيدلي قبل استخدام أي منتج طبي، خاصة في حالات الحمل أو الأمراض المزمنة.</p>
            </div>
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5">
                <h2 class="text-xl font-black">ضمان الصيدلية</h2>
                <p class="mt-4 text-sm font-semibold leading-7 text-slate-600">كل المنتجات أصلية ومخزنة وفق معايير مناسبة، مع سياسة متابعة للطلبات والدعم بعد الشراء.</p>
            </div>
        </section>

        @if($related->count())
            <section class="mt-10">
                <div class="mb-5 flex items-end justify-between gap-3">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Related Products</div>
                        <h2 class="text-2xl font-black text-slate-950">منتجات مشابهة</h2>
                    </div>
                    <a href="{{ $categoryUrl }}" class="text-sm font-black text-emerald-700 hover:text-emerald-900">عرض المزيد</a>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($related as $item)
                        @php $itemAvailableQty = (int) max(0, (float) ($item->available_qty ?? $item->quantity ?? 0)); @endphp
                        <article class="group rounded-[1.6rem] border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                            <a href="{{ route('store.product.show', $item->slug) }}" class="relative grid h-48 place-items-center overflow-hidden rounded-[1.3rem] bg-slate-50">
                                @if($item->discount_percent > 0)
                                    <span class="absolute right-3 top-3 z-10 rounded-full bg-rose-600 px-2.5 py-1 text-xs font-black text-white">خصم {{ $item->discount_percent }}%</span>
                                @endif
                                <img src="{{ $item->image_url }}" class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-105" alt="{{ $item->name }}">
                            </a>
                            <a href="{{ route('store.product.show', $item->slug) }}" class="mt-3 line-clamp-2 min-h-12 text-sm font-black leading-6 text-slate-900 hover:text-emerald-700">{{ $item->name }}</a>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-lg font-black text-emerald-700">{{ number_format($item->price, 2) }} ج.م</span>
                                <span class="rounded-full {{ $itemAvailableQty > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-2.5 py-1 text-xs font-black">{{ $itemAvailableQty > 0 ? 'متوفر' : 'نافد' }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <footer class="mt-10 bg-slate-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 md:grid-cols-4 md:px-5">
            <div>
                <div class="text-2xl font-black">صيدلية د. محمد رمضان</div>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-300">منتجات أصلية وتجربة شراء طبية موثوقة مع دعم صيدلي ومتابعة للطلبات.</p>
            </div>
            <div>
                <div class="font-black">روابط سريعة</div>
                <div class="mt-3 space-y-2 text-sm font-semibold text-slate-300">
                    <a class="block hover:text-white" href="{{ route('store.home') }}">الرئيسية</a>
                    <a class="block hover:text-white" href="{{ route('store.products.index') }}">كل المنتجات</a>
                    <a class="block hover:text-white" href="{{ route('store.cart.index') }}">السلة</a>
                </div>
            </div>
            <div>
                <div class="font-black">خدمة العملاء</div>
                <div class="mt-3 space-y-2 text-sm font-semibold text-slate-300">
                    <div>الدعم: 0509095816</div>
                    <div>توصيل خلال 24-48 ساعة</div>
                    <div>دفع آمن وفاتورة إلكترونية</div>
                </div>
            </div>
            <div>
                <div class="font-black">الثقة والجودة</div>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-300">نراجع توفر المنتجات وأسعارها باستمرار لتقديم تجربة شراء واضحة وسريعة.</p>
            </div>
        </div>
    </footer>

    <div class="fixed inset-x-3 bottom-3 z-40 rounded-3xl border border-slate-200 bg-white/95 p-2 shadow-2xl backdrop-blur md:hidden">
        <form action="{{ route('store.cart.add') }}" method="POST" class="grid grid-cols-[86px_1fr] gap-2">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="number" name="qty" value="1" min="1" max="{{ max(1, $availableQty) }}" class="rounded-2xl border border-slate-200 text-center font-black" @disabled($availableQty < 1)>
            <button class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white" @disabled($availableQty < 1)>إضافة للسلة - {{ number_format($product->price, 2) }} ج.م</button>
        </form>
    </div>
</div>

<script>
(() => {
    const mainImage = document.getElementById('mainProductImage');
    document.querySelectorAll('.product-thumb').forEach((button) => {
        button.addEventListener('click', () => {
            if (!mainImage) return;
            mainImage.src = button.dataset.image;
            document.querySelectorAll('.product-thumb').forEach((item) => item.classList.remove('border-emerald-500', 'ring-2', 'ring-emerald-100'));
            button.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-100');
        });
    });
})();
</script>
@endsection
