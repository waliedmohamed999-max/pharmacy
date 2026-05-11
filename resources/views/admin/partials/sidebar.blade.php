@php
    $navGroups = [
        [
            'label' => 'العمليات',
            'items' => [
                ['label' => 'لوحة التحكم', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard'],
                ['label' => 'الطلبات', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'cart', 'badge' => '8'],
                ['label' => 'المنتجات', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'icon' => 'package'],
                ['label' => 'التصنيفات', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'boxes'],
                ['label' => 'العملاء', 'route' => 'admin.customers.index', 'match' => 'admin.customers.*', 'icon' => 'users'],
            ],
        ],
        [
            'label' => 'المخزون والصيدلية',
            'items' => [
                ['label' => 'المخزون', 'route' => 'admin.inventory.index', 'match' => 'admin.inventory.*', 'icon' => 'chart', 'badge' => '!'],
                ['label' => 'نقطة البيع POS', 'route' => 'admin.pos.index', 'match' => 'admin.pos.*', 'icon' => 'card'],
                ['label' => 'سند استلام', 'route' => 'admin.inventory.receive.form', 'match' => 'admin.inventory.receive.*', 'icon' => 'receipt'],
                ['label' => 'تنبيهات النواقص', 'route' => 'admin.inventory.alerts', 'match' => 'admin.inventory.alerts', 'icon' => 'alert'],
            ],
        ],
        [
            'label' => 'الواجهة الخارجية',
            'items' => [
                ['label' => 'Home Builder', 'route' => 'admin.home-sections.index', 'match' => 'admin.home-sections.*', 'icon' => 'layout'],
                ['label' => 'البنرات والتسويق', 'route' => 'admin.banners.index', 'match' => 'admin.banners.*', 'icon' => 'image'],
                ['label' => 'الصفحات', 'route' => 'admin.pages.index', 'match' => 'admin.pages.*', 'icon' => 'file'],
                ['label' => 'إعدادات الواجهة', 'route' => 'admin.footer.edit', 'match' => 'admin.footer.*', 'icon' => 'settings'],
            ],
        ],
        [
            'label' => 'المالية والإدارة',
            'items' => [
                ['label' => 'تقارير المالية', 'route' => 'admin.finance.index', 'match' => 'admin.finance.*', 'icon' => 'wallet'],
                ['label' => 'النظام المالي', 'route' => 'admin.accounting.index', 'match' => 'admin.accounting.*', 'icon' => 'receipt'],
                ['label' => 'الموظفون والصلاحيات', 'route' => 'admin.users.permissions.index', 'match' => 'admin.users.permissions.*', 'icon' => 'user-cog'],
            ],
        ],
    ];

    $iconPath = [
        'dashboard' => 'M3 3h7v8H3z M14 3h7v5h-7z M14 12h7v9h-7z M3 16h7v5H3z',
        'cart' => 'M3 4h2l2.3 10.5a2 2 0 0 0 2 1.5h7.9a2 2 0 0 0 2-1.5L21 8H6 M9 21h.01 M18 21h.01',
        'package' => 'M12 3 21 8v8l-9 5-9-5V8z M3 8l9 5 9-5 M12 13v8',
        'boxes' => 'M7 7h10v10H7z M3 11h4v8H3z M17 11h4v8h-4z',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87',
        'chart' => 'M3 3v18h18 M8 17v-5 M13 17V7 M18 17v-9',
        'card' => 'M3 6h18v12H3z M3 10h18',
        'receipt' => 'M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1 2-1V3z M9 8h6 M9 12h6 M9 16h4',
        'alert' => 'M12 3 22 20H2z M12 9v4 M12 17h.01',
        'layout' => 'M4 4h16v16H4z M4 10h16 M10 20V10',
        'image' => 'M4 5h16v14H4z M8 10h.01 M20 15l-4-4-8 8',
        'file' => 'M14 3H6v18h12V7z M14 3v4h4 M9 13h6 M9 17h6',
        'settings' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M4 12h2 M18 12h2 M12 4v2 M12 18v2',
        'wallet' => 'M4 7h16v13H4z M4 7a3 3 0 0 1 3-3h10v3 M17 14h.01',
        'user-cog' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M2 21v-2a4 4 0 0 1 4-4h5 M18 12a3 3 0 1 0 0 6 3 3 0 0 0 0-6 M21 15h1 M14 15h1',
    ];
@endphp

<aside id="adminSidebar" class="admin-sidebar fixed inset-y-0 right-0 z-50 w-[320px] max-w-[88vw] translate-x-full overflow-y-auto border-l border-white/70 bg-white/95 p-3 shadow-2xl backdrop-blur-xl transition lg:z-30 lg:translate-x-0 lg:shadow-none">
    <div class="admin-brand-card">
        <div class="flex items-center gap-3">
            <span class="admin-brand-logo">Rx</span>
            <a href="{{ route('admin.dashboard') }}" class="min-w-0 flex-1">
                <span class="block truncate text-sm font-black">صيدلية د. محمد رمضان</span>
                <span class="block text-xs font-semibold text-white/75">Medical ERP Suite</span>
            </a>
            <button id="closeSidebar" class="rounded-xl bg-white/15 px-3 py-2 text-lg font-black lg:hidden" type="button" aria-label="إغلاق القائمة">×</button>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2 text-xs font-black">
            <a href="{{ route('admin.pos.index') }}" class="admin-brand-action">POS سريع</a>
            <a href="{{ route('admin.products.create') }}" class="admin-brand-action">منتج جديد</a>
        </div>
    </div>

    <div class="admin-workspace-card">
        <div>
            <div class="text-xs font-black text-slate-400">مساحة العمل</div>
            <div class="mt-1 text-sm font-black text-slate-900">الفرع الرئيسي</div>
        </div>
        <span class="badge-success">نشط</span>
    </div>

    <nav class="space-y-5 pb-6">
        @foreach($navGroups as $group)
            <div>
                <div class="admin-nav-label">{{ $group['label'] }}</div>
                <div class="space-y-1">
                    @foreach($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                            <span class="admin-nav-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $iconPath[$item['icon']] }}"></path></svg>
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="admin-nav-badge {{ $item['badge'] === '!' ? 'danger' : '' }}">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div>
            <div class="admin-nav-label">روابط سريعة</div>
            <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="sidebar-link">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24"><path d="M15 3h6v6 M10 14 21 3 M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                </span>
                <span class="min-w-0 flex-1 truncate">عرض المتجر</span>
            </a>
        </div>
    </nav>
</aside>

<div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-950/45 backdrop-blur-sm lg:hidden"></div>
