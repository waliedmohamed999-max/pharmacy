<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صيدلية د. محمد رمضان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
@php
    $cartCount = app(\App\Services\CartService::class)->summary()['count'] ?? 0;
    $navItems = [
        ['label' => 'الأدوية', 'url' => route('store.products.index'), 'icon' => 'pill'],
        ['label' => 'الفيتامينات', 'url' => route('store.products.index'), 'icon' => 'sparkles'],
        ['label' => 'المكملات', 'url' => route('store.products.index'), 'icon' => 'bolt'],
        ['label' => 'العناية بالطفل', 'url' => route('store.products.index'), 'icon' => 'shield'],
        ['label' => 'العناية بالبشرة', 'url' => route('store.products.index'), 'icon' => 'star'],
        ['label' => 'أجهزة طبية', 'url' => route('store.products.index'), 'icon' => 'box'],
        ['label' => 'السكري', 'url' => route('store.products.index'), 'icon' => 'shield'],
        ['label' => 'العروض', 'url' => route('store.products.index'), 'icon' => 'bolt'],
        ['label' => 'كل المنتجات', 'url' => route('store.products.index'), 'icon' => 'box'],
    ];
@endphp

@if(!View::hasSection('full_bleed'))
    <div class="border-b border-white/40 bg-emerald-800 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 overflow-x-auto px-4 py-2 text-[11px] font-bold md:px-5 md:text-xs">
            <div class="flex shrink-0 items-center gap-4 md:gap-6">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 18V6a2 2 0 0 0-2-2H3v14h11Z"/><path d="M14 9h4l3 3v6h-7Z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    شحن مجاني للطلبات فوق 500 ج.م
                </span>
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.11 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L8 9.7a16 16 0 0 0 6.3 6.3l1.26-1.25a2 2 0 0 1 2.11-.45c.84.27 1.72.47 2.61.59A2 2 0 0 1 22 16.92Z"/></svg>
                    الدعم: 0509095816
                </span>
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    توصيل خلال 24-48 ساعة
                </span>
            </div>
            <div class="flex shrink-0 items-center gap-3 md:gap-4">
                @auth
                    <a class="transition hover:text-emerald-100" href="{{ route('admin.dashboard') }}">حسابي</a>
                @else
                    <a class="transition hover:text-emerald-100" href="{{ route('login') }}">حسابي</a>
                @endauth
                <a class="transition hover:text-emerald-100" href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">English</a>
            </div>
        </div>
    </div>

    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-xl shadow-sm shadow-slate-900/5">
        <div class="mx-auto grid max-w-7xl grid-cols-12 items-center gap-2 px-3 py-3 sm:gap-3 md:px-5 md:py-4">
            <a href="{{ route('store.home') }}" class="col-span-8 flex min-w-0 items-center gap-2 md:col-span-3 md:gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 md:h-12 md:w-12">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black text-slate-950 sm:text-base md:text-lg">صيدلية د. محمد رمضان</span>
                    <span class="hidden text-xs font-bold text-emerald-600 sm:block">رعاية موثوقة وتسوق أسرع</span>
                </span>
            </a>

            <form action="{{ route('store.search') }}" class="relative order-3 col-span-12 md:order-none md:col-span-6">
                <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    name="q"
                    value="{{ request('q') }}"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-11 pl-24 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 md:h-14 md:rounded-3xl md:pr-12 md:pl-28"
                    placeholder="ابحث عن دواء، فيتامين، باركود أو منتج صحي"
                >
                <div class="absolute left-2 top-1/2 flex -translate-y-1/2 gap-1">
                    <button type="button" class="rounded-2xl p-2 text-slate-500 transition hover:bg-white hover:text-emerald-700" aria-label="بحث صوتي">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/></svg>
                    </button>
                    <button type="button" class="rounded-2xl p-2 text-slate-500 transition hover:bg-white hover:text-emerald-700" aria-label="بحث بالباركود">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 5v4"/><path d="M7 5v14"/><path d="M11 5v4"/><path d="M15 5v14"/><path d="M19 5v4"/><path d="M3 15v4"/><path d="M11 15v4"/><path d="M19 15v4"/></svg>
                    </button>
                </div>
            </form>

            <div class="col-span-4 flex items-center justify-end gap-1 md:col-span-3">
                <a href="#" class="hidden rounded-2xl p-3 text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:block" aria-label="الإشعارات">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.27 21a2 2 0 0 0 3.46 0"/><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/></svg>
                </a>
                <a href="#" class="hidden rounded-2xl p-3 text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:block" aria-label="المفضلة">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19.5 12.6 12 20l-7.5-7.4A5 5 0 0 1 12 6a5 5 0 0 1 7.5 6.6Z"/></svg>
                </a>
                <a href="{{ route('store.cart.index') }}" class="relative rounded-2xl p-2.5 text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:p-3" aria-label="السلة">
                    <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    <span class="absolute -top-1 -left-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-black text-white">{{ $cartCount }}</span>
                </a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-2xl p-2.5 text-slate-700 transition hover:bg-slate-100 md:p-3" aria-label="الحساب">
                        <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-2xl p-2.5 text-slate-700 transition hover:bg-slate-100 md:p-3" aria-label="الحساب">
                        <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <nav class="relative z-40 border-b border-slate-100 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-end gap-1 overflow-x-auto px-3 py-2 md:px-5">
            @foreach($navItems as $item)
                <a href="{{ $item['url'] }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 md:text-sm">
                    @if($item['icon'] === 'pill')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                    @elseif($item['icon'] === 'bolt')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M13 2 3 14h8l-1 8 11-13h-8l0-7Z"/></svg>
                    @elseif($item['icon'] === 'shield')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    @elseif($item['icon'] === 'star')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2Z"/></svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif

<main class="@if(!View::hasSection('full_bleed')) mx-auto max-w-7xl px-4 py-6 md:px-5 md:py-8 @endif">
    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

@php
    $footer = $footerSettings ?? [];
@endphp

@if(($footer['enabled'] ?? true) && !View::hasSection('full_bleed'))
    <footer class="mt-12 overflow-hidden bg-slate-950 text-white">
        <div class="relative">
            <div class="absolute -right-32 top-0 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-cyan-500/20 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 md:px-5">
                <div class="mb-8 grid gap-4 rounded-[2rem] border border-white/10 bg-white/[0.06] p-4 backdrop-blur md:grid-cols-4 md:p-5">
                    <div class="flex items-center gap-3 rounded-3xl bg-white/[0.06] p-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400/15 text-emerald-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        <div>
                            <div class="font-black">منتجات أصلية</div>
                            <div class="text-xs font-semibold text-white/55">مصادر موثوقة ومراجعة</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-3xl bg-white/[0.06] p-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-400/15 text-cyan-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 18V6a2 2 0 0 0-2-2H3v14h11Z"/><path d="M14 9h4l3 3v6h-7Z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                        </span>
                        <div>
                            <div class="font-black">توصيل سريع</div>
                            <div class="text-xs font-semibold text-white/55">خلال 24-48 ساعة</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-3xl bg-white/[0.06] p-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-400/15 text-amber-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        </span>
                        <div>
                            <div class="font-black">دفع آمن</div>
                            <div class="text-xs font-semibold text-white/55">حماية كاملة للطلب</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-3xl bg-white/[0.06] p-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-rose-400/15 text-rose-200">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.11 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg>
                        </span>
                        <div>
                            <div class="font-black">دعم مستمر</div>
                            <div class="text-xs font-semibold text-white/55">0509095816</div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-[1.15fr_.85fr_.85fr_1fr]">
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.06] p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-950/20">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m10.5 20.5 10-10a4.95 4.95 0 0 0-7-7l-10 10a4.95 4.95 0 0 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                            </span>
                            <div>
                                <h3 class="text-2xl font-black">{{ $footer['brand_title'] ?? 'صيدلية د. محمد رمضان' }}</h3>
                                <p class="text-xs font-black text-emerald-200">رعاية موثوقة وتسوق أسرع</p>
                            </div>
                        </div>
                        <p class="text-sm font-semibold leading-8 text-white/65">
                            {{ $footer['about'] ?? 'منصة صيدلية إلكترونية حديثة لطلب الأدوية والمنتجات الصحية، مع متابعة للطلبات وتجربة شراء آمنة وسريعة.' }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs font-black text-white/70">مرخصة</span>
                            <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs font-black text-white/70">منتجات أصلية</span>
                            <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs font-black text-white/70">توصيل موثوق</span>
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.06] p-6">
                        <h4 class="mb-4 text-xl font-black">{{ $footer['links_title'] ?? 'روابط مفيدة' }}</h4>
                        <ul class="space-y-3 text-sm font-semibold text-white/65">
                            <li><a class="flex items-center justify-between rounded-2xl px-3 py-2 transition hover:bg-white/10 hover:text-white" href="{{ route('store.home') }}"><span>الرئيسية</span><span>‹</span></a></li>
                            <li><a class="flex items-center justify-between rounded-2xl px-3 py-2 transition hover:bg-white/10 hover:text-white" href="{{ route('store.products.index') }}"><span>كل المنتجات</span><span>‹</span></a></li>
                            <li><a class="flex items-center justify-between rounded-2xl px-3 py-2 transition hover:bg-white/10 hover:text-white" href="{{ route('store.cart.index') }}"><span>السلة</span><span>‹</span></a></li>
                            @if(($footer['show_pages'] ?? true) && !empty($footerPages) && $footerPages->count())
                                @foreach($footerPages as $footerPage)
                                    <li><a href="{{ route('store.pages.show', $footerPage->slug) }}" class="flex items-center justify-between rounded-2xl px-3 py-2 transition hover:bg-white/10 hover:text-white"><span>{{ $footerPage->title }}</span><span>‹</span></a></li>
                                @endforeach
                            @endif
                        </ul>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.06] p-6">
                        <h4 class="mb-4 text-xl font-black">{{ $footer['contact_title'] ?? 'تواصل معنا' }}</h4>
                        <ul class="space-y-3 text-sm font-semibold text-white/65">
                            <li class="rounded-2xl bg-white/[0.06] px-3 py-3">{{ $footer['contact_address'] ?? 'خدمة عملاء الصيدلية' }}</li>
                            <li><a href="tel:{{ $footer['contact_phone'] ?? '0509095816' }}" class="block rounded-2xl bg-white/[0.06] px-3 py-3 hover:bg-white/10 hover:text-white">{{ $footer['contact_phone'] ?? '0509095816' }}</a></li>
                            <li><a href="mailto:{{ $footer['contact_email'] ?? 'support@pharmacy.local' }}" class="block rounded-2xl bg-white/[0.06] px-3 py-3 hover:bg-white/10 hover:text-white">{{ $footer['contact_email'] ?? 'support@pharmacy.local' }}</a></li>
                        </ul>
                    </div>

                    @if(($footer['newsletter_enabled'] ?? true))
                        <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.06] p-6">
                            <h4 class="mb-2 text-xl font-black">{{ $footer['newsletter_title'] ?? 'النشرة الإخبارية' }}</h4>
                            <p class="mb-4 text-sm font-semibold leading-7 text-white/65">{{ $footer['newsletter_text'] ?? 'اشترك لتصلك أحدث العروض والمنتجات الصحية والتنبيهات المهمة.' }}</p>
                            <form class="space-y-3" onsubmit="event.preventDefault();">
                                <input type="email" class="h-12 w-full rounded-2xl border border-white/15 bg-white px-4 text-sm font-bold text-slate-900 outline-none placeholder:text-slate-400" placeholder="البريد الإلكتروني">
                                <button type="button" class="h-12 w-full rounded-2xl bg-amber-400 px-4 text-sm font-black text-slate-950 shadow-lg shadow-amber-950/20 transition hover:bg-amber-300">اشتراك</button>
                            </form>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-black text-white/55">
                                <span class="rounded-full bg-white/[0.06] px-3 py-1">Visa</span>
                                <span class="rounded-full bg-white/[0.06] px-3 py-1">Mastercard</span>
                                <span class="rounded-full bg-white/[0.06] px-3 py-1">Cash</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex flex-col gap-4 border-t border-white/10 pt-5 text-sm font-semibold text-white/55 md:flex-row md:items-center md:justify-between">
                    <div>{{ $footer['copyright'] ?? ('© ' . date('Y') . ' صيدلية د. محمد رمضان') }}</div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-white/[0.06] px-3 py-1">سياسة الخصوصية</span>
                        <span class="rounded-full bg-white/[0.06] px-3 py-1">الشروط والأحكام</span>
                        <span class="rounded-full bg-white/[0.06] px-3 py-1">دفع آمن وشحن موثوق</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
@endif
</body>
</html>
