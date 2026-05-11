<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button id="openSidebar" class="admin-icon-button lg:hidden" type="button" aria-label="فتح القائمة">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <form action="{{ route('admin.products.index') }}" class="admin-global-search">
                <span class="admin-search-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 21-4.3-4.3"/><circle cx="11" cy="11" r="7"/></svg>
                </span>
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
            <div class="admin-topbar-stat">
                <span class="h-2.5 w-2.5 rounded-full bg-sky-500 shadow-[0_0_0_4px_rgba(14,165,233,.14)]"></span>
                <span>مزامنة فورية</span>
            </div>

            <div class="admin-sync-pill hidden xl:flex">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,.16)]"></span>
                <span>النظام متصل</span>
            </div>

            <button id="adminCommandTrigger" type="button" class="btn-secondary hidden lg:inline-flex">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0 0-6"/></svg>
                Ctrl K
            </button>

            <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="btn-secondary hidden md:inline-flex">
                عرض المتجر
            </a>

            <a href="{{ route('admin.products.create') }}" class="btn-primary">
                <span>+</span>
                إنشاء سريع
            </a>

            <button id="adminThemeToggle" type="button" class="admin-icon-button" aria-label="تبديل الوضع">
                <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 1 0 9 9 8 8 0 1 1-9-9Z"/></svg>
                <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>

            <div class="relative">
                <button id="adminNotifyTrigger" type="button" class="admin-icon-button relative" aria-label="الإشعارات">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                    <span class="absolute -left-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">8</span>
                </button>
                <div id="adminNotificationsPanel" class="admin-floating-panel w-80 p-3">
                    <div class="mb-2 flex items-center justify-between px-2">
                        <h3 class="text-sm font-black text-slate-950">مركز الإشعارات</h3>
                        <span class="badge-danger">8 جديد</span>
                    </div>
                    <div class="space-y-1">
                        <a href="{{ route('admin.orders.index') }}" class="admin-notification-item">
                            <span class="admin-notification-dot"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-black text-slate-800">طلبات جديدة تحتاج متابعة</span>
                                <span class="block text-xs font-bold text-slate-500">راجع حالة الدفع والتجهيز والتوصيل</span>
                            </span>
                        </a>
                        <a href="{{ route('admin.inventory.alerts') }}" class="admin-notification-item">
                            <span class="admin-notification-dot !bg-rose-500 !shadow-[0_0_0_4px_rgba(244,63,94,.14)]"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-black text-slate-800">تنبيهات نواقص المخزون</span>
                                <span class="block text-xs font-bold text-slate-500">منتجات وصلت لحد إعادة الطلب</span>
                            </span>
                        </a>
                        <a href="{{ route('admin.finance.index') }}" class="admin-notification-item">
                            <span class="admin-notification-dot !bg-amber-500 !shadow-[0_0_0_4px_rgba(245,158,11,.14)]"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-black text-slate-800">مراجعة مالية يومية</span>
                                <span class="block text-xs font-bold text-slate-500">مطابقة الفواتير والتحصيلات</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

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

<div id="adminCommandOverlay" class="admin-command-overlay">
    <div class="admin-command-dialog">
        <div class="flex items-center gap-3 border-b border-slate-100 px-4">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 3a3 3 0 0 0-3 3v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0 0-6"/></svg>
            <input id="adminCommandInput" class="admin-command-input" placeholder="ابحث أو نفذ أمر سريع: منتج، طلب، مخزون، مالية..." aria-label="لوحة الأوامر">
            <button id="adminCommandClose" type="button" class="admin-icon-button h-10 w-10 shadow-none" aria-label="إغلاق">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="grid gap-1 p-3">
            <a href="{{ route('admin.products.create') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">+</span> إنشاء منتج جديد</span><span class="text-xs text-slate-400">Product</span></a>
            <a href="{{ route('admin.orders.index') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">#</span> إدارة الطلبات</span><span class="text-xs text-slate-400">Orders</span></a>
            <a href="{{ route('admin.pos.index') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">POS</span> نقطة البيع السريعة</span><span class="text-xs text-slate-400">Cashier</span></a>
            <a href="{{ route('admin.inventory.index') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">Inv</span> إدارة المخزون</span><span class="text-xs text-slate-400">Stock</span></a>
            <a href="{{ route('admin.home-sections.index') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">UI</span> التحكم في الواجهة الخارجية</span><span class="text-xs text-slate-400">Storefront</span></a>
            <a href="{{ route('admin.reports.index') }}" class="admin-command-item"><span class="inline-flex items-center gap-3"><span class="admin-command-icon">Rpt</span> مركز التقارير الموحد</span><span class="text-xs text-slate-400">Reports</span></a>
        </div>
    </div>
</div>
