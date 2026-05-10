@extends('admin.layouts.app')

@section('title', 'Home Builder')
@section('page-title', 'Home Builder')
@section('page-subtitle', 'تحكم كامل في ترتيب ومحتوى أقسام الصفحة الرئيسية للمتجر')

@section('page-actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.banners.index') }}" class="btn-secondary">إدارة البنرات</a>
        <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="btn-primary">معاينة المتجر</a>
    </div>
@endsection

@section('content')
@php
    $totalSections = $sections->count();
    $activeSections = $sections->where('is_active', true)->count();
    $manualSections = $sections->where('type', 'manual')->count();
    $autoSections = $sections->where('type', 'auto')->count();
    $staticSections = $sections->where('type', 'static')->count();
    $totalItems = $sections->sum(fn ($section) => $section->items->count());

    $typeLabels = [
        'auto' => 'تلقائي',
        'manual' => 'يدوي',
        'static' => 'ثابت',
    ];

    $typeClasses = [
        'auto' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'manual' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'static' => 'bg-amber-50 text-amber-700 ring-amber-100',
    ];

    $sourceLabels = [
        'banners' => 'بنرات رئيسية',
        'categories' => 'التصنيفات',
        'discounted' => 'عروض اليوم',
        'products' => 'منتجات مختارة',
        'best_sellers' => 'الأكثر مبيعاً',
        'new_arrivals' => 'وصل حديثاً',
        'tags' => 'حسب الاحتياج',
    ];

    $summaryCards = [
        ['label' => 'إجمالي الأقسام', 'value' => $totalSections, 'hint' => 'قسم في الواجهة', 'tone' => 'from-emerald-600 to-teal-500'],
        ['label' => 'الأقسام المفعلة', 'value' => $activeSections, 'hint' => 'تظهر للعميل الآن', 'tone' => 'from-teal-600 to-cyan-500'],
        ['label' => 'مصادر تلقائية', 'value' => $autoSections, 'hint' => 'تتحدث من المنتجات', 'tone' => 'from-sky-600 to-blue-500'],
        ['label' => 'عناصر يدوية', 'value' => $totalItems, 'hint' => 'عنصر مثبت داخل الأقسام', 'tone' => 'from-violet-600 to-fuchsia-500'],
    ];
@endphp

<div class="space-y-5">
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <div class="card-premium overflow-hidden p-0">
                <div class="relative p-4">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l {{ $card['tone'] }}"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($card['value']) }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $card['hint'] }}</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(330px,0.55fr)]">
        <form method="POST" action="{{ route('admin.home-sections.order') }}" class="card-premium p-4">
            @csrf
            @method('PATCH')

            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">ترتيب أقسام الصفحة الرئيسية</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">اسحب القسم لتغيير مكان ظهوره. الأعلى يظهر أولاً في المتجر.</p>
                </div>
                <button class="btn-primary">حفظ الترتيب</button>
            </div>

            <div id="section-sort-list" class="space-y-3">
                @forelse($sections as $section)
                    @php
                        $type = $section->type ?: 'auto';
                        $source = $section->data_source ?: 'custom';
                        $limit = $section->filters_json['limit'] ?? null;
                        $itemsCount = $section->items->count();
                    @endphp
                    <article class="section-row group rounded-3xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/5" draggable="true" data-id="{{ $section->id }}">
                        <input type="hidden" name="ids[]" value="{{ $section->id }}">

                        <div class="grid gap-3 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                            <div class="flex items-center gap-3">
                                <button type="button" class="drag-handle grid h-11 w-11 cursor-move place-items-center rounded-2xl bg-slate-100 text-slate-500 transition group-hover:bg-emerald-600 group-hover:text-white" aria-label="سحب لتغيير الترتيب">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 5h.01M15 5h.01M9 12h.01M15 12h.01M9 19h.01M15 19h.01"/>
                                    </svg>
                                </button>
                                <span class="order-badge grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-sm font-black text-emerald-700 ring-1 ring-emerald-100">{{ $loop->iteration }}</span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-black text-slate-950">{{ $section->display_title ?: $section->key }}</h3>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $typeClasses[$type] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">{{ $typeLabels[$type] ?? $type }}</span>
                                    <span class="{{ $section->is_active ? 'badge-success' : 'badge-danger' }}">{{ $section->is_active ? 'مفعل' : 'متوقف' }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">المصدر: {{ $sourceLabels[$source] ?? $source }}</span>
                                    @if($limit)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1">حد العرض: {{ $limit }}</span>
                                    @endif
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">العناصر اليدوية: {{ $itemsCount }}</span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">المفتاح: {{ $section->key }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <a href="{{ route('admin.home-sections.edit', $section) }}" class="btn-secondary">تعديل</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">لا توجد أقسام للصفحة الرئيسية حالياً.</div>
                @endforelse
            </div>
        </form>

        <aside class="space-y-5">
            <div class="card-premium overflow-hidden p-0">
                <div class="bg-gradient-to-br from-emerald-700 to-teal-500 p-5 text-white">
                    <p class="text-xs font-black uppercase text-white/70">Storefront Preview</p>
                    <h2 class="mt-1 text-xl font-black">خريطة الصفحة الرئيسية</h2>
                    <p class="mt-2 text-sm font-semibold text-white/75">الترتيب الحالي كما سيظهر للعميل في واجهة الصيدلية.</p>
                </div>
                <div class="space-y-3 p-4">
                    @foreach($sections->take(7) as $section)
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-white text-sm font-black text-emerald-700 shadow-sm">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-slate-900">{{ $section->display_title ?: $section->key }}</p>
                                <p class="truncate text-xs font-bold text-slate-500">{{ $sourceLabels[$section->data_source] ?? ($section->data_source ?: 'مصدر مخصص') }}</p>
                            </div>
                            <span class="h-2.5 w-2.5 rounded-full {{ $section->is_active ? 'bg-emerald-500' : 'bg-rose-400' }}"></span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-premium p-4">
                <h3 class="text-base font-black text-slate-950">توزيع الأقسام</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <div class="mb-1 flex justify-between text-xs font-black text-slate-500"><span>تلقائي</span><span>{{ $autoSections }}</span></div>
                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-sky-500" style="width: {{ $totalSections ? ($autoSections / $totalSections) * 100 : 0 }}%"></div></div>
                    </div>
                    <div>
                        <div class="mb-1 flex justify-between text-xs font-black text-slate-500"><span>يدوي</span><span>{{ $manualSections }}</span></div>
                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-violet-500" style="width: {{ $totalSections ? ($manualSections / $totalSections) * 100 : 0 }}%"></div></div>
                    </div>
                    <div>
                        <div class="mb-1 flex justify-between text-xs font-black text-slate-500"><span>ثابت</span><span>{{ $staticSections }}</span></div>
                        <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-amber-500" style="width: {{ $totalSections ? ($staticSections / $totalSections) * 100 : 0 }}%"></div></div>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <section class="card-premium p-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-slate-950">تفاصيل الأقسام</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">إدارة الحالة، المصدر، النوع، وعدد العناصر لكل قسم.</p>
            </div>
            <input id="sectionsSearch" class="input-premium w-full md:w-80" type="search" placeholder="ابحث باسم القسم أو المصدر...">
        </div>

        <div class="table-wrap">
            <table class="table-premium" id="sectionsTable">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>النوع</th>
                        <th>المصدر</th>
                        <th>الحالة</th>
                        <th>العناصر</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $section)
                        @php
                            $type = $section->type ?: 'auto';
                            $source = $section->data_source ?: 'custom';
                        @endphp
                        <tr data-section-row data-search="{{ \Illuminate\Support\Str::lower(($section->display_title ?: $section->key) . ' ' . $type . ' ' . $source) }}">
                            <td>
                                <div class="font-black text-slate-950">{{ $section->display_title ?: $section->key }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ $section->key }}</div>
                            </td>
                            <td><span class="rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $typeClasses[$type] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">{{ $typeLabels[$type] ?? $type }}</span></td>
                            <td class="font-bold text-slate-600">{{ $sourceLabels[$source] ?? $source }}</td>
                            <td><span class="{{ $section->is_active ? 'badge-success' : 'badge-danger' }}">{{ $section->is_active ? 'مفعل' : 'متوقف' }}</span></td>
                            <td class="font-black text-slate-700">{{ $section->items->count() }}</td>
                            <td><a href="{{ route('admin.home-sections.edit', $section) }}" class="btn-secondary">فتح</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
(() => {
    const list = document.getElementById('section-sort-list');
    const search = document.getElementById('sectionsSearch');

    if (list) {
        let dragged = null;

        const refreshOrder = () => {
            list.querySelectorAll('.section-row').forEach((row, i) => {
                const badge = row.querySelector('.order-badge');
                if (badge) badge.textContent = i + 1;
            });
        };

        list.querySelectorAll('.section-row').forEach((row) => {
            row.addEventListener('dragstart', () => {
                dragged = row;
                row.classList.add('opacity-50', 'scale-[0.99]');
            });

            row.addEventListener('dragend', () => {
                row.classList.remove('opacity-50', 'scale-[0.99]');
                dragged = null;
                refreshOrder();
            });

            row.addEventListener('dragover', (event) => {
                event.preventDefault();
                row.classList.add('ring-2', 'ring-emerald-200');
            });

            row.addEventListener('dragleave', () => {
                row.classList.remove('ring-2', 'ring-emerald-200');
            });

            row.addEventListener('drop', (event) => {
                event.preventDefault();
                row.classList.remove('ring-2', 'ring-emerald-200');
                if (!dragged || dragged === row) return;

                const rows = [...list.querySelectorAll('.section-row')];
                const draggedIndex = rows.indexOf(dragged);
                const targetIndex = rows.indexOf(row);
                if (draggedIndex < targetIndex) row.after(dragged);
                else row.before(dragged);
                refreshOrder();
            });
        });
    }

    if (search) {
        search.addEventListener('input', () => {
            const value = search.value.trim().toLowerCase();
            document.querySelectorAll('[data-section-row]').forEach((row) => {
                row.classList.toggle('hidden', value && !row.dataset.search.includes(value));
            });
        });
    }
})();
</script>
@endsection
