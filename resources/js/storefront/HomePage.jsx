import React, { memo, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { motion, AnimatePresence } from 'framer-motion';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Pagination } from 'swiper/modules';
import {
    Bell,
    ChevronDown,
    Clock3,
    CreditCard,
    Heart,
    Home,
    Languages,
    Menu,
    Mic,
    PackageCheck,
    PhoneCall,
    Pill,
    QrCode,
    Search,
    ShieldCheck,
    ShoppingCart,
    Sparkles,
    Star,
    Truck,
    User,
    X,
    Zap,
} from 'lucide-react';
import 'swiper/css';
import 'swiper/css/pagination';
import { Button } from '../components/ui/button';
import { Badge, Card } from '../components/ui/card';
import { cn } from '../lib/utils';
import { useStorefront } from './store';

const queryClient = new QueryClient();

const fadeUp = {
    initial: { opacity: 0, y: 22 },
    whileInView: { opacity: 1, y: 0 },
    viewport: { once: true, margin: '-80px' },
    transition: { duration: 0.55, ease: 'easeOut' },
};

const navItems = [
    ['الأدوية', Pill, ['مسكنات', 'برد وإنفلونزا', 'حساسية', 'مضادات حيوية'], null],
    ['الفيتامينات', Sparkles, ['فيتامين D', 'أوميغا 3', 'حديد', 'مالتي فيتامين'], null],
    ['المكملات', Zap, ['بروتين', 'طاقة', 'مناعة', 'صحة العظام'], null],
    ['العناية بالطفل', ShieldCheck, ['حفاضات', 'رضاعات', 'شامبو أطفال', 'كريم تسلخات'], null],
    ['العناية بالبشرة', Star, ['مرطبات', 'واقي شمس', 'غسول', 'سيروم'], null],
    ['العناية بالشعر', Sparkles, ['تساقط الشعر', 'شامبو طبي', 'زيوت', 'قشرة'], null],
    ['أجهزة طبية', PackageCheck, ['قياس ضغط', 'قياس سكر', 'ترمومتر', 'نيبولايزر'], null],
    ['السكري', ShieldCheck, ['شرائط قياس', 'أجهزة سكر', 'عناية القدم', 'محليات'], null],
    ['العروض', Zap, ['خصومات اليوم', 'الأكثر توفيرا', 'باقات شهرية', 'وصل حديثا'], null],
    ['كل المنتجات', PackageCheck, ['كل منتجات الصيدلية', 'الأحدث', 'المتوفر في المخزون', 'تصفح كامل'], 'products'],
];

const concerns = [
    ['المناعة', 'دعم يومي لصحة أقوى', 'bg-emerald-50 text-emerald-700'],
    ['السكري', 'قياس ومتابعة ومنتجات أساسية', 'bg-sky-50 text-sky-700'],
    ['ضغط الدم', 'أجهزة ومنتجات متابعة منزلية', 'bg-rose-50 text-rose-700'],
    ['النوم', 'روتين هادئ ومكملات مساعدة', 'bg-indigo-50 text-indigo-700'],
    ['العناية بالبشرة', 'حلول طبية لبشرة صحية', 'bg-orange-50 text-orange-700'],
    ['الوزن الصحي', 'مكملات ومتابعة نمط حياة', 'bg-lime-50 text-lime-700'],
];

const brands = ['Bioderma', 'La Roche-Posay', 'Vichy', 'Centrum', 'Mustela', 'Accu-Chek', 'Sebamed', 'Now'];

function formatPrice(value) {
    return new Intl.NumberFormat('ar-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
}

function useCountdown() {
    const [time, setTime] = useState({ h: 7, m: 42, s: 15 });

    useEffect(() => {
        const timer = setInterval(() => {
            setTime((current) => {
                const total = Math.max(0, current.h * 3600 + current.m * 60 + current.s - 1);
                return {
                    h: Math.floor(total / 3600),
                    m: Math.floor((total % 3600) / 60),
                    s: total % 60,
                };
            });
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    return time;
}

function TopBar({ routes }) {
    return (
        <div className="border-b border-white/50 bg-medical-800 text-white">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 overflow-x-auto px-4 py-2 text-[11px] font-semibold md:px-5 md:text-xs">
                <div className="flex shrink-0 items-center gap-4 md:gap-5">
                    <span className="inline-flex items-center gap-2"><Truck size={15} /> شحن مجاني للطلبات فوق 500 ج.م</span>
                    <span className="inline-flex items-center gap-2"><PhoneCall size={15} /> الدعم: 0509095816</span>
                    <span className="inline-flex items-center gap-2"><Clock3 size={15} /> توصيل خلال 24-48 ساعة</span>
                </div>
                <div className="flex shrink-0 items-center gap-3 md:gap-4">
                    <a className="transition hover:text-mint-100" href={routes.login}>حسابي</a>
                    <a className="inline-flex items-center gap-1 transition hover:text-mint-100" href={routes.locale}>
                        <Languages size={14} /> English
                    </a>
                </div>
            </div>
        </div>
    );
}

function Header({ payload }) {
    const { cartCount, dark, toggleDark } = useStorefront();
    const [query, setQuery] = useState('');
    const [scrolled, setScrolled] = useState(false);
    const suggestions = useMemo(() => {
        const term = query.trim();
        return term
            ? payload.products.filter((product) => product.name.includes(term)).slice(0, 5)
            : payload.products.slice(0, 5);
    }, [payload.products, query]);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 18);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header className={cn('sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-xl transition-all dark:border-slate-800 dark:bg-slate-950/95', scrolled && 'shadow-lg shadow-slate-900/5')}>
            <div className={cn('mx-auto grid max-w-7xl grid-cols-12 items-center gap-2 px-3 transition-all sm:gap-3 md:px-5', scrolled ? 'py-2' : 'py-3 md:py-4')}>
                <a href={payload.routes.home} className="col-span-8 flex min-w-0 items-center gap-2 md:col-span-3 md:gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-medical-600 text-white shadow-lg shadow-medical-600/25 md:h-12 md:w-12">
                        <Pill size={26} />
                    </div>
                    <div className="min-w-0">
                        <div className="truncate text-sm font-black text-slate-950 dark:text-white sm:text-base md:text-lg">صيدلية د. محمد رمضان</div>
                        <div className="hidden text-xs font-bold text-medical-600 sm:block">رعاية موثوقة وتسوق أسرع</div>
                    </div>
                </a>

                <div className="relative order-3 col-span-12 md:order-none md:col-span-6">
                    <form action={payload.routes.search} className="relative">
                        <Search className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400" size={20} />
                        <input
                            name="q"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            className="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-11 pl-24 text-sm font-semibold outline-none transition focus:border-medical-300 focus:bg-white focus:ring-4 focus:ring-medical-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-white md:h-14 md:rounded-3xl md:pr-12 md:pl-28"
                            placeholder="ابحث عن دواء، فيتامين، باركود أو منتج صحي"
                        />
                        <div className="absolute left-2 top-1/2 flex -translate-y-1/2 gap-1">
                            <button type="button" className="rounded-2xl p-2 text-slate-500 transition hover:bg-white hover:text-medical-700"><Mic size={18} /></button>
                            <button type="button" className="rounded-2xl p-2 text-slate-500 transition hover:bg-white hover:text-medical-700"><QrCode size={18} /></button>
                        </div>
                    </form>
                    <div className={cn('absolute right-0 top-[calc(100%+10px)] hidden w-full rounded-3xl border border-slate-200 bg-white p-3 shadow-2xl dark:border-slate-800 dark:bg-slate-950', (query || document.activeElement?.name === 'q') && 'md:block')}>
                        <div className="mb-2 flex items-center justify-between px-2 text-xs font-black text-slate-500">
                            <span>{query ? 'اقتراحات البحث' : 'الأكثر بحثا'}</span>
                            <span>منتجات رائجة</span>
                        </div>
                        {suggestions.map((item) => (
                            <a key={item.id} href={item.url} className="flex items-center gap-3 rounded-2xl p-2 transition hover:bg-slate-50 dark:hover:bg-slate-900">
                                <img src={item.image} alt={item.name} className="h-10 w-10 rounded-xl object-cover" loading="lazy" />
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-bold text-slate-900 dark:text-white">{item.name}</div>
                                    <div className="text-xs text-slate-500">{formatPrice(item.price)} ج.م</div>
                                </div>
                                <Badge className="bg-medical-50 text-medical-700">متوفر</Badge>
                            </a>
                        ))}
                    </div>
                </div>

                <div className="col-span-4 flex items-center justify-end gap-1 md:col-span-3">
                    <IconAction label="الإشعارات"><Bell size={20} /></IconAction>
                    <IconAction label="المفضلة"><Heart size={20} /></IconAction>
                    <a href={payload.routes.cart} className="relative rounded-2xl p-2.5 text-slate-700 transition hover:bg-medical-50 hover:text-medical-700 dark:text-slate-200 md:p-3">
                        <ShoppingCart size={21} />
                        <span className="absolute -top-1 -left-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-black text-white">{cartCount}</span>
                    </a>
                    <a href={payload.routes.login} className="rounded-2xl p-2.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 md:p-3"><User size={21} /></a>
                    <button onClick={toggleDark} className="hidden rounded-2xl px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-100 dark:text-white sm:block">{dark ? 'نهاري' : 'داكن'}</button>
                </div>
            </div>
        </header>
    );
}

function IconAction({ children, label }) {
    return (
        <button type="button" aria-label={label} className="hidden rounded-2xl p-3 text-slate-700 transition hover:bg-medical-50 hover:text-medical-700 dark:text-slate-200 md:block">
            {children}
        </button>
    );
}

function MegaNav({ products, routes }) {
    const [active, setActive] = useState(null);
    return (
        <>
        <nav className="relative z-40 border-b border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-950 lg:hidden">
            <div className="flex gap-2 overflow-x-auto px-3 py-2">
                {navItems.map(([label, Icon, , routeKey]) => (
                    <a key={label} href={routeKey ? routes[routeKey] : '#'} className="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-medical-50 hover:text-medical-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <Icon size={15} /> {label}
                    </a>
                ))}
            </div>
        </nav>
        <nav className="relative z-40 hidden border-y border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-950 lg:block">
            <div className="mx-auto flex max-w-7xl items-center justify-end gap-1 px-5">
                {navItems.map(([label, Icon, subs, routeKey], index) => (
                    <div key={label} onMouseEnter={() => setActive(index)} onMouseLeave={() => setActive(null)} className="py-2">
                        <a href={routeKey ? routes[routeKey] : '#'} className="flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-black text-slate-700 transition hover:bg-medical-50 hover:text-medical-700 dark:text-slate-200">
                            <Icon size={17} /> {label} <ChevronDown size={14} />
                        </a>
                        {active === index && (
                            <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="absolute inset-x-0 top-full w-full rounded-b-[2rem] border-y border-slate-100 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-950">
                                <div className="mx-auto grid max-w-7xl grid-cols-4 gap-5 px-5 py-5">
                                    <div className="col-span-2 grid grid-cols-2 gap-2">
                                        {subs.map((sub) => (
                                            <a key={sub} href="#" className="rounded-2xl bg-slate-50 p-3 text-sm font-bold text-slate-700 transition hover:bg-medical-50 hover:text-medical-700 dark:bg-slate-900 dark:text-slate-200">{sub}</a>
                                        ))}
                                    </div>
                                    <div>
                                        <div className="mb-2 text-sm font-black text-slate-900 dark:text-white">منتجات مميزة</div>
                                        {products.slice(0, 3).map((product) => (
                                            <a key={product.id} href={product.url} className="flex items-center gap-2 rounded-xl p-2 hover:bg-slate-50 dark:hover:bg-slate-900">
                                                <img src={product.image} alt={product.name} className="h-10 w-10 rounded-xl object-cover" loading="lazy" />
                                                <span className="line-clamp-1 text-xs font-bold text-slate-700 dark:text-slate-200">{product.name}</span>
                                            </a>
                                        ))}
                                    </div>
                                    <div className="rounded-3xl bg-gradient-to-br from-medical-700 to-teal-500 p-5 text-white">
                                        <Badge className="mb-4 bg-white/20 text-white">عرض خاص</Badge>
                                        <div className="text-2xl font-black">خصومات صحية يومية</div>
                                        <p className="mt-2 text-sm text-white/85">تسوق أفضل منتجات الرعاية بثقة.</p>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </div>
                ))}
            </div>
        </nav>
        </>
    );
}

function Hero({ banners }) {
    const slides = banners.length ? banners : [];
    return (
        <section className="mx-auto max-w-7xl px-4 pt-5 md:px-5">
            <Swiper modules={[Autoplay, Pagination]} autoplay={{ delay: 4300 }} pagination={{ clickable: true }} loop className="overflow-hidden rounded-[2rem]">
                {slides.map((banner, index) => (
                    <SwiperSlide key={banner.id}>
                        <div className={cn('relative min-h-[420px] overflow-hidden bg-gradient-to-br p-5 sm:p-6 md:p-10', index % 2 ? 'from-sky-700 via-teal-600 to-medical-500' : 'from-medical-800 via-medical-600 to-emerald-400')}>
                            <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,rgba(255,255,255,.25),transparent_28%),radial-gradient(circle_at_85%_20%,rgba(255,255,255,.16),transparent_30%)]" />
                            <div className="relative grid min-h-[380px] grid-cols-2 items-center gap-4 md:gap-8">
                                <motion.div initial={{ opacity: 0, x: 30 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: 0.7 }} className="text-white">
                                    <Badge className="mb-3 bg-white/18 text-[10px] text-white backdrop-blur sm:mb-5 sm:text-xs">منتجات أصلية 100%</Badge>
                                    <h1 className="max-w-xl text-[2rem] font-black leading-tight sm:text-4xl md:text-6xl">{banner.title}</h1>
                                    <p className="mt-3 max-w-lg text-sm font-semibold leading-6 text-white/88 sm:text-lg sm:leading-8">{banner.subtitle}</p>
                                    <div className="mt-5 flex flex-wrap gap-2 sm:mt-7 sm:gap-3">
                                        <a href={banner.url}><Button className="h-10 bg-white px-4 text-xs text-medical-800 hover:bg-medical-50 sm:h-11 sm:px-5 sm:text-sm">تسوق الآن</Button></a>
                                        <Button variant="secondary" className="h-10 border-white/30 bg-white/15 px-4 text-xs text-white backdrop-blur hover:bg-white/20 sm:h-11 sm:px-5 sm:text-sm">اكتشف العروض</Button>
                                    </div>
                                </motion.div>
                                <motion.div initial={{ opacity: 0, scale: 0.92 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.7 }} className="relative">
                                    <div className="absolute -inset-5 rounded-full bg-white/20 blur-3xl" />
                                    <img src={banner.image} alt={banner.title} className="relative mx-auto max-h-[210px] w-full rounded-[1.6rem] object-cover shadow-2xl sm:max-h-[280px] sm:rounded-[2rem] md:max-h-[330px]" loading={index === 0 ? 'eager' : 'lazy'} />
                                    <div className="absolute -bottom-4 right-3 rounded-2xl bg-white/90 p-3 shadow-xl backdrop-blur dark:bg-slate-900/90 sm:-bottom-5 sm:right-8 sm:rounded-3xl sm:p-4">
                                        <div className="text-[10px] font-bold text-slate-500 sm:text-xs">خصم حتى</div>
                                        <div className="text-2xl font-black text-rose-600 sm:text-3xl">40%</div>
                                    </div>
                                </motion.div>
                            </div>
                        </div>
                    </SwiperSlide>
                ))}
            </Swiper>
            <div className="-mt-7 flex gap-3 overflow-x-auto px-4 pb-2 md:grid md:grid-cols-4 md:overflow-visible md:pb-0">
                {[
                    [ShieldCheck, 'منتجات أصلية', 'مصادر موثوقة'],
                    [Truck, 'توصيل سريع', 'خلال 24-48 ساعة'],
                    [CreditCard, 'دفع آمن', 'تشفير كامل'],
                    [PhoneCall, 'دعم 24/7', 'متابعة مستمرة'],
                ].map(([Icon, title, desc]) => (
                    <Card key={title} className="relative z-10 flex min-w-[260px] items-center gap-3 p-4 shadow-xl shadow-slate-900/5 md:min-w-0">
                        <div className="rounded-2xl bg-medical-50 p-3 text-medical-700"><Icon size={22} /></div>
                        <div><div className="font-black text-slate-900 dark:text-white">{title}</div><div className="text-xs font-semibold text-slate-500">{desc}</div></div>
                    </Card>
                ))}
            </div>
        </section>
    );
}

function SectionHeader({ eyebrow, title, action, urgent }) {
    return (
        <div className="mb-5 flex items-end justify-between gap-3">
            <div>
                <div className={cn('mb-1 text-xs font-black uppercase tracking-wide', urgent ? 'text-rose-600' : 'text-medical-600')}>{eyebrow}</div>
                <h2 className="text-2xl font-black text-slate-950 dark:text-white md:text-3xl">{title}</h2>
            </div>
            {action && <a href={action.href} className="text-sm font-black text-medical-700 hover:text-medical-900">{action.label}</a>}
        </div>
    );
}

function Categories({ categories, title = 'أقسام الصيدلية', eyebrow = 'تسوق أسرع' }) {
    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl px-4 py-12 md:px-5">
            <SectionHeader eyebrow={eyebrow} title={title} action={{ href: '#', label: 'كل الأقسام' }} />
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                {categories.slice(0, 18).map((category, index) => (
                    <a
                        key={category.id}
                        href={category.url}
                        className="group relative overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-950/10 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div className="absolute inset-x-4 top-0 h-16 rounded-b-full bg-gradient-to-b from-emerald-100/70 to-transparent blur-xl transition group-hover:from-emerald-200/80" />
                        <div className="relative overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-slate-50 to-emerald-50 p-2 dark:from-slate-800 dark:to-slate-900">
                            <img
                                src={category.image}
                                alt={category.name}
                                className="h-28 w-full rounded-[1.1rem] object-contain transition duration-500 group-hover:scale-110"
                                loading={index < 6 ? 'eager' : 'lazy'}
                            />
                        </div>
                        <div className="relative mt-3">
                            <div className="line-clamp-2 min-h-12 text-center text-sm font-black leading-6 text-slate-900 transition group-hover:text-emerald-700 dark:text-white">{category.name}</div>
                            <div className="mx-auto mt-2 w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-slate-800">
                                {Number(category.count || 0).toLocaleString('ar-EG')} منتج
                            </div>
                        </div>
                    </a>
                ))}
            </div>
        </motion.section>
    );
}

const ProductCard = memo(function ProductCard({ product, routes }) {
    const openPreview = useStorefront((state) => state.openPreview);
    const discount = Number(product.discount || 0);
    return (
        <Card className="group relative h-full overflow-hidden p-3 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-900/10">
            <div className="absolute left-3 top-3 z-10 flex flex-col gap-2 opacity-0 transition group-hover:opacity-100">
                <button className="rounded-full bg-white p-2 text-slate-700 shadow hover:text-rose-600" aria-label="المفضلة"><Heart size={17} /></button>
                <button onClick={() => openPreview(product)} className="rounded-full bg-white p-2 text-slate-700 shadow hover:text-medical-700" aria-label="معاينة"><Search size={17} /></button>
            </div>
            <a href={product.url} className="relative block overflow-hidden rounded-3xl bg-slate-50 dark:bg-slate-900">
                {discount > 0 && <Badge className="absolute right-3 top-3 z-10 bg-rose-600 text-white">خصم {discount}%</Badge>}
                <img src={product.image} alt={product.name} className="h-52 w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy" />
            </a>
            <div className="pt-3">
                <div className="mb-2 flex items-center justify-between">
                    <Badge className={product.available_qty > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}>
                        {product.available_qty > 0 ? 'متوفر بالصيدلية' : 'غير متوفر'}
                    </Badge>
                    <div className="flex items-center gap-1 text-amber-500"><Star size={15} fill="currentColor" /><span className="text-xs font-black text-slate-600 dark:text-slate-300">{product.rating}</span></div>
                </div>
                <a href={product.url} className="line-clamp-2 min-h-11 text-sm font-black leading-6 text-slate-900 hover:text-medical-700 dark:text-white">{product.name}</a>
                <div className="mt-2 text-xs font-semibold text-slate-500">{product.reviews} تقييم · تقسيط متاح</div>
                <div className="mt-3 flex items-end gap-2">
                    <span className="text-lg font-black text-medical-700">{formatPrice(product.price)} ج.م</span>
                    {Number(product.compare_price) > Number(product.price) && <span className="text-xs font-bold text-slate-400 line-through">{formatPrice(product.compare_price)}</span>}
                </div>
                <form action={routes.addToCart} method="POST" className="mt-3">
                    <input type="hidden" name="_token" value={routes.csrf} />
                    <input type="hidden" name="product_id" value={product.id} />
                    <input type="hidden" name="qty" value="1" />
                    <Button className="w-full" disabled={product.available_qty < 1}><ShoppingCart size={17} /> إضافة سريعة</Button>
                </form>
            </div>
        </Card>
    );
});

function ProductSlider({ title, eyebrow, products, routes, urgent = false, countdown = false }) {
    const time = useCountdown();
    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl px-4 py-9 md:px-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader eyebrow={eyebrow} title={title} urgent={urgent} action={{ href: '#', label: 'عرض الكل' }} />
                {countdown && (
                    <div className="mb-5 flex items-center gap-2 rounded-2xl bg-rose-50 px-4 py-2 text-rose-700">
                        <Zap size={18} />
                        <span className="font-black">ينتهي خلال</span>
                        <span className="font-mono text-lg font-black">{String(time.h).padStart(2, '0')}:{String(time.m).padStart(2, '0')}:{String(time.s).padStart(2, '0')}</span>
                    </div>
                )}
            </div>
            <Swiper modules={[Pagination]} pagination={{ clickable: true }} spaceBetween={16} slidesPerView={1.35} breakpoints={{ 640: { slidesPerView: 2.2 }, 900: { slidesPerView: 3.2 }, 1200: { slidesPerView: 5 } }} className="!pb-10">
                {products.map((product) => (
                    <SwiperSlide key={product.id} className="h-auto">
                        <ProductCard product={product} routes={routes} />
                    </SwiperSlide>
                ))}
            </Swiper>
        </motion.section>
    );
}

function Brands({ title = 'أشهر الماركات الطبية', eyebrow = 'شركاء موثوقون', brandLogos = [] }) {
    const visibleBrands = brandLogos.length ? brandLogos : brands.map((name) => ({ name, image: null, url: '' }));
    const marqueeBrands = [...visibleBrands, ...visibleBrands];

    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl overflow-hidden px-4 py-9 md:px-5">
            <SectionHeader eyebrow={eyebrow} title={title} />
            <div className="relative">
                <div className="pointer-events-none absolute inset-y-0 right-0 z-10 w-20 bg-gradient-to-l from-slate-50 to-transparent dark:from-slate-950" />
                <div className="pointer-events-none absolute inset-y-0 left-0 z-10 w-20 bg-gradient-to-r from-slate-50 to-transparent dark:from-slate-950" />
                <div className="brand-marquee flex w-max gap-3 py-1">
                    {marqueeBrands.map((brand, index) => (
                        <a
                            key={`${brand.name}-${index}`}
                            href={brand.url || '#'}
                            className="group grid h-28 w-48 shrink-0 place-items-center rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm grayscale transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/10 hover:grayscale-0 dark:border-slate-800 dark:bg-slate-900 md:w-56"
                        >
                            {brand.image ? (
                                <img src={brand.image} alt={brand.name} className="max-h-14 max-w-full object-contain transition duration-300 group-hover:scale-105" loading="lazy" />
                            ) : (
                                <span className="text-center text-lg font-black text-slate-500">{brand.name}</span>
                            )}
                        </a>
                    ))}
                </div>
            </div>
        </motion.section>
    );
}

function Concerns({ title = 'حلول صحية موجهة', eyebrow = 'تسوق حسب احتياجك' }) {
    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl px-4 py-9 md:px-5">
            <SectionHeader eyebrow={eyebrow} title={title} />
            <div className="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:overflow-visible md:px-0 md:pb-0">
                {concerns.map(([title, desc, tone]) => (
                    <Card key={title} className="group min-w-[82vw] snap-start overflow-hidden p-5 transition hover:-translate-y-1 hover:shadow-xl sm:min-w-[48vw] md:min-w-0">
                        <div className={cn('mb-5 flex h-16 w-16 items-center justify-center rounded-3xl', tone)}>
                            <Pill size={30} />
                        </div>
                        <h3 className="text-xl font-black text-slate-950 dark:text-white">{title}</h3>
                        <p className="mt-2 text-sm font-semibold leading-7 text-slate-500">{desc}</p>
                    </Card>
                ))}
            </div>
        </motion.section>
    );
}

function Testimonials() {
    const reviews = [
        ['أحمد', 'تجربة شراء ممتازة والتوصيل كان سريع جدا.', 'شراء موثق'],
        ['منى', 'المنتجات وصلت مغلفة ونظيفة والأسعار واضحة.', 'عميلة موثقة'],
        ['كريم', 'واجهة سهلة والعروض واضحة، طلبت في دقايق.', 'شراء موثق'],
    ];
    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl px-4 py-9 md:px-5">
            <SectionHeader eyebrow="آراء العملاء" title="ثقة يومية من عملائنا" />
            <div className="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:overflow-visible md:px-0 md:pb-0">
                {reviews.map(([name, text, badge]) => (
                    <Card key={name} className="min-w-[82vw] snap-start p-5 transition hover:-translate-y-1 hover:shadow-xl sm:min-w-[48vw] md:min-w-0">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="grid h-12 w-12 place-items-center rounded-full bg-medical-100 text-lg font-black text-medical-800">{name[0]}</div>
                            <div>
                                <div className="font-black text-slate-900 dark:text-white">{name}</div>
                                <Badge className="bg-sky-50 text-sky-700">{badge}</Badge>
                            </div>
                        </div>
                        <div className="mb-3 flex text-amber-400">{Array.from({ length: 5 }).map((_, i) => <Star key={i} size={16} fill="currentColor" />)}</div>
                        <p className="text-sm font-semibold leading-7 text-slate-600 dark:text-slate-300">{text}</p>
                    </Card>
                ))}
            </div>
        </motion.section>
    );
}

function AppBanner() {
    return (
        <section className="mx-auto max-w-7xl px-4 py-9 md:px-5">
            <div className="grid overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-medical-900 to-teal-700 p-6 text-white md:grid-cols-2 md:p-10">
                <div>
                    <Badge className="mb-5 bg-white/15 text-white">تطبيق الصيدلية</Badge>
                    <h2 className="text-3xl font-black md:text-5xl">اطلب أدويتك من الموبايل أسرع</h2>
                    <p className="mt-4 max-w-lg text-sm font-semibold leading-7 text-white/80">تنبيهات للطلبات، عروض خاصة، وسهولة متابعة السلة والدفع.</p>
                    <div className="mt-7 flex flex-wrap gap-3">
                        <Button className="bg-white text-slate-950 hover:bg-slate-100">App Store</Button>
                        <Button className="bg-white text-slate-950 hover:bg-slate-100">Google Play</Button>
                        <div className="grid h-12 w-12 place-items-center rounded-2xl bg-white text-slate-950"><QrCode /></div>
                    </div>
                </div>
                <div className="mt-8 flex justify-center md:mt-0">
                    <div className="h-[320px] w-[190px] rounded-[2.5rem] border-8 border-slate-900 bg-white p-3 shadow-2xl">
                        <div className="h-full rounded-[1.8rem] bg-gradient-to-b from-medical-50 to-white p-3">
                            <div className="mb-5 h-8 rounded-2xl bg-medical-600" />
                            <div className="space-y-3">{[1, 2, 3, 4].map((i) => <div key={i} className="h-12 rounded-2xl bg-slate-100" />)}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function Newsletter() {
    return (
        <section className="mx-auto max-w-7xl px-4 py-9 md:px-5">
            <Card className="overflow-hidden bg-gradient-to-r from-medical-600 to-teal-500 p-6 text-white md:p-8">
                <div className="grid gap-5 md:grid-cols-[1fr_460px] md:items-center">
                    <div>
                        <h2 className="text-3xl font-black">اشترك للحصول على عروض صحية خاصة</h2>
                        <p className="mt-2 text-sm font-semibold text-white/80">خصومات، منتجات جديدة، ونصائح تسوق تصل لبريدك.</p>
                    </div>
                    <form className="flex gap-2 rounded-3xl bg-white p-2" onSubmit={(e) => e.preventDefault()}>
                        <input className="min-w-0 flex-1 rounded-2xl px-4 text-sm font-bold text-slate-900 outline-none" placeholder="البريد الإلكتروني" />
                        <Button>اشتراك</Button>
                    </form>
                </div>
            </Card>
        </section>
    );
}

function AllProductsCta({ routes }) {
    return (
        <motion.section {...fadeUp} className="mx-auto max-w-7xl px-4 md:px-5">
            <a href={routes.products} className="group flex flex-col gap-4 overflow-hidden rounded-[2rem] bg-gradient-to-br from-medical-800 via-emerald-600 to-teal-500 p-5 text-white shadow-2xl shadow-emerald-950/10 md:flex-row md:items-center md:justify-between md:p-7">
                <div>
                    <div className="text-xs font-black uppercase text-white/70">كل الكتالوج</div>
                    <h2 className="mt-2 text-3xl font-black">كل منتجات الصيدلية</h2>
                    <p className="mt-2 text-sm font-semibold text-white/80">تصفح كل المنتجات المتاحة مع الفلترة بالسعر والتوفر والترتيب.</p>
                </div>
                <span className="inline-flex w-fit items-center rounded-2xl bg-white px-5 py-3 text-sm font-black text-medical-800 transition group-hover:-translate-x-1">
                    عرض كل المنتجات
                </span>
            </a>
        </motion.section>
    );
}

function Footer({ routes }) {
    return (
        <footer className="mt-10 bg-slate-950 text-white">
            <div className="mx-auto grid max-w-7xl gap-8 px-5 py-12 md:grid-cols-4">
                <div>
                    <div className="mb-3 flex items-center gap-2 text-xl font-black"><Pill /> صيدلية د. محمد رمضان</div>
                    <p className="text-sm leading-7 text-slate-400">صيدلية إلكترونية بتجربة تسوق حديثة ومنتجات صحية موثوقة.</p>
                </div>
                {['الدعم', 'الأقسام', 'السياسات'].map((title) => (
                    <div key={title}>
                        <h3 className="mb-3 font-black">{title}</h3>
                        <div className="space-y-2 text-sm text-slate-400">
                            <a className="block hover:text-white" href={routes.home}>الرئيسية</a>
                            <a className="block hover:text-white" href={routes.cart}>السلة</a>
                            <a className="block hover:text-white" href={routes.login}>حسابي</a>
                        </div>
                    </div>
                ))}
            </div>
            <div className="border-t border-white/10 py-4 text-center text-xs font-semibold text-slate-500">© {new Date().getFullYear()} صيدلية د. محمد رمضان - دفع آمن وشحن موثوق</div>
        </footer>
    );
}

function PreviewModal({ routes }) {
    const { previewProduct, closePreview } = useStorefront();
    return (
        <AnimatePresence>
            {previewProduct && (
                <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 z-[80] grid place-items-center bg-slate-950/60 p-4 backdrop-blur">
                    <motion.div initial={{ scale: 0.95, y: 20 }} animate={{ scale: 1, y: 0 }} exit={{ scale: 0.95, y: 20 }} className="w-full max-w-3xl overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-slate-950">
                        <div className="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
                            <h3 className="text-lg font-black dark:text-white">معاينة سريعة</h3>
                            <button onClick={closePreview} className="rounded-full p-2 hover:bg-slate-100 dark:hover:bg-slate-800"><X /></button>
                        </div>
                        <div className="grid gap-5 p-5 md:grid-cols-2">
                            <img src={previewProduct.image} alt={previewProduct.name} className="h-72 w-full rounded-3xl object-cover" />
                            <div>
                                <Badge className="mb-3 bg-medical-50 text-medical-700">متوفر بالصيدلية</Badge>
                                <h4 className="text-2xl font-black text-slate-950 dark:text-white">{previewProduct.name}</h4>
                                <div className="mt-3 flex text-amber-400">{Array.from({ length: 5 }).map((_, i) => <Star key={i} size={17} fill="currentColor" />)}</div>
                                <div className="mt-5 text-3xl font-black text-medical-700">{formatPrice(previewProduct.price)} ج.م</div>
                                <p className="mt-4 text-sm font-semibold leading-7 text-slate-500">منتج صحي موثوق متاح للطلب السريع من الصيدلية.</p>
                                <form action={routes.addToCart} method="POST" className="mt-6">
                                    <input type="hidden" name="_token" value={routes.csrf} />
                                    <input type="hidden" name="product_id" value={previewProduct.id} />
                                    <input type="hidden" name="qty" value="1" />
                                    <Button className="w-full"><ShoppingCart size={18} /> أضف للسلة</Button>
                                </form>
                            </div>
                        </div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

function MobileBottomNav({ routes }) {
    return (
        <nav className="fixed inset-x-3 bottom-3 z-50 grid grid-cols-4 rounded-3xl border border-slate-200 bg-white/95 p-2 shadow-2xl backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:hidden">
            {[[Home, 'الرئيسية', routes.home], [Search, 'بحث', routes.search], [Heart, 'المفضلة', '#'], [ShoppingCart, 'السلة', routes.cart]].map(([Icon, label, href]) => (
                <a key={label} href={href} className="grid place-items-center gap-1 rounded-2xl p-2 text-xs font-black text-slate-600 hover:bg-medical-50 hover:text-medical-700 dark:text-slate-300">
                    <Icon size={19} /> {label}
                </a>
            ))}
        </nav>
    );
}

function HomePage({ initialPayload }) {
    const setCartCount = useStorefront((state) => state.setCartCount);
    const dark = useStorefront((state) => state.dark);
    const { data: payload } = useQuery({
        queryKey: ['storefront-home'],
        queryFn: async () => initialPayload,
        initialData: initialPayload,
        staleTime: 1000 * 60 * 5,
    });

    useEffect(() => setCartCount(payload.cartCount), [payload.cartCount, setCartCount]);
    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
    }, [dark]);

    const featured = payload.products.filter((product) => product.featured).slice(0, 12);
    const flash = payload.products.filter((product) => product.discount > 0).slice(0, 12);
    const best = payload.products.slice(8, 20);
    const recent = [...payload.products].reverse().slice(0, 12);
    const sectionProducts = payload.sectionProducts || {};
    const sectionList = (payload.sections?.length ? payload.sections : [
        { key: 'slider_banners', title: 'بنرات رئيسية' },
        { key: 'categories_circles', title: 'أقسام الصيدلية' },
        { key: 'featured_products', title: 'منتجات مميزة' },
        { key: 'flash_deals', title: 'عروض اليوم' },
        { key: 'all_products_cta', title: 'كل المنتجات' },
        { key: 'brands', title: 'أشهر الماركات الطبية' },
        { key: 'collections', title: 'حلول صحية موجهة' },
        { key: 'best_sellers', title: 'الأكثر مبيعا' },
        { key: 'new_arrivals', title: 'وصل حديثا' },
        { key: 'testimonials', title: 'آراء العملاء' },
        { key: 'app_banner', title: 'تطبيق الصيدلية' },
        { key: 'newsletter', title: 'النشرة البريدية' },
    ]).filter((section) => section.active !== false);

    const renderSection = (section) => {
        const productsFor = (key, fallback) => (sectionProducts[key]?.length ? sectionProducts[key] : fallback);

        switch (section.key) {
            case 'slider_banners':
                return <Hero key={section.key} banners={payload.banners} />;
            case 'categories_circles':
                return <Categories key={section.key} categories={payload.categories} title={section.title || 'أقسام الصيدلية'} />;
            case 'featured_products':
                return <ProductSlider key={section.key} title={section.title || 'منتجات مميزة'} eyebrow="اختيارات الصيدلية" products={productsFor(section.key, featured.length ? featured : payload.products.slice(0, 12))} routes={payload.routes} />;
            case 'flash_deals':
                return <ProductSlider key={section.key} title={section.title || 'عروض اليوم'} eyebrow="Flash Deals" products={productsFor(section.key, flash.length ? flash : payload.products.slice(0, 12))} routes={payload.routes} urgent countdown />;
            case 'all_products_cta':
                return <AllProductsCta key={section.key} routes={payload.routes} />;
            case 'brands':
                return <Brands key={section.key} title={section.title || 'أشهر الماركات الطبية'} brandLogos={payload.brandLogos || []} />;
            case 'collections':
            case 'concerns':
                return <Concerns key={section.key} title={section.title || 'حلول صحية موجهة'} />;
            case 'best_sellers':
                return <ProductSlider key={section.key} title={section.title || 'الأكثر مبيعا'} eyebrow="Best Sellers" products={productsFor(section.key, best)} routes={payload.routes} />;
            case 'new_arrivals':
                return <ProductSlider key={section.key} title={section.title || 'وصل حديثا'} eyebrow="Recently Added" products={productsFor(section.key, recent)} routes={payload.routes} />;
            case 'testimonials':
                return <Testimonials key={section.key} />;
            case 'app_banner':
                return <AppBanner key={section.key} />;
            case 'newsletter':
                return <Newsletter key={section.key} />;
            default:
                return null;
        }
    };

    return (
        <div className="min-h-screen bg-[#f5f8fb] text-slate-950 dark:bg-slate-950 dark:text-white">
            <TopBar routes={payload.routes} />
            <Header payload={payload} />
            <MegaNav products={payload.products} routes={payload.routes} />
            {sectionList.map(renderSection)}
            <Footer routes={payload.routes} />
            <PreviewModal routes={payload.routes} />
            <MobileBottomNav routes={payload.routes} />
        </div>
    );
}

export function mountStorefrontHome(element, payload) {
    createRoot(element).render(
        <QueryClientProvider client={queryClient}>
            <HomePage initialPayload={payload} />
        </QueryClientProvider>
    );
}
