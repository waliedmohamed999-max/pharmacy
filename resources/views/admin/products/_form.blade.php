@csrf
@php
    $isEdit = !empty($product->id);
    $currentPrimary = !empty($product->primary_image) ? $product->image_url : null;
    $selectedCategory = old('category_id', $product->category_id ?? '');
    $activeChecked = (bool) old('is_active', $product->is_active ?? true);
    $featuredChecked = (bool) old('featured', $product->featured ?? false);
    $priceValue = old('price', $product->price ?? 0);
    $compareValue = old('compare_price', $product->compare_price ?? '');
    $quantityValue = old('quantity', $product->quantity ?? 0);
    $reorderLevelValue = old('reorder_level', $product->reorder_level ?? 0);
    $reorderQtyValue = old('reorder_qty', $product->reorder_qty ?? 0);
@endphp

<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Product Master Data</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">{{ $isEdit ? 'تعديل بيانات المنتج' : 'إضافة منتج جديد' }}</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">أدخل بيانات المنتج، الأسعار، المخزون، الصور، والباركود من شاشة واحدة واضحة.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                <div class="text-xs font-bold text-slate-500">السعر</div>
                <div id="summary-price" class="mt-1 text-sm font-black text-emerald-700">0.00</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                <div class="text-xs font-bold text-slate-500">الخصم</div>
                <div id="summary-discount" class="mt-1 text-sm font-black text-slate-950">0%</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                <div class="text-xs font-bold text-slate-500">المخزون</div>
                <div id="summary-stock" class="mt-1 text-sm font-black text-slate-950">0</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                <div class="text-xs font-bold text-slate-500">الحالة</div>
                <div id="summary-status" class="mt-1 text-sm font-black text-slate-950">نشط</div>
            </div>
        </div>
    </div>
</section>

<div class="mt-5 grid gap-5 xl:grid-cols-[1fr_360px]">
    <main class="space-y-5">
        <section class="card-premium p-5">
            <div class="mb-4 text-right">
                <h2 class="text-xl font-black text-slate-950">البيانات الأساسية</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">اسم واضح وتصنيف صحيح يساعدان في البحث ونقاط البيع والتطبيق.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-black text-slate-500">اسم المنتج</label>
                    <input id="product-name" type="text" name="name" class="input-premium h-12 text-base font-bold" value="{{ old('name', $product->name ?? '') }}" required>
                    @error('name')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">SKU</label>
                    <input id="sku-input" type="text" name="sku" class="input-premium" value="{{ old('sku', $product->sku ?? '') }}" placeholder="مثال: MED-1001">
                    @error('sku')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">التصنيف</label>
                    <select name="category_id" class="select-premium" required>
                        <option value="">اختر التصنيف</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>
                                {{ $category->display_name ?? $category->name_ar ?? $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-black text-slate-500">الباركود</label>
                    <div class="grid gap-2 md:grid-cols-[1fr_auto]">
                        <input id="barcode-input" type="text" name="barcode" class="input-premium" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="اتركه فارغاً ليتم توليده تلقائياً">
                        <button type="button" id="generateBarcodeBtn" class="btn-secondary h-11 whitespace-nowrap">توليد باركود</button>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">إذا تركته فارغاً سيتم توليده تلقائياً عند الحفظ.</p>
                    @error('barcode')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card-premium p-5">
            <div class="mb-4 text-right">
                <h2 class="text-xl font-black text-slate-950">الأسعار والمخزون</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">حدد سعر البيع وحدود إعادة الطلب ليظهر المنتج بدقة في التقارير والمخزون.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">السعر الحالي</label>
                    <input id="price-input" type="number" step="0.01" min="0" name="price" class="input-premium" value="{{ $priceValue }}" required>
                    @error('price')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">السعر قبل الخصم</label>
                    <input id="compare-input" type="number" step="0.01" min="0" name="compare_price" class="input-premium" value="{{ $compareValue }}">
                    @error('compare_price')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">المخزون الحالي</label>
                    <input id="quantity-input" type="number" min="0" name="quantity" class="input-premium" value="{{ $quantityValue }}" required>
                    @error('quantity')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">حد الطلب</label>
                    <input id="reorder-level-input" type="number" step="0.01" min="0" name="reorder_level" class="input-premium" value="{{ $reorderLevelValue }}">
                    @error('reorder_level')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-xs font-black text-slate-500">كمية الطلب المقترحة</label>
                    <input type="number" step="0.01" min="0" name="reorder_qty" class="input-premium" value="{{ $reorderQtyValue }}">
                    @error('reorder_qty')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card-premium p-5">
            <div class="mb-4 text-right">
                <h2 class="text-xl font-black text-slate-950">الوصف والوسوم</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">استخدم كلمات واضحة تساعد في البحث وتجميع المنتجات داخل المتجر.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">Tags / Collections</label>
                    <input type="text" name="tags" class="input-premium" value="{{ old('tags', $product->tags ?? '') }}" placeholder="مثال: برد وزكام، فيتامينات">
                    @error('tags')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">وصف مختصر</label>
                    <input type="text" name="short_description" class="input-premium" value="{{ old('short_description', $product->short_description ?? '') }}" placeholder="سطر مختصر يظهر في بطاقة المنتج">
                    @error('short_description')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">الوصف الكامل</label>
                    <textarea name="description" rows="6" class="input-premium" placeholder="تفاصيل المنتج، الاستخدام، التحذيرات، أو الملاحظات">{{ old('description', $product->description ?? '') }}</textarea>
                    @error('description')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
    </main>

    <aside class="space-y-5">
        <section class="card-premium p-5">
            <div class="mb-4 text-right">
                <h2 class="text-xl font-black text-slate-950">معاينة المنتج</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">تأكد من الصورة والباركود قبل الحفظ.</p>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                <div class="aspect-square bg-slate-50">
                    <img id="primary-preview" src="{{ $currentPrimary ?: asset('images/placeholder.png') }}" alt="" class="h-full w-full object-cover">
                </div>
                <div class="p-4 text-right">
                    <div id="preview-name" class="truncate text-lg font-black text-slate-950">{{ old('name', $product->name ?? 'اسم المنتج') ?: 'اسم المنتج' }}</div>
                    <div class="mt-1 text-sm font-bold text-emerald-700"><span id="preview-price">0.00</span></div>
                    <div id="barcode-preview" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-3 text-center">
                        <div class="mx-auto flex h-10 max-w-52 items-end justify-center gap-1">
                            @for($i = 0; $i < 24; $i++)
                                <span class="barcode-bar block w-1 rounded-sm bg-slate-900" style="height: {{ 14 + (($i * 7) % 24) }}px"></span>
                            @endfor
                        </div>
                        <div id="barcode-preview-value" class="mt-2 font-mono text-xs font-black text-slate-600">{{ old('barcode', $product->barcode ?? '') ?: 'AUTO' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card-premium p-5">
            <h2 class="mb-4 text-right text-xl font-black text-slate-950">الصور</h2>
            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-black text-slate-700">الصورة الرئيسية</label>
                    <label class="dropzone-premium block cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center transition hover:border-emerald-300 hover:bg-emerald-50">
                        <input id="primary-image-input" type="file" name="primary_image" accept="image/png,image/jpeg,image/webp" class="hidden">
                        <span class="text-sm font-black text-slate-700">اسحب الصورة هنا أو اضغط للاختيار</span>
                        <span class="mt-1 block text-xs font-semibold text-slate-500">JPG / PNG / WEBP حتى 4MB</span>
                    </label>
                    @error('primary_image')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-black text-slate-500">أو أدخل رابط صورة</label>
                    <input id="primary-image-url" type="url" name="primary_image_url" class="input-premium" value="{{ old('primary_image_url', (isset($product->primary_image) && (str_starts_with($product->primary_image, 'http://') || str_starts_with($product->primary_image, 'https://'))) ? $product->primary_image : '') }}" placeholder="https://example.com/image.jpg">
                    <p class="mt-1 text-xs font-semibold text-slate-500">إذا رفعت ملفاً وأدخلت رابطاً، سيتم استخدام الملف المرفوع.</p>
                    @error('primary_image_url')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-black text-slate-700">صور المعرض</label>
                    <label class="dropzone-premium block cursor-pointer rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center transition hover:border-emerald-300 hover:bg-emerald-50">
                        <input id="gallery-input" type="file" name="gallery[]" accept="image/png,image/jpeg,image/webp" class="hidden" multiple>
                        <span class="text-sm font-black text-slate-700">اختيار صور إضافية</span>
                        <span id="gallery-count" class="mt-1 block text-xs font-semibold text-slate-500">يمكن رفع حتى 10 صور</span>
                    </label>
                    @error('gallery')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    @error('gallery.*')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card-premium p-5">
            <h2 class="mb-4 text-right text-xl font-black text-slate-950">النشر</h2>
            <input type="hidden" name="is_active" value="0">
            <input type="hidden" name="featured" value="0">
            <div class="space-y-3">
                <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <span class="text-sm font-black text-slate-800">منتج نشط</span>
                    <input id="active-toggle" type="checkbox" name="is_active" value="1" class="h-5 w-5 accent-emerald-600" @checked($activeChecked)>
                </label>
                <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <span class="text-sm font-black text-slate-800">منتج مميز</span>
                    <input type="checkbox" name="featured" value="1" class="h-5 w-5 accent-emerald-600" @checked($featuredChecked)>
                </label>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button class="btn-primary px-7">حفظ</button>
                @if($isEdit)
                    <a href="{{ route('admin.products.barcode', $product) }}" target="_blank" class="btn-secondary">طباعة الباركود</a>
                @endif
                <a href="{{ route('admin.products.index') }}" class="btn-secondary">رجوع</a>
            </div>
        </section>
    </aside>
</div>

@if($isEdit && $product->images->count())
    <section class="card-premium mt-5 p-5">
        <div class="mb-4 text-right">
            <h2 class="text-xl font-black text-slate-950">صور المعرض الحالية</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">حدد الصور التي تريد حذفها عند حفظ التعديل.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-6">
            @foreach($product->images as $image)
                @php
                    $imageUrl = str_starts_with($image->path, 'http://') || str_starts_with($image->path, 'https://')
                        ? $image->path
                        : asset(str_starts_with($image->path, 'images/') ? $image->path : 'storage/' . $image->path);
                @endphp
                <label class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-center">
                    <img src="{{ $imageUrl }}" class="h-28 w-full rounded-xl object-cover" alt="">
                    <span class="mt-2 flex items-center justify-center gap-2 text-xs font-black text-rose-600">
                        <input type="checkbox" name="delete_gallery[]" value="{{ $image->id }}" class="accent-rose-600">
                        حذف الصورة
                    </span>
                </label>
            @endforeach
        </div>
    </section>
@endif

<script>
    (() => {
        const nameInput = document.getElementById('product-name');
        const skuInput = document.getElementById('sku-input');
        const barcodeInput = document.getElementById('barcode-input');
        const priceInput = document.getElementById('price-input');
        const compareInput = document.getElementById('compare-input');
        const quantityInput = document.getElementById('quantity-input');
        const activeToggle = document.getElementById('active-toggle');
        const primaryInput = document.getElementById('primary-image-input');
        const primaryUrl = document.getElementById('primary-image-url');
        const galleryInput = document.getElementById('gallery-input');
        const generateBtn = document.getElementById('generateBarcodeBtn');

        const summaryPrice = document.getElementById('summary-price');
        const summaryDiscount = document.getElementById('summary-discount');
        const summaryStock = document.getElementById('summary-stock');
        const summaryStatus = document.getElementById('summary-status');
        const previewName = document.getElementById('preview-name');
        const previewPrice = document.getElementById('preview-price');
        const primaryPreview = document.getElementById('primary-preview');
        const barcodePreviewValue = document.getElementById('barcode-preview-value');
        const galleryCount = document.getElementById('gallery-count');

        const money = (value) => Number(value || 0).toLocaleString('ar-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const numberValue = (input) => Number.parseFloat(input?.value || '0') || 0;
        const norm = (value) => (value || '').toUpperCase().replace(/[^A-Z0-9\-.$/+% ]/g, '').trim();

        const update = () => {
            const price = numberValue(priceInput);
            const compare = numberValue(compareInput);
            const discount = compare > price && compare > 0 ? Math.round(((compare - price) / compare) * 100) : 0;

            summaryPrice.textContent = money(price);
            summaryDiscount.textContent = `${discount}%`;
            summaryStock.textContent = Number(quantityInput.value || 0).toLocaleString('ar-SA');
            summaryStatus.textContent = activeToggle.checked ? 'نشط' : 'غير نشط';
            previewName.textContent = nameInput.value.trim() || 'اسم المنتج';
            previewPrice.textContent = money(price);
            barcodePreviewValue.textContent = barcodeInput.value.trim() || 'AUTO';
        };

        generateBtn?.addEventListener('click', () => {
            const sku = norm(skuInput.value);
            const name = norm(nameInput.value).replace(/\s+/g, '');
            const random = Math.floor(Math.random() * 900000 + 100000).toString();
            barcodeInput.value = sku || (name ? `${name}-${random}` : `PRD-${random}`);
            update();
        });

        primaryInput?.addEventListener('change', () => {
            const file = primaryInput.files?.[0];
            if (!file) {
                return;
            }
            primaryPreview.src = URL.createObjectURL(file);
        });

        primaryUrl?.addEventListener('input', () => {
            if (primaryUrl.value.trim()) {
                primaryPreview.src = primaryUrl.value.trim();
            }
        });

        galleryInput?.addEventListener('change', () => {
            const count = galleryInput.files?.length || 0;
            galleryCount.textContent = count ? `تم اختيار ${count} صورة` : 'يمكن رفع حتى 10 صور';
        });

        [nameInput, skuInput, barcodeInput, priceInput, compareInput, quantityInput, activeToggle].forEach((element) => {
            element?.addEventListener('input', update);
            element?.addEventListener('change', update);
        });

        update();
    })();
</script>
