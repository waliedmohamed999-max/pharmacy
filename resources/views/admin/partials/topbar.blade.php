<header class="admin-topbar">
    <div class="flex flex-wrap items-center gap-3 px-3 py-3 md:px-5 lg:px-6">
        <button id="openSidebar" class="btn-secondary lg:hidden" type="button">☰</button>

        <form action="{{ route('admin.products.index') }}" class="order-3 flex-1 basis-full md:order-none md:basis-auto">
            <div class="relative">
                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                <input name="search" class="input-premium h-12 pr-10" placeholder="بحث ذكي: منتج، طلب، عميل، باركود..." value="{{ request('search') }}">
            </div>
        </form>

        <div class="mr-auto flex items-center gap-2">
            <a href="{{ route('store.home') }}" target="_blank" class="btn-secondary hidden md:inline-flex">عرض المتجر</a>
            <a href="{{ route('admin.products.create') }}" class="btn-primary">+ إنشاء سريع</a>

            <button type="button" class="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 shadow-sm transition hover:bg-emerald-50 hover:text-emerald-700">
                🔔
                <span class="absolute -left-1 -top-1 h-3 w-3 rounded-full bg-rose-500"></span>
            </button>

            <div class="hidden items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm md:flex">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-100 text-sm font-black text-emerald-800">A</span>
                <span class="text-sm">
                    <span class="block font-black text-slate-900">{{ auth()->user()->name }}</span>
                    <span class="block text-xs font-semibold text-slate-500">مدير النظام</span>
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-secondary">خروج</button>
            </form>
        </div>
    </div>
</header>
