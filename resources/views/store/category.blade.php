@extends('store.layouts.app')

@section('full_bleed', true)

@section('content')
@php
    $cartCount = app(\App\Services\CartService::class)->summary()['count'] ?? 0;
    $title = $category->display_name ?? 'كل المنتجات';
    $subtitle = $pageSubtitle ?? 'تصفح منتجات الصيدلية الأصلية مع فلترة ذكية وتجربة شراء سريعة.';
    $totalProducts = method_exists($products, 'total') ? $products->total() : $products->count();
    $activeFilters = collect(['q', 'min_price', 'max_price', 'sort', 'in_stock'])->filter(fn ($key) => filled(request($key)))->count();
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
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-black">{{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 md:px-5 md:py-8">
        @if(session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ session('error') }}</div>
        @endif

        <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500">
            <a href="{{ route('store.home') }}" class="hover:text-emerald-700">الرئيسية</a>
            <span>/</span>
            <a href="{{ route('store.products.index') }}" class="hover:text-emerald-700">كل المنتجات</a>
            <span>/</span>
            <span class="text-slate-900">{{ $title }}</span>
        </nav>

        <section class="relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5 shadow-sm md:p-8">
            <div class="absolute -left-20 -top-20 h-60 w-60 rounded-full bg-emerald-200/35 blur-3xl"></div>
            <div class="absolute -bottom-24 right-20 h-64 w-64 rounded-full bg-cyan-200/35 blur-3xl"></div>

            <div class="relative grid gap-6 lg:grid-cols-[1fr_360px] lg:items-end">
                <div>
                    <div class="mb-3 inline-flex rounded-full bg-emerald-600/10 px-3 py-1 text-xs font-black uppercase text-emerald-700">Pharmacy Store</div>
                    <h1 class="text-3xl font-black leading-tight text-slate-950 md:text-5xl">{{ $title }}</h1>
                    <p class="mt-3 max-w-2xl text-base font-semibold leading-8 text-slate-600">{{ $subtitle }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm">
                        <div class="text-xs font-black text-slate-500">عدد المنتجات</div>
                        <div class="mt-2 text-3xl font-black text-emerald-700">{{ number_format($totalProducts) }}</div>
                    </div>
                    <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm">
                        <div class="text-xs font-black text-slate-500">الفلاتر النشطة</div>
                        <div class="mt-2 text-3xl font-black text-slate-950">{{ $activeFilters }}</div>
                    </div>
                </div>
            </div>
        </section>

        <form method="GET" class="mt-5 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[minmax(240px,1.4fr)_1fr_1fr_1fr_auto] md:items-end">
                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-500">بحث داخل القسم</span>
                    <input name="q" value="{{ request('q') }}" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="اسم المنتج / SKU / باركود">
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-500">أقل سعر</span>
                    <input name="min_price" value="{{ request('min_price') }}" inputmode="decimal" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="0.00">
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-500">أعلى سعر</span>
                    <input name="max_price" value="{{ request('max_price') }}" inputmode="decimal" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder="500.00">
                </label>

                <label class="block">
                    <span class="mb-2 block text-xs font-black text-slate-500">الترتيب</span>
                    <select name="sort" class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-black outline-none transition focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        <option value="">الأحدث</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>السعر من الأقل</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>السعر من الأعلى</option>
                        <option value="name_asc" @selected(request('sort') === 'name_asc')>الاسم أ - ي</option>
                    </select>
                </label>

                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex h-12 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-black text-slate-700">
                        <input type="checkbox" name="in_stock" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(request('in_stock'))>
                        متوفر فقط
                    </label>
                    <button class="h-12 rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">تطبيق</button>
                </div>
            </div>

            @if($activeFilters)
                <div class="mt-3">
                    <a href="{{ url()->current() }}" class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-200">مسح الفلاتر</a>
                </div>
            @endif
        </form>

        @if($products->count())
            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    @include('store.components.product-card', ['product' => $product])
                @endforeach
            </section>

            <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white px-4 py-3 shadow-sm">
                {{ $products->links() }}
            </div>
        @else
            <section class="mt-6 rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-emerald-50 text-3xl text-emerald-700">+</div>
                <h2 class="mt-4 text-2xl font-black text-slate-950">لا توجد منتجات مطابقة</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">جرّب تغيير الفلاتر أو الرجوع إلى كل المنتجات.</p>
                <a href="{{ route('store.products.index') }}" class="mt-5 inline-flex rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">عرض كل المنتجات</a>
            </section>
        @endif
    </main>

    <footer class="mt-10 bg-slate-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 md:grid-cols-4 md:px-5">
            <div>
                <div class="text-2xl font-black">صيدلية د. محمد رمضان</div>
                <p class="mt-3 text-sm font-semibold leading-7 text-slate-300">تجربة تسوق صحية موثوقة بمنتجات أصلية، فلاتر واضحة، ودعم صيدلي سريع.</p>
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
</div>
@endsection
