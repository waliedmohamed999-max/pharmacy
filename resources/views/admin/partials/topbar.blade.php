<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button id="openSidebar" class="admin-icon-button lg:hidden" type="button" aria-label="فتح القائمة">
                <span class="text-lg leading-none">☰</span>
            </button>

            <form action="{{ route('admin.products.index') }}" class="admin-global-search">
                <span class="admin-search-icon">⌕</span>
                <input
                    name="search"
                    class="admin-global-search-input"
                    placeholder="بحث ذكي: دواء، منتج، طلب، عميل، باركود..."
                    value="{{ request('search') }}"
                    aria-label="البحث داخل النظام"
                >
                <span class="admin-search-kbd">Ctrl K</span>
            </form>
        </div>

        <div class="admin-topbar-actions">
            <div class="admin-sync-pill hidden xl:flex">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,.16)]"></span>
                <span>النظام متصل</span>
            </div>

            <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="btn-secondary hidden md:inline-flex">
                عرض المتجر
            </a>

            <a href="{{ route('admin.products.create') }}" class="btn-primary">
                <span>+</span>
                إنشاء سريع
            </a>

            <button type="button" class="admin-icon-button relative" aria-label="الإشعارات">
                <span>🔔</span>
                <span class="absolute -left-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">8</span>
            </button>

            <div class="admin-user-card hidden md:flex">
                <span class="admin-user-avatar">{{ str(auth()->user()->name ?? 'A')->substr(0, 1) }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black text-slate-950">{{ auth()->user()->name }}</span>
                    <span class="block text-xs font-bold text-slate-500">مدير النظام</span>
                </span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-secondary">خروج</button>
            </form>
        </div>
    </div>
</header>
