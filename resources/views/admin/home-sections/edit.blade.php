@extends('admin.layouts.app')

@section('page-title', 'تعديل قسم الواجهة')

@php
    $filters = $homeSection->filters_json ?? [];
    $selectedProducts = $homeSection->items->where('item_type', 'product')->pluck('item_id')->all();
    $selectedCategories = $homeSection->items->where('item_type', 'category')->pluck('item_id')->all();
    $brandLogos = $brandLogos ?? [];
    $assetUrl = function (?string $path) {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset(str_starts_with($path, 'images/') ? $path : 'storage/'.$path);
    };
    $sourceOptions = [
        'banners' => 'بنرات الصفحة الرئيسية',
        'categories' => 'تصنيفات الصيدلية',
        'products' => 'منتجات مختارة',
        'discounted' => 'منتجات عليها خصم',
        'best_sellers' => 'الأكثر مبيعاً',
        'new_arrivals' => 'وصل حديثاً',
        'tags' => 'وسوم / احتياجات صحية',
        'custom' => 'مكون ثابت داخل الواجهة',
    ];
    $controlMap = [
        'slider_banners' => ['label' => 'إدارة البنرات', 'url' => route('admin.banners.index')],
        'categories_circles' => ['label' => 'إدارة التصنيفات', 'url' => route('admin.categories.index')],
        'featured_products' => ['label' => 'إدارة المنتجات', 'url' => route('admin.products.index')],
        'flash_deals' => ['label' => 'إدارة الخصومات', 'url' => route('admin.products.index')],
        'best_sellers' => ['label' => 'إدارة المنتجات', 'url' => route('admin.products.index')],
        'new_arrivals' => ['label' => 'إدارة المنتجات', 'url' => route('admin.products.index')],
        'all_products_cta' => ['label' => 'صفحة كل المنتجات', 'url' => route('store.products.index')],
        'brands' => ['label' => 'إدارة المنتجات والماركات', 'url' => route('admin.products.index')],
        'collections' => ['label' => 'إدارة وسوم الاحتياجات', 'url' => route('admin.products.index')],
        'testimonials' => ['label' => 'إدارة الصفحات', 'url' => route('admin.pages.index')],
        'app_banner' => ['label' => 'إدارة إعدادات الواجهة', 'url' => route('admin.footer.edit')],
        'newsletter' => ['label' => 'إدارة الفوتر والاشتراك', 'url' => route('admin.footer.edit')],
    ];
    $control = $controlMap[$homeSection->key] ?? null;
@endphp

@section('content')
<div class="space-y-5">
    <section class="card-premium flex flex-col justify-between gap-4 p-6 lg:flex-row lg:items-center">
        <div>
            <span class="text-xs font-black uppercase text-emerald-600">Storefront Control</span>
            <h1 class="mt-2 text-3xl font-black text-slate-950">تعديل قسم: {{ $homeSection->display_title ?: $homeSection->key }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">كل إعداد هنا ينعكس على الواجهة الخارجية حسب ترتيب Home Builder ونوع مصدر البيانات.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($control)
                <a href="{{ $control['url'] }}" class="btn-secondary">{{ $control['label'] }}</a>
            @endif
            <a href="{{ route('store.home') }}" target="_blank" class="btn-secondary">معاينة المتجر</a>
            <a href="{{ route('admin.home-sections.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('admin.home-sections.update', $homeSection) }}" class="card-premium p-5">
            @csrf
            @method('PUT')

            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-950">إعدادات العرض</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">تحكم في العنوان، المصدر، الحالة، وعدد العناصر الظاهرة.</p>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">
                    <input type="checkbox" name="is_active" value="1" class="accent-emerald-600" @checked(old('is_active', $homeSection->is_active))>
                    مفعل على الواجهة
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">العنوان العربي</span>
                    <input name="title_ar" class="input-premium" value="{{ old('title_ar', $homeSection->title_ar) }}" placeholder="مثال: عروض اليوم">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">العنوان الإنجليزي</span>
                    <input name="title_en" class="input-premium" value="{{ old('title_en', $homeSection->title_en) }}" placeholder="Today Deals">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">نوع القسم</span>
                    <select name="type" class="input-premium">
                        <option value="auto" @selected(old('type', $homeSection->type)==='auto')>تلقائي من قاعدة البيانات</option>
                        <option value="manual" @selected(old('type', $homeSection->type)==='manual')>اختيار يدوي</option>
                        <option value="static" @selected(old('type', $homeSection->type)==='static')>مكون ثابت</option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">مصدر البيانات</span>
                    <select name="data_source" class="input-premium">
                        @foreach($sourceOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('data_source', $homeSection->data_source)===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">عدد العناصر</span>
                    <input type="number" min="1" max="40" name="limit" class="input-premium" value="{{ old('limit', $filters['limit'] ?? 12) }}">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-black text-slate-600">وسوم الاحتياجات الصحية</span>
                    <input name="tags" class="input-premium" value="{{ old('tags', isset($filters['tags']) ? implode(', ', $filters['tags']) : '') }}" placeholder="نزلات البرد، السكري، العناية بالبشرة">
                </label>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <button class="btn-primary">حفظ إعدادات القسم</button>
                <a href="{{ route('admin.home-sections.index') }}" class="btn-secondary">إلغاء</a>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="card-premium p-5">
                <div class="text-xs font-black uppercase text-emerald-600">Route Key</div>
                <div class="mt-2 rounded-2xl bg-slate-50 px-4 py-3 font-mono text-sm font-black text-slate-700">{{ $homeSection->key }}</div>
                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="font-bold text-slate-400">الترتيب</div>
                        <div class="mt-1 text-xl font-black">{{ $homeSection->sort_order }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <div class="font-bold text-slate-400">العناصر</div>
                        <div class="mt-1 text-xl font-black">{{ $homeSection->items->count() }}</div>
                    </div>
                </div>
            </div>

            <div class="card-premium p-5">
                <h3 class="text-lg font-black">كيف يتم التحكم؟</h3>
                <p class="mt-2 text-sm font-bold leading-7 text-slate-500">
                    البنرات من قسم البنرات، التصنيفات من قسم التصنيفات، المنتجات من قسم المنتجات، والفوتر والصفحات من أقسام الواجهة الخارجية. ترتيب الظهور يتم من صفحة Home Builder.
                </p>
            </div>
        </aside>
    </div>

    @if($homeSection->key === 'brands')
        <form method="POST" action="{{ route('admin.home-sections.brands', $homeSection) }}" enctype="multipart/form-data" class="card-premium overflow-hidden p-0">
            @csrf
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 bg-gradient-to-l from-emerald-50 to-white p-5 lg:flex-row lg:items-center">
                <div>
                    <span class="text-xs font-black uppercase text-emerald-600">Brand Logo Manager</span>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">لوجوهات أشهر الماركات</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">ارفع صور PNG / JPG / WEBP / SVG، ورتب ظهورها من خلال ترتيب الحقول. الصور تظهر مباشرة في شريط الماركات بالواجهة.</p>
                </div>
                <button class="btn-primary">حفظ لوجوهات الماركات</button>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[1fr_380px]">
                <div class="space-y-4">
                    <h3 class="text-lg font-black text-slate-950">اللوجوهات الحالية</h3>
                    @forelse($brandLogos as $index => $brand)
                        <div class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-3 md:grid-cols-[120px_1fr_1fr_auto] md:items-center">
                            <div class="grid h-24 place-items-center rounded-2xl bg-slate-50 p-3">
                                @if(!empty($brand['image']))
                                    <img src="{{ $assetUrl($brand['image']) }}" alt="{{ $brand['name'] }}" class="max-h-16 max-w-full object-contain">
                                @else
                                    <span class="text-xs font-black text-slate-400">بدون صورة</span>
                                @endif
                            </div>
                            <input type="hidden" name="existing[{{ $index }}][image]" value="{{ $brand['image'] ?? '' }}">
                            <label class="space-y-1">
                                <span class="text-xs font-black text-slate-500">اسم الماركة</span>
                                <input name="existing[{{ $index }}][name]" class="input-premium" value="{{ $brand['name'] ?? '' }}" placeholder="Bioderma">
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs font-black text-slate-500">رابط اختياري</span>
                                <input name="existing[{{ $index }}][url]" class="input-premium" value="{{ $brand['url'] ?? '' }}" placeholder="https://...">
                            </label>
                            <label class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-black text-rose-700">
                                <input type="checkbox" name="existing[{{ $index }}][remove]" value="1" class="accent-rose-600">
                                حذف
                            </label>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm font-bold text-slate-500">
                            لا توجد لوجوهات مرفوعة حالياً. أضف أول مجموعة من الحقول على اليسار.
                        </div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-emerald-100 bg-emerald-50/60 p-4">
                    <h3 class="text-lg font-black text-slate-950">إضافة لوجوهات جديدة</h3>
                    <p class="mt-1 text-sm font-bold text-slate-500">يمكنك رفع أكثر من لوجو مرة واحدة. يفضل استخدام SVG أو PNG بخلفية شفافة.</p>
                    <div class="mt-4 space-y-3">
                        @for($i = 0; $i < 6; $i++)
                            <div class="rounded-3xl border border-white bg-white/85 p-3 shadow-sm">
                                <input name="brand_names[{{ $i }}]" class="input-premium mb-2" placeholder="اسم الماركة">
                                <input name="brand_urls[{{ $i }}]" class="input-premium mb-2" placeholder="رابط اختياري">
                                <input type="file" name="brand_logos[{{ $i }}]" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="input-premium">
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.home-sections.items', $homeSection) }}" class="card-premium p-5">
        @csrf
        @method('PATCH')

        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-950">العناصر اليدوية</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">استخدمها للأقسام اليدوية مثل منتجات مميزة أو ترتيب تصنيفات محدد.</p>
            </div>
            <button class="btn-primary">حفظ العناصر</button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-black text-slate-600">منتجات مرتبطة بالقسم</span>
                <select name="product_ids[]" multiple size="14" class="input-premium min-h-[340px]">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(in_array($product->id, $selectedProducts))>
                            {{ $product->name }} - {{ $product->sku ?: 'بدون SKU' }} - {{ number_format((float) $product->price, 2) }} ج.م
                        </option>
                    @endforeach
                </select>
                <span class="block text-xs font-bold text-slate-400">اضغط Ctrl لاختيار أكثر من منتج.</span>
            </label>

            <label class="space-y-2">
                <span class="text-sm font-black text-slate-600">تصنيفات مرتبطة بالقسم</span>
                <select name="category_ids[]" multiple size="14" class="input-premium min-h-[340px]">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories))>
                            {{ $category->display_name }} / {{ $category->slug }}
                        </option>
                    @endforeach
                </select>
                <span class="block text-xs font-bold text-slate-400">مفيد لقسم أقسام الصيدلية عند تحويله إلى اختيار يدوي.</span>
            </label>
        </div>
    </form>
</div>
@endsection
