<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صيدلية د. محمد رمضان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $cartCount = app(\App\Services\CartService::class)->summary()['count'] ?? 0;
@endphp
@if(!View::hasSection('full_bleed'))
<header class="sticky top-0 z-50 mx-3 mt-3 mb-4 neo-card px-3 md:px-5 py-3">
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('store.home') }}" class="text-lg md:text-xl font-extrabold whitespace-nowrap">صيدلية د. محمد رمضان</a>

        <form action="{{ route('store.search') }}" class="flex-1 min-w-[220px]">
            <input name="q" class="neo-input" placeholder="ابحث بالاسم أو الباركود" value="{{ request('q') }}">
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('store.cart.index') }}" class="neo-btn">السلة ({{ $cartCount }})</a>
            @auth
                <a href="{{ route('admin.dashboard') }}" class="neo-btn">حسابي</a>
            @else
                <a href="{{ route('login') }}" class="neo-btn">حسابي</a>
            @endauth
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="neo-btn uppercase">{{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}</a>
        </div>
    </div>
</header>
@endif

<main class="@if(!View::hasSection('full_bleed')) container mx-auto px-4 pb-10 @endif">
    @if(session('success'))
        <div class="neo-card p-3 mb-4 text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="neo-card p-3 mb-4 text-red-700">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>

@php
    $footer = $footerSettings ?? [];
@endphp

@if(($footer['enabled'] ?? true) && !View::hasSection('full_bleed'))
    <footer class="mt-10 bg-[#0b3d53] text-black">
        <div class="container mx-auto px-4 md:px-6 py-8 md:py-10">
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl p-4 md:p-5 bg-white/85 border border-white/60">
                    <h3 class="text-2xl font-black mb-2">{{ $footer['brand_title'] ?? 'صيدلية د. محمد رمضان' }}</h3>
                    @if(!empty($footer['about']))
                        <p class="text-sm leading-7 text-black">{{ $footer['about'] }}</p>
                    @endif
                </div>

                <div class="rounded-2xl p-4 md:p-5 bg-white/85 border border-white/60">
                    <h4 class="text-lg md:text-xl font-black mb-3">{{ $footer['links_title'] ?? 'روابط مفيدة' }}</h4>
                    <ul class="space-y-2 text-sm">
                        @if(!empty($footer['links']) && is_array($footer['links']))
                            @foreach($footer['links'] as $link)
                                <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="hover:underline">‹ {{ $link['label'] }}</a></li>
                            @endforeach
                        @endif
                        @if(($footer['show_pages'] ?? true) && !empty($footerPages) && $footerPages->count())
                            @foreach($footerPages as $footerPage)
                                <li><a href="{{ route('store.pages.show', $footerPage->slug) }}" class="hover:underline">‹ {{ $footerPage->title }}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                @if(($footer['newsletter_enabled'] ?? true))
                    <div class="rounded-2xl p-4 md:p-5 bg-white/85 border border-white/60">
                        <h4 class="text-lg md:text-xl font-black mb-3">{{ $footer['newsletter_title'] ?? 'النشرة الإخبارية' }}</h4>
                        @if(!empty($footer['newsletter_text']))
                            <p class="text-sm leading-7 text-black mb-2">{{ $footer['newsletter_text'] }}</p>
                        @endif
                        <form class="space-y-2" onsubmit="event.preventDefault();">
                            <input type="email" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-black placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-200" placeholder="أدخل عنوان البريد الإلكتروني">
                            <button type="button" class="inline-flex items-center justify-center rounded-xl bg-amber-400 text-slate-900 px-4 py-2 text-sm font-black hover:bg-amber-300 transition">الإشتراك</button>
                        </form>
                    </div>
                @endif

                <div class="rounded-2xl p-4 md:p-5 bg-white/85 border border-white/60">
                    <h4 class="text-lg md:text-xl font-black mb-3">{{ $footer['contact_title'] ?? 'اتصل بنا' }}</h4>
                    <ul class="space-y-2 text-sm">
                        @if(!empty($footer['contact_address']))
                            <li>{{ $footer['contact_address'] }}</li>
                        @endif
                        @if(!empty($footer['contact_phone']))
                            <li><a href="tel:{{ $footer['contact_phone'] }}" class="hover:underline">{{ $footer['contact_phone'] }}</a></li>
                        @endif
                        @if(!empty($footer['contact_email']))
                            <li><a href="mailto:{{ $footer['contact_email'] }}" class="hover:underline">{{ $footer['contact_email'] }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-white/35 text-center text-sm text-black">
                {{ $footer['copyright'] ?? ('© ' . date('Y') . ' صيدلية د. محمد رمضان') }}
            </div>
        </div>
    </footer>
@endif
</body>
</html>
