<aside id="adminSidebar" class="admin-sidebar fixed inset-y-0 right-0 z-50 w-[310px] max-w-[88vw] translate-x-full overflow-y-auto border-l border-white/70 bg-white/95 p-3 shadow-2xl backdrop-blur-xl transition lg:z-30 lg:translate-x-0 lg:shadow-none">
    <div class="mb-4 rounded-[1.6rem] bg-gradient-to-br from-emerald-700 to-teal-500 p-3 text-white">
        <div class="flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/18 text-base font-black">Rx</span>
            <a href="{{ route('admin.dashboard') }}" class="min-w-0 flex-1">
                <span class="block truncate text-sm font-black">صيدلية د. محمد رمضان</span>
                <span class="block text-xs font-semibold text-white/75">Pharmacy ERP Suite</span>
            </a>
            <button id="closeSidebar" class="rounded-xl bg-white/15 px-3 py-2 text-lg font-black lg:hidden" type="button">×</button>
        </div>
    </div>

    <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
        <div class="text-xs font-black text-slate-400">مساحة العمل</div>
        <div class="mt-1 flex items-center justify-between text-sm font-black text-slate-800">
            <span>الفرع الرئيسي</span>
            <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] text-emerald-700">نشط</span>
        </div>
    </div>

    <svg class="hidden" aria-hidden="true">
        <symbol id="admin-icon-dashboard" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></symbol>
        <symbol id="admin-icon-cart" viewBox="0 0 24 24"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></symbol>
        <symbol id="admin-icon-package" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></symbol>
        <symbol id="admin-icon-boxes" viewBox="0 0 24 24"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l3 1.8a2 2 0 0 0 2.06 0L12 19v-5l-5-3Z"/><path d="m7 16.5-4.74-2.85"/><path d="m7 16.5 5-3"/><path d="M7 16.5v5.17"/><path d="M12 13.5V19l3.97 2.38a2 2 0 0 0 2.06 0l3-1.8a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L17 10.5Z"/><path d="m17 16.5-5-3"/><path d="m17 16.5 4.74-2.85"/><path d="M17 16.5v5.17"/><path d="M7.97 4.42a2 2 0 0 0 0 3.16L12 10l4.03-2.42a2 2 0 0 0 0-3.16l-3-1.8a2 2 0 0 0-2.06 0Z"/><path d="M12 10V4.5"/><path d="m7 5.5 5 3 5-3"/></symbol>
        <symbol id="admin-icon-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
        <symbol id="admin-icon-chart" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></symbol>
        <symbol id="admin-icon-card" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></symbol>
        <symbol id="admin-icon-layout" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></symbol>
        <symbol id="admin-icon-image" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></symbol>
        <symbol id="admin-icon-file" viewBox="0 0 24 24"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></symbol>
        <symbol id="admin-icon-settings" viewBox="0 0 24 24"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.51a2 2 0 0 1 1-1.72l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/></symbol>
        <symbol id="admin-icon-external" viewBox="0 0 24 24"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></symbol>
        <symbol id="admin-icon-wallet" viewBox="0 0 24 24"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3v4a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5"/><path d="M18 12h.01"/></symbol>
        <symbol id="admin-icon-receipt" viewBox="0 0 24 24"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M10 16h4"/></symbol>
        <symbol id="admin-icon-user-cog" viewBox="0 0 24 24"><circle cx="18" cy="15" r="3"/><path d="M19.4 18.4 21 20"/><path d="M16.6 11.6 15 10"/><path d="M21 10l-1.6 1.6"/><path d="M15 20l1.6-1.6"/><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6"/></symbol>
    </svg>

    <nav class="space-y-5">
        <div>
            <div class="admin-nav-label">العمليات</div>
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-dashboard"></use></svg></span>
                    <span>لوحة التحكم</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-cart"></use></svg></span>
                    <span>الطلبات</span>
                    <span class="admin-nav-badge">8</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-package"></use></svg></span>
                    <span>المنتجات</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-boxes"></use></svg></span>
                    <span>التصنيفات</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-users"></use></svg></span>
                    <span>العملاء</span>
                </a>
            </div>
        </div>

        <div>
            <div class="admin-nav-label">المخزون والصيدلية</div>
            <div class="space-y-1">
                <a href="{{ route('admin.inventory.index') }}" class="sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-chart"></use></svg></span>
                    <span>المخزون</span>
                    <span class="admin-nav-badge danger">!</span>
                </a>
                <a href="{{ route('admin.pos.index') }}" class="sidebar-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-card"></use></svg></span>
                    <span>نقطة البيع POS</span>
                </a>
            </div>
        </div>

        <div>
            <div class="admin-nav-label">التحكم في الواجهة الخارجية</div>
            <div class="space-y-1">
                <a href="{{ route('admin.home-sections.index') }}" class="sidebar-link {{ request()->routeIs('admin.home-sections.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-layout"></use></svg></span>
                    <span>Home Builder</span>
                </a>
                <a href="{{ route('admin.banners.index') }}" class="sidebar-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-image"></use></svg></span>
                    <span>البنرات والتسويق</span>
                </a>
                <a href="{{ route('admin.pages.index') }}" class="sidebar-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-file"></use></svg></span>
                    <span>الصفحات</span>
                </a>
                <a href="{{ route('admin.footer.edit') }}" class="sidebar-link {{ request()->routeIs('admin.footer.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-settings"></use></svg></span>
                    <span>إعدادات الواجهة</span>
                </a>
                <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="sidebar-link">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-external"></use></svg></span>
                    <span>عرض المتجر</span>
                </a>
            </div>
        </div>

        <div>
            <div class="admin-nav-label">المالية والإدارة</div>
            <div class="space-y-1">
                <a href="{{ route('admin.finance.index') }}" class="sidebar-link {{ request()->routeIs('admin.finance.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-wallet"></use></svg></span>
                    <span>تقارير المالية</span>
                </a>
                <a href="{{ route('admin.accounting.index') }}" class="sidebar-link {{ request()->routeIs('admin.accounting.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-receipt"></use></svg></span>
                    <span>النظام المالي</span>
                </a>
                <a href="{{ route('admin.users.permissions.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.permissions.*') ? 'active' : '' }}">
                    <span class="admin-nav-icon"><svg><use href="#admin-icon-user-cog"></use></svg></span>
                    <span>الموظفون والصلاحيات</span>
                </a>
            </div>
        </div>
    </nav>
</aside>

<div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>
