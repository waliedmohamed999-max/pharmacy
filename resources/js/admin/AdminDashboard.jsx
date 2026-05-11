import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { useReactTable, getCoreRowModel, getPaginationRowModel, getSortedRowModel, flexRender } from '@tanstack/react-table';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Activity,
    AlertTriangle,
    BarChart3,
    Bell,
    Boxes,
    BriefcaseMedical,
    Building2,
    ChevronDown,
    ChevronsRight,
    Command,
    CreditCard,
    FileText,
    Home,
    Image,
    LayoutDashboard,
    LayoutTemplate,
    Menu,
    Moon,
    Package,
    Percent,
    Pill,
    Plus,
    Receipt,
    Search,
    Settings,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Sparkles,
    Sun,
    Truck,
    UserCog,
    Users,
    Wallet,
    Zap,
    X,
} from 'lucide-react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Button } from '../components/ui/button';
import { Badge, Card } from '../components/ui/card';
import { cn } from '../lib/utils';
import { useAdminStore } from './store';

const queryClient = new QueryClient();

const navGroups = [
    {
        label: 'العمليات',
        items: [
            ['Dashboard', 'لوحة التحكم', LayoutDashboard, 'dashboard', null],
            ['Orders', 'الطلبات', ShoppingCart, 'orders', 8],
            ['Products', 'المنتجات', Package, 'products', null],
            ['Categories', 'التصنيفات', Boxes, 'categories', null],
            ['Customers', 'العملاء', Users, 'customers', null],
        ],
    },
    {
        label: 'المخزون والصيدلية',
        items: [
            ['Inventory', 'المخزون', BarChart3, 'inventory', 5],
            ['Prescriptions', 'الروشتات', FileText, 'dashboard', 14],
            ['Branches', 'الفروع', Building2, 'dashboard', null],
            ['POS', 'نقطة البيع POS', CreditCard, 'pos', null],
            ['Suppliers', 'الموردون', Truck, 'accounting', null],
            ['Purchases', 'المشتريات', Receipt, 'accounting', null],
        ],
    },
    {
        label: 'التحكم في الواجهة الخارجية',
        items: [
            ['HomeBuilder', 'Home Builder', LayoutTemplate, 'homeSections', null],
            ['Marketing', 'البنرات والتسويق', Image, 'banners', null],
            ['Pages', 'الصفحات', FileText, 'pages', null],
            ['StoreSettings', 'إعدادات الواجهة', Settings, 'footer', null],
            ['Storefront', 'عرض المتجر', Home, 'storefront', null],
        ],
    },
    {
        label: 'المالية والإدارة',
        items: [
            ['Finance', 'المركز المالي', Wallet, 'finance', null],
            ['Accounting', 'النظام المحاسبي', Receipt, 'accounting', null],
            ['Employees', 'الموظفون والصلاحيات', UserCog, 'users', null],
            ['Permissions', 'الأدوار والصلاحيات', ShieldCheck, 'users', null],
            ['Coupons', 'الكوبونات', Percent, 'banners', null],
            ['Notifications', 'الإشعارات', Bell, 'dashboard', 12],
        ],
    },
    {
        label: 'التقارير والتحليلات',
        items: [
            ['Reports', 'مركز التقارير', Activity, 'reports', null],
        ],
    },
];

function formatMoney(value) {
    return `${new Intl.NumberFormat('ar-EG', { maximumFractionDigits: 0 }).format(Number(value || 0))} ج.م`;
}

function Sidebar({ routes }) {
    const collapsed = useAdminStore((state) => state.collapsed);
    const toggleCollapsed = useAdminStore((state) => state.toggleCollapsed);
    return (
        <aside className={cn('sticky top-0 hidden h-screen shrink-0 border-l border-slate-200 bg-white/90 p-3 backdrop-blur-xl transition-all dark:border-slate-800 dark:bg-slate-950/90 lg:block', collapsed ? 'w-[88px]' : 'w-[310px]')}>
            <div className="flex h-full flex-col">
                <div className="mb-4 flex items-center gap-3 rounded-3xl bg-gradient-to-br from-medical-700 to-teal-500 p-3 text-white">
                    <div className="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/20"><Pill /></div>
                    {!collapsed && (
                        <div className="min-w-0">
                            <div className="truncate text-sm font-black">صيدلية د. محمد رمضان</div>
                            <div className="text-xs font-semibold text-white/75">Enterprise ERP</div>
                        </div>
                    )}
                    <button onClick={toggleCollapsed} className="mr-auto rounded-xl p-2 hover:bg-white/15"><ChevronsRight className={cn('transition', collapsed && 'rotate-180')} size={17} /></button>
                </div>

                {!collapsed && (
                    <button className="mb-4 flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-black text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <span className="inline-flex items-center gap-2"><Building2 size={17} /> الفرع الرئيسي</span>
                        <ChevronDown size={16} />
                    </button>
                )}

                <div className="flex-1 overflow-y-auto pr-1">
                    {navGroups.map((group) => (
                        <div key={group.label} className="mb-5">
                            {!collapsed && <div className="admin-nav-label">{group.label}</div>}
                            <div className="space-y-1">
                                {group.items.map(([key, label, Icon, routeKey, badge]) => (
                                    <a
                                        key={key}
                                        href={routes[routeKey] || routes.dashboard}
                                        className={cn(
                                            'sidebar-link',
                                            key === 'Dashboard' && 'active'
                                        )}
                                    >
                                        <span className="admin-nav-icon">
                                            <Icon size={17} />
                                        </span>
                                        {!collapsed && <span className="flex-1">{label}</span>}
                                        {!collapsed && badge && <span className={cn('admin-nav-badge', key === 'Inventory' && 'danger')}>{badge}</span>}
                                    </a>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </aside>
    );
}

function Topbar({ routes }) {
    const { dark, toggleDark, setCommandOpen, notificationsOpen, setNotificationsOpen } = useAdminStore();
    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-slate-50/85 px-4 py-3 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/85">
            <div className="flex items-center gap-3">
                <Button variant="secondary" className="lg:hidden"><Menu size={18} /></Button>
                <button onClick={() => setCommandOpen(true)} className="flex h-12 flex-1 items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-500 shadow-sm transition hover:border-medical-200 hover:text-medical-700 dark:border-slate-800 dark:bg-slate-900">
                    <Search size={18} /> ابحث عن منتج، طلب، عميل، روشتة أو فاتورة
                    <span className="mr-auto hidden rounded-lg bg-slate-100 px-2 py-1 text-xs dark:bg-slate-800">Ctrl K</span>
                </button>
                <Button onClick={() => setCommandOpen(true)}><Plus size={18} /> إنشاء سريع</Button>
                <button onClick={toggleDark} className="rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-white">
                    {dark ? <Sun size={18} /> : <Moon size={18} />}
                </button>
                <div className="relative">
                    <button onClick={() => setNotificationsOpen(!notificationsOpen)} className="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-white">
                        <Bell size={18} />
                        <span className="absolute -top-1 -left-1 h-3 w-3 rounded-full bg-rose-500" />
                    </button>
                    <AnimatePresence>
                        {notificationsOpen && <Notifications />}
                    </AnimatePresence>
                </div>
                <div className="hidden items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900 md:flex">
                    <div className="grid h-9 w-9 place-items-center rounded-xl bg-medical-100 font-black text-medical-800">A</div>
                    <div className="text-sm"><div className="font-black dark:text-white">Admin</div><div className="text-xs text-slate-500">مدير النظام</div></div>
                </div>
            </div>
        </header>
    );
}

function Notifications() {
    const items = ['طلب جديد يحتاج مراجعة', '5 منتجات أقل من حد إعادة الطلب', 'روشتة جديدة قيد الفحص', 'تنبيه مالي: فاتورة مستحقة'];
    return (
        <motion.div initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 8 }} className="absolute left-0 top-[calc(100%+10px)] w-80 rounded-3xl border border-slate-200 bg-white p-3 shadow-2xl dark:border-slate-800 dark:bg-slate-950">
            <div className="mb-2 px-2 text-sm font-black dark:text-white">مركز الإشعارات</div>
            {items.map((item, index) => (
                <div key={item} className="flex items-start gap-3 rounded-2xl p-3 hover:bg-slate-50 dark:hover:bg-slate-900">
                    <div className={cn('mt-1 h-2.5 w-2.5 rounded-full', index === 1 ? 'bg-rose-500' : 'bg-medical-500')} />
                    <div className="text-sm font-bold text-slate-700 dark:text-slate-200">{item}</div>
                </div>
            ))}
        </motion.div>
    );
}

function KpiCard({ item, index }) {
    const icons = [Wallet, ShoppingCart, ClockIcon, Boxes, AlertTriangle, Users, FileText, Building2];
    const Icon = icons[index] || Activity;
    return (
        <motion.div initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: index * 0.04 }}>
            <Card className="group overflow-hidden p-4 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-900/10">
                <div className="flex items-center justify-between">
                    <div className="rounded-2xl bg-medical-50 p-3 text-medical-700 dark:bg-medical-900/25"><Icon size={22} /></div>
                    <Badge className={String(item.trend).startsWith('-') ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}>{item.trend}</Badge>
                </div>
                <div className="mt-4 text-sm font-bold text-slate-500">{item.label}</div>
                <div className="mt-1 text-2xl font-black text-slate-950 dark:text-white">{item.type === 'money' ? formatMoney(item.value) : new Intl.NumberFormat('ar-EG').format(item.value)}</div>
                <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div className="h-full w-2/3 rounded-full bg-gradient-to-l from-medical-600 to-teal-400" /></div>
            </Card>
        </motion.div>
    );
}

function ClockIcon(props) {
    return <Activity {...props} />;
}

function Analytics({ charts }) {
    return (
        <div className="grid gap-4 xl:grid-cols-[1.6fr_1fr]">
            <Card className="p-5">
                <div className="mb-5 flex items-center justify-between">
                    <div><div className="text-sm font-black text-medical-600">Revenue Analytics</div><h2 className="text-xl font-black dark:text-white">تحليل الإيرادات والطلبات</h2></div>
                    <Badge className="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">آخر 8 أشهر</Badge>
                </div>
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={charts.revenue}>
                            <defs><linearGradient id="revenue" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#059669" stopOpacity={0.35}/><stop offset="95%" stopColor="#059669" stopOpacity={0}/></linearGradient></defs>
                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                            <XAxis dataKey="month" tickLine={false} axisLine={false} />
                            <YAxis tickLine={false} axisLine={false} />
                            <Tooltip formatter={(value, name) => [name === 'revenue' ? formatMoney(value) : value, name === 'revenue' ? 'الإيراد' : 'الطلبات']} />
                            <Area type="monotone" dataKey="revenue" stroke="#059669" strokeWidth={3} fill="url(#revenue)" />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            </Card>
            <Card className="p-5">
                <div className="mb-5"><div className="text-sm font-black text-medical-600">Branches</div><h2 className="text-xl font-black dark:text-white">أداء الفروع</h2></div>
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={charts.branches} layout="vertical">
                            <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="#e2e8f0" />
                            <XAxis type="number" hide />
                            <YAxis dataKey="name" type="category" width={95} tickLine={false} axisLine={false} />
                            <Tooltip />
                            <Bar dataKey="sales" fill="#14b8a6" radius={[10, 10, 10, 10]} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </Card>
        </div>
    );
}

function SmartOpsPanel({ routes, lowStock, orders }) {
    const ops = [
        ['نقطة بيع سريعة', 'وضع الكاشير والباركود', CreditCard, routes.pos, 'from-emerald-700 to-teal-500'],
        ['تسوية مخزون', 'حركات وتحديث كميات', Boxes, routes.inventory, 'from-cyan-700 to-sky-500'],
        ['مركز التقارير', 'مالية ومخزون ومبيعات', Activity, routes.reports || routes.finance, 'from-slate-800 to-slate-600'],
        ['إدارة الواجهة', 'بنرات وأقسام المتجر', LayoutTemplate, routes.homeSections, 'from-violet-700 to-fuchsia-500'],
    ];
    const activeOrders = orders.filter((order) => ['new', 'preparing'].includes(order.status)).length;
    return (
        <div className="grid gap-4 xl:grid-cols-[1.25fr_.75fr]">
            <Card className="overflow-hidden p-5">
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <div className="text-sm font-black text-medical-600">Smart Workflows</div>
                        <h2 className="text-xl font-black dark:text-white">مركز التشغيل السريع</h2>
                    </div>
                    <Badge className="bg-emerald-50 text-emerald-700">ERP Ready</Badge>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {ops.map(([title, subtitle, Icon, href, gradient]) => (
                        <a key={title} href={href} className={cn('group rounded-[1.4rem] bg-gradient-to-br p-4 text-white shadow-lg transition hover:-translate-y-1 hover:shadow-2xl', gradient)}>
                            <div className="mb-5 grid h-12 w-12 place-items-center rounded-2xl bg-white/16 ring-1 ring-white/20"><Icon size={22} /></div>
                            <div className="font-black">{title}</div>
                            <div className="mt-1 text-xs font-bold text-white/75">{subtitle}</div>
                        </a>
                    ))}
                </div>
            </Card>

            <Card className="p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-xl font-black dark:text-white">نبض الصيدلية</h2>
                    <Activity className="text-medical-600" size={20} />
                </div>
                <div className="grid gap-3">
                    <PulseRow icon={ShoppingCart} label="طلبات قيد التنفيذ" value={activeOrders} tone="emerald" />
                    <PulseRow icon={AlertTriangle} label="نواقص مخزون حرجة" value={lowStock.length} tone="rose" />
                    <PulseRow icon={BriefcaseMedical} label="روشتات تحتاج مراجعة" value={14} tone="sky" />
                </div>
            </Card>
        </div>
    );
}

function ExecutiveCommandCenter({ routes, orders, lowStock, products }) {
    const pendingOrders = orders.filter((order) => ['new', 'preparing'].includes(order.status));
    const stockRisk = lowStock.slice(0, 3);
    const bestProducts = products.slice(0, 3);
    const lanes = [
        {
            title: 'الطلبات النشطة',
            subtitle: `${pendingOrders.length} طلب قيد التنفيذ`,
            icon: ShoppingCart,
            tone: 'emerald',
            href: routes.orders,
            items: pendingOrders.slice(0, 3).map((order) => `#${order.id} - ${order.customer}`),
        },
        {
            title: 'مخاطر المخزون',
            subtitle: `${lowStock.length} صنف يحتاج متابعة`,
            icon: AlertTriangle,
            tone: 'rose',
            href: routes.inventory,
            items: stockRisk.map((item) => `${item.name} (${item.quantity})`),
        },
        {
            title: 'أداء المنتجات',
            subtitle: 'الأصناف الأحدث في الكتالوج',
            icon: Package,
            tone: 'sky',
            href: routes.products,
            items: bestProducts.map((item) => `${item.name} - ${formatMoney(item.price)}`),
        },
    ];

    return (
        <div className="grid gap-4 xl:grid-cols-3">
            {lanes.map((lane, index) => (
                <motion.a
                    key={lane.title}
                    href={lane.href}
                    initial={{ opacity: 0, y: 14 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: .08 + index * .04 }}
                    className="group overflow-hidden rounded-[1.7rem] border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-medical-200 hover:shadow-2xl hover:shadow-medical-900/10 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className={cn('grid h-12 w-12 place-items-center rounded-2xl', lane.tone === 'rose' ? 'bg-rose-50 text-rose-700' : lane.tone === 'sky' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700')}>
                            <lane.icon size={22} />
                        </div>
                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 transition group-hover:bg-medical-50 group-hover:text-medical-700 dark:bg-slate-800">فتح</span>
                    </div>
                    <div className="mt-4">
                        <h3 className="text-lg font-black dark:text-white">{lane.title}</h3>
                        <p className="mt-1 text-sm font-bold text-slate-500">{lane.subtitle}</p>
                    </div>
                    <div className="mt-4 space-y-2">
                        {lane.items.length ? lane.items.map((item) => (
                            <div key={item} className="line-clamp-1 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 dark:bg-slate-950 dark:text-slate-300">{item}</div>
                        )) : (
                            <div className="rounded-2xl border border-dashed border-slate-200 px-3 py-5 text-center text-xs font-bold text-slate-400 dark:border-slate-700">لا توجد عناصر حرجة حاليا</div>
                        )}
                    </div>
                </motion.a>
            ))}
        </div>
    );
}

function PharmacyWorkflowBoard({ routes, orders, lowStock }) {
    const stages = [
        { label: 'استلام الطلب', count: orders.filter((order) => order.status === 'new').length, icon: Bell, color: 'bg-sky-500' },
        { label: 'تجهيز الصيدلي', count: orders.filter((order) => order.status === 'preparing').length, icon: BriefcaseMedical, color: 'bg-indigo-500' },
        { label: 'الشحن والتسليم', count: orders.filter((order) => order.status === 'shipped').length, icon: Truck, color: 'bg-violet-500' },
        { label: 'نواقص حرجة', count: lowStock.length, icon: AlertTriangle, color: 'bg-rose-500' },
    ];

    return (
        <Card className="overflow-hidden p-5">
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="text-sm font-black text-medical-600">Live Pharmacy Operations</div>
                    <h2 className="text-xl font-black dark:text-white">مسار التشغيل اليومي</h2>
                </div>
                <div className="flex gap-2">
                    <a href={routes.pos}><Button variant="secondary"><Zap size={16} /> POS</Button></a>
                    <a href={routes.inventory}><Button variant="secondary">تحديث المخزون</Button></a>
                </div>
            </div>
            <div className="grid gap-3 md:grid-cols-4">
                {stages.map((stage, index) => (
                    <div key={stage.label} className="relative rounded-[1.4rem] border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex items-center justify-between">
                            <span className={cn('grid h-11 w-11 place-items-center rounded-2xl text-white', stage.color)}><stage.icon size={19} /></span>
                            <span className="text-2xl font-black dark:text-white">{stage.count}</span>
                        </div>
                        <div className="mt-4 text-sm font-black text-slate-700 dark:text-slate-200">{stage.label}</div>
                        <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-white dark:bg-slate-800">
                            <div className={cn('h-full rounded-full', stage.color)} style={{ width: `${Math.min(100, 24 + stage.count * 12)}%` }} />
                        </div>
                        {index < stages.length - 1 && <div className="absolute left-[-18px] top-1/2 hidden h-px w-8 bg-slate-200 md:block dark:bg-slate-700" />}
                    </div>
                ))}
            </div>
        </Card>
    );
}

function FinanceInventorySnapshot({ routes, kpis }) {
    const revenue = kpis.find((item) => item.key === 'revenue')?.value || 0;
    const inventory = kpis.find((item) => item.key === 'inventoryValue')?.value || 0;
    const lowStock = kpis.find((item) => item.key === 'lowStock')?.value || 0;
    const cards = [
        ['صافي الإيراد', formatMoney(revenue), 'تحليل فواتير المبيعات', routes.finance, Wallet],
        ['قيمة المخزون', formatMoney(inventory), 'تكلفة الأصناف الحالية', routes.inventory, Boxes],
        ['حد إعادة الطلب', new Intl.NumberFormat('ar-EG').format(lowStock), 'أصناف تحتاج قرار شراء', routes.inventory, AlertTriangle],
    ];

    return (
        <Card className="p-5">
            <div className="mb-5 flex items-center justify-between">
                <div>
                    <div className="text-sm font-black text-medical-600">Accounting + Inventory</div>
                    <h2 className="text-xl font-black dark:text-white">ملخص المدير المالي والتشغيلي</h2>
                </div>
                <a href={routes.accounting}><Button variant="secondary">فتح النظام المالي</Button></a>
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                {cards.map(([label, value, desc, href, Icon]) => (
                    <a key={label} href={href} className="rounded-[1.4rem] border border-slate-100 bg-gradient-to-br from-white to-emerald-50/50 p-4 transition hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:from-slate-950 dark:to-slate-900">
                        <div className="mb-5 flex items-center justify-between">
                            <span className="grid h-11 w-11 place-items-center rounded-2xl bg-medical-50 text-medical-700"><Icon size={19} /></span>
                            <Sparkles size={17} className="text-medical-500" />
                        </div>
                        <div className="text-xs font-black text-slate-500">{label}</div>
                        <div className="mt-1 text-2xl font-black dark:text-white">{value}</div>
                        <div className="mt-2 text-xs font-bold text-slate-500">{desc}</div>
                    </a>
                ))}
            </div>
        </Card>
    );
}

function PrescriptionAndCareCenter({ routes, orders, products }) {
    const waiting = orders.filter((order) => ['new', 'preparing'].includes(order.status)).slice(0, 4);
    const careTools = [
        ['مراجعة الروشتات', 'قائمة انتظار الصيدلي', FileText, routes.orders],
        ['بدائل الدواء', 'اقتراح بدائل عند نقص المخزون', Pill, routes.products],
        ['متابعة العملاء', 'إشعارات إعادة الطلب', Users, routes.customers],
        ['سلامة التشغيل', 'صلاحيات وسجل إجراءات', ShieldCheck, routes.users],
    ];

    return (
        <div className="grid gap-4 xl:grid-cols-[.95fr_1.05fr]">
            <Card className="p-5">
                <div className="mb-5 flex items-center justify-between">
                    <div>
                        <div className="text-sm font-black text-medical-600">Pharmacist Care Desk</div>
                        <h2 className="text-xl font-black dark:text-white">مركز رعاية الصيدلي</h2>
                    </div>
                    <Badge className="bg-sky-50 text-sky-700">Clinical Ready</Badge>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    {careTools.map(([title, subtitle, Icon, href]) => (
                        <a key={title} href={href} className="rounded-[1.4rem] border border-slate-100 bg-slate-50 p-4 transition hover:-translate-y-1 hover:border-medical-200 hover:bg-medical-50 dark:border-slate-800 dark:bg-slate-950">
                            <span className="mb-4 grid h-11 w-11 place-items-center rounded-2xl bg-white text-medical-700 shadow-sm dark:bg-slate-900"><Icon size={19} /></span>
                            <div className="font-black text-slate-900 dark:text-white">{title}</div>
                            <div className="mt-1 text-xs font-bold text-slate-500">{subtitle}</div>
                        </a>
                    ))}
                </div>
            </Card>
            <Card className="p-5">
                <div className="mb-5 flex items-center justify-between">
                    <div>
                        <div className="text-sm font-black text-medical-600">Activity Timeline</div>
                        <h2 className="text-xl font-black dark:text-white">نشاط اليوم</h2>
                    </div>
                    <Activity className="text-medical-600" size={20} />
                </div>
                <div className="space-y-3">
                    {(waiting.length ? waiting : products.slice(0, 4)).map((item, index) => (
                        <div key={`${item.id}-${index}`} className="flex items-center gap-3 rounded-3xl border border-slate-100 bg-white/70 p-3 dark:border-slate-800 dark:bg-slate-950">
                            <span className="grid h-10 w-10 place-items-center rounded-2xl bg-medical-50 text-medical-700">{index + 1}</span>
                            <div className="min-w-0 flex-1">
                                <div className="line-clamp-1 text-sm font-black text-slate-800 dark:text-white">{item.customer || item.name}</div>
                                <div className="text-xs font-bold text-slate-500">{item.status ? `طلب ${item.status} - ${item.date}` : `منتج متاح بسعر ${formatMoney(item.price)}`}</div>
                            </div>
                            {item.url && <a href={item.url} className="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-600 hover:bg-medical-50 hover:text-medical-700 dark:bg-slate-900">فتح</a>}
                        </div>
                    ))}
                </div>
            </Card>
        </div>
    );
}

function InventoryDecisionMatrix({ routes, lowStock, products }) {
    const stockValue = products.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.quantity || 0), 0);
    const zeroStock = lowStock.filter((item) => Number(item.quantity || 0) <= 0).length;
    const decisions = [
        ['طلب شراء مقترح', lowStock.length, 'راجع الأصناف الأقل من حد الطلب', routes.inventory, 'rose'],
        ['أرصدة صفرية', zeroStock, 'أصناف تحتاج إدخال أو إخفاء مؤقت', routes.inventory, 'amber'],
        ['قيمة عينة الكتالوج', formatMoney(stockValue), 'تحليل تكلفة أحدث المنتجات', routes.reports || routes.finance, 'emerald'],
    ];

    return (
        <Card className="p-5">
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="text-sm font-black text-medical-600">Inventory Decision Matrix</div>
                    <h2 className="text-xl font-black dark:text-white">مصفوفة قرارات المخزون</h2>
                </div>
                <a href={routes.inventory}><Button>فتح مركز المخزون</Button></a>
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                {decisions.map(([label, value, desc, href, tone]) => (
                    <a key={label} href={href} className="rounded-[1.4rem] border border-slate-100 bg-white p-4 transition hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-950">
                        <div className={cn('mb-4 inline-flex rounded-full px-3 py-1 text-xs font-black', tone === 'rose' ? 'bg-rose-50 text-rose-700' : tone === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700')}>{label}</div>
                        <div className="text-3xl font-black dark:text-white">{value}</div>
                        <div className="mt-2 text-sm font-bold text-slate-500">{desc}</div>
                    </a>
                ))}
            </div>
        </Card>
    );
}

function PulseRow({ icon: Icon, label, value, tone }) {
    const toneClass = {
        emerald: 'bg-emerald-50 text-emerald-700',
        rose: 'bg-rose-50 text-rose-700',
        sky: 'bg-sky-50 text-sky-700',
    }[tone] || 'bg-slate-50 text-slate-700';
    return (
        <div className="flex items-center gap-3 rounded-3xl border border-slate-100 bg-white/70 p-3 dark:border-slate-800 dark:bg-slate-900/70">
            <span className={cn('grid h-11 w-11 place-items-center rounded-2xl', toneClass)}><Icon size={19} /></span>
            <span className="flex-1 text-sm font-black text-slate-700 dark:text-slate-200">{label}</span>
            <span className="text-xl font-black text-slate-950 dark:text-white">{value}</span>
        </div>
    );
}

function OrdersTable({ orders }) {
    const [sorting, setSorting] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const columns = useMemo(() => [
        { accessorKey: 'id', header: 'رقم الطلب', cell: ({ row }) => <a href={row.original.url} className="font-black text-medical-700">#{row.original.id}</a> },
        { accessorKey: 'customer', header: 'العميل' },
        { accessorKey: 'status', header: 'الحالة', cell: ({ getValue }) => <StatusBadge status={getValue()} /> },
        { accessorKey: 'payment', header: 'الدفع' },
        { accessorKey: 'branch', header: 'الفرع' },
        { accessorKey: 'delivery', header: 'التوصيل' },
        { accessorKey: 'total', header: 'الإجمالي', cell: ({ getValue }) => formatMoney(getValue()) },
        { accessorKey: 'date', header: 'التاريخ' },
    ], []);
    const filteredOrders = useMemo(() => {
        const term = searchTerm.trim().toLowerCase();
        return orders.filter((order) => {
            const matchesStatus = statusFilter === 'all' || order.status === statusFilter;
            const matchesSearch = !term || [order.id, order.customer, order.branch, order.delivery, order.payment].join(' ').toLowerCase().includes(term);
            return matchesStatus && matchesSearch;
        });
    }, [orders, searchTerm, statusFilter]);
    const table = useReactTable({ data: filteredOrders, columns, state: { sorting }, onSortingChange: setSorting, getCoreRowModel: getCoreRowModel(), getSortedRowModel: getSortedRowModel(), getPaginationRowModel: getPaginationRowModel() });
    return (
        <Card className="overflow-hidden">
            <div className="space-y-4 border-b border-slate-100 p-5 dark:border-slate-800">
                <div><div className="text-sm font-black text-medical-600">Orders Management</div><h2 className="text-xl font-black dark:text-white">إدارة الطلبات</h2></div>
                <div className="flex gap-2"><Button variant="secondary">Export CSV</Button><Button variant="secondary">Saved Views</Button></div>
            </div>
            <div className="grid gap-2 border-b border-slate-100 p-5 pt-0 md:grid-cols-[1fr_190px_150px] dark:border-slate-800">
                <label className="relative">
                    <Search className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" size={17} />
                    <input value={searchTerm} onChange={(event) => setSearchTerm(event.target.value)} className="h-11 w-full rounded-2xl border border-slate-200 bg-white pr-10 pl-3 text-sm font-bold outline-none transition focus:border-medical-300 focus:ring-4 focus:ring-medical-100 dark:border-slate-800 dark:bg-slate-950 dark:text-white" placeholder="بحث برقم الطلب، العميل، الفرع أو التوصيل" />
                </label>
                <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)} className="h-11 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-black outline-none focus:border-medical-300 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                    <option value="all">كل الحالات</option>
                    <option value="new">قيد الانتظار</option>
                    <option value="preparing">قيد التجهيز</option>
                    <option value="shipped">تم الشحن</option>
                    <option value="completed">مكتمل</option>
                    <option value="cancelled">ملغي</option>
                </select>
                <div className="grid place-items-center rounded-2xl bg-slate-50 px-3 text-sm font-black text-slate-600 dark:bg-slate-900 dark:text-slate-300">{filteredOrders.length} طلب</div>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-slate-50 dark:bg-slate-900">
                        {table.getHeaderGroups().map((group) => (
                            <tr key={group.id}>
                                {group.headers.map((header) => (
                                    <th key={header.id} onClick={header.column.getToggleSortingHandler()} className="whitespace-nowrap px-4 py-3 text-right font-black text-slate-500">{flexRender(header.column.columnDef.header, header.getContext())}</th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                    <tbody>
                        {table.getRowModel().rows.map((row) => (
                            <tr key={row.id} className="border-t border-slate-100 transition hover:bg-medical-50/40 dark:border-slate-800 dark:hover:bg-slate-900">
                                {row.getVisibleCells().map((cell) => <td key={cell.id} className="whitespace-nowrap px-4 py-4 font-semibold text-slate-700 dark:text-slate-200">{flexRender(cell.column.columnDef.cell, cell.getContext())}</td>)}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Card>
    );
}

function StatusBadge({ status }) {
    const map = {
        new: 'bg-sky-50 text-sky-700',
        preparing: 'bg-indigo-50 text-indigo-700',
        shipped: 'bg-violet-50 text-violet-700',
        completed: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-rose-50 text-rose-700',
    };
    const labels = { new: 'قيد الانتظار', preparing: 'قيد التجهيز', shipped: 'تم الشحن', completed: 'مكتمل', cancelled: 'ملغي' };
    return <Badge className={map[status] || 'bg-slate-100 text-slate-700'}>{labels[status] || status}</Badge>;
}

function ProductGrid({ products }) {
    return (
        <Card className="p-5">
            <div className="mb-5 flex items-center justify-between"><h2 className="text-xl font-black dark:text-white">إدارة المنتجات</h2><Button variant="secondary">Grid / List</Button></div>
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                {products.slice(0, 5).map((product) => (
                    <a key={product.id} href={product.url} className="rounded-3xl border border-slate-100 p-3 transition hover:-translate-y-1 hover:shadow-xl dark:border-slate-800">
                        <img src={product.image} alt={product.name} className="h-32 w-full rounded-2xl object-cover" loading="lazy" />
                        <div className="mt-3 line-clamp-2 min-h-10 text-sm font-black dark:text-white">{product.name}</div>
                        <div className="mt-2 text-xs font-bold text-slate-500">SKU: {product.sku || `SKU-${product.id}`}</div>
                        <div className="mt-3 flex items-center justify-between"><span className="font-black text-medical-700">{formatMoney(product.price)}</span><Badge className={product.quantity < 6 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'}>{product.quantity}</Badge></div>
                    </a>
                ))}
            </div>
        </Card>
    );
}

function SideWidgets({ lowStock }) {
    return (
        <div className="space-y-4">
            <Card className="p-5">
                <h2 className="mb-4 text-lg font-black dark:text-white">تنبيهات المخزون</h2>
                <div className="space-y-2">
                    {lowStock.length ? lowStock.map((item) => (
                        <a key={item.id} href={item.url} className="flex items-center justify-between rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-800">
                            <span className="line-clamp-1">{item.name}</span><span>{item.quantity}</span>
                        </a>
                    )) : <EmptyState text="لا توجد تنبيهات مخزون" />}
                </div>
            </Card>
            <Card className="p-5">
                <h2 className="mb-4 text-lg font-black dark:text-white">Quick Actions</h2>
                <div className="grid gap-2">
                    {['مسح باركود', 'رفع روشتة', 'تحويل مخزون', 'تقرير يومي'].map((item) => <Button key={item} variant="secondary" className="justify-start">{item}</Button>)}
                </div>
            </Card>
        </div>
    );
}

function EmptyState({ text }) {
    return <div className="rounded-3xl border border-dashed border-slate-200 p-6 text-center text-sm font-bold text-slate-500 dark:border-slate-800">{text}</div>;
}

function CommandPalette({ routes }) {
    const { commandOpen, setCommandOpen } = useAdminStore();
    const actions = [
        ['إنشاء منتج', routes.createProduct, Package],
        ['عرض الطلبات', routes.orders, ShoppingCart],
        ['إدارة المخزون', routes.inventory, Boxes],
        ['نقطة البيع', routes.pos, CreditCard],
        ['مركز التقارير', routes.reports || routes.finance, Activity],
    ];
    return (
        <AnimatePresence>
            {commandOpen && (
                <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 z-[90] bg-slate-950/55 p-4 backdrop-blur">
                    <motion.div initial={{ scale: 0.97, y: 20 }} animate={{ scale: 1, y: 0 }} exit={{ scale: 0.97, y: 20 }} className="mx-auto mt-16 max-w-2xl overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-slate-950">
                        <div className="flex items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800"><Command /><input autoFocus className="flex-1 bg-transparent text-sm font-bold outline-none dark:text-white" placeholder="اكتب أمرا أو ابحث..." /><button onClick={() => setCommandOpen(false)}><X /></button></div>
                        <div className="p-3">{actions.map(([label, href, Icon]) => <a key={label} href={href} className="flex items-center gap-3 rounded-2xl p-3 font-bold text-slate-700 hover:bg-medical-50 hover:text-medical-700 dark:text-slate-200"><Icon size={18} /> {label}</a>)}</div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

function MobileNav({ routes }) {
    return (
        <nav className="fixed inset-x-3 bottom-3 z-40 grid grid-cols-4 rounded-3xl border border-slate-200 bg-white/95 p-2 shadow-2xl backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 lg:hidden">
            {[[Home, 'الرئيسية', routes.dashboard], [ShoppingCart, 'الطلبات', routes.orders], [Package, 'المنتجات', routes.products], [Settings, 'الإعدادات', routes.settings]].map(([Icon, label, href]) => (
                <a key={label} href={href} className="grid place-items-center gap-1 rounded-2xl p-2 text-xs font-black text-slate-600 hover:bg-medical-50 hover:text-medical-700 dark:text-slate-300"><Icon size={18} />{label}</a>
            ))}
        </nav>
    );
}

function AdminDashboard({ initialPayload }) {
    const { data } = useQuery({ queryKey: ['admin-dashboard'], queryFn: async () => initialPayload, initialData: initialPayload, staleTime: 1000 * 60 * 5 });
    const dark = useAdminStore((state) => state.dark);
    useEffect(() => document.documentElement.classList.toggle('dark', dark), [dark]);
    return (
        <div dir="rtl" className="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
            <div className="flex">
                <Sidebar routes={data.routes} />
                <main className="min-w-0 flex-1">
                    <Topbar routes={data.routes} />
                    <div className="space-y-5 p-4 pb-24 lg:p-6">
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div><div className="text-sm font-black text-medical-600">Pharmacy Ecommerce ERP</div><h1 className="text-3xl font-black tracking-tight dark:text-white">لوحة التحكم التشغيلية</h1><p className="mt-1 text-sm font-semibold text-slate-500">مركز قيادة ذكي للمبيعات، المخزون، الروشتات، الفروع، والمالية.</p></div>
                            <div className="flex gap-2"><a href={data.routes.storefront}><Button variant="secondary">عرض المتجر</Button></a><a href={data.routes.createProduct}><Button><Plus size={18} /> منتج جديد</Button></a></div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{data.kpis.map((item, index) => <KpiCard key={item.key} item={item} index={index} />)}</div>
                        <SmartOpsPanel routes={data.routes} lowStock={data.lowStock} orders={data.orders} />
                        <ExecutiveCommandCenter routes={data.routes} orders={data.orders} lowStock={data.lowStock} products={data.products} />
                        <PharmacyWorkflowBoard routes={data.routes} orders={data.orders} lowStock={data.lowStock} />
                        <Analytics charts={data.charts} />
                        <FinanceInventorySnapshot routes={data.routes} kpis={data.kpis} />
                        <InventoryDecisionMatrix routes={data.routes} lowStock={data.lowStock} products={data.products} />
                        <PrescriptionAndCareCenter routes={data.routes} orders={data.orders} products={data.products} />
                        <div className="grid gap-4 xl:grid-cols-[1fr_360px]"><div className="space-y-4"><OrdersTable orders={data.orders} /><ProductGrid products={data.products} /></div><SideWidgets lowStock={data.lowStock} /></div>
                    </div>
                </main>
            </div>
            <CommandPalette routes={data.routes} />
            <MobileNav routes={data.routes} />
            <button className="fixed bottom-24 left-5 z-40 grid h-14 w-14 place-items-center rounded-full bg-medical-600 text-white shadow-2xl shadow-medical-600/30 lg:bottom-6"><Plus /></button>
        </div>
    );
}

export function mountAdminDashboard(element, payload) {
    createRoot(element).render(
        <QueryClientProvider client={queryClient}>
            <AdminDashboard initialPayload={payload} />
        </QueryClientProvider>
    );
}
