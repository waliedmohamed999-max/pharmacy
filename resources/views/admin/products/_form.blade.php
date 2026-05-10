@csrf
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card-premium p-4 space-y-4">
        @include('admin.components.input', ['name' => 'name', 'label' => 'اسم المنتج', 'value' => old('name', $product->name ?? ''), 'required' => true])
        @include('admin.components.input', ['name' => 'sku', 'label' => 'SKU', 'value' => old('sku', $product->sku ?? '')])

        <div class="grid md:grid-cols-[1fr_auto] gap-2 items-end">
            @include('admin.components.input', ['name' => 'barcode', 'label' => 'الباركود', 'value' => old('barcode', $product->barcode ?? ''), 'hint' => 'إذا تركته فارغًا سيتم توليده تلقائيًا'])
            <button type="button" class="btn-secondary h-[42px]" id="generateBarcodeBtn">توليد باركود</button>
        </div>

        @include('admin.components.select', [
            'name' => 'category_id',
            'label' => 'التصنيف',
            'selected' => old('category_id', $product->category_id ?? ''),
            'options' => $categories->pluck('display_name','id')->toArray(),
        ])

        <div class="grid md:grid-cols-2 gap-4">
            @include('admin.components.input', ['name' => 'price', 'label' => 'السعر الحالي', 'type' => 'number', 'value' => old('price', $product->price ?? 0)])
            @include('admin.components.input', ['name' => 'compare_price', 'label' => 'السعر قبل الخصم', 'type' => 'number', 'value' => old('compare_price', $product->compare_price ?? '')])
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            @include('admin.components.input', ['name' => 'reorder_level', 'label' => 'حد الطلب', 'type' => 'number', 'value' => old('reorder_level', $product->reorder_level ?? 0)])
            @include('admin.components.input', ['name' => 'reorder_qty', 'label' => 'كمية الطلب المقترحة', 'type' => 'number', 'value' => old('reorder_qty', $product->reorder_qty ?? 0)])
        </div>

        @include('admin.components.input', ['name' => 'tags', 'label' => 'Tags / Collections', 'value' => old('tags', $product->tags ?? ''), 'hint' => 'مثال: برد وزكام, فيتامينات'])
        @include('admin.components.input', ['name' => 'short_description', 'label' => 'وصف مختصر', 'value' => old('short_description', $product->short_description ?? '')])
        @include('admin.components.textarea', ['name' => 'description', 'label' => 'الوصف', 'rows' => 5, 'value' => old('description', $product->description ?? '')])
    </div>

    <div class="card-premium p-4 space-y-4 h-fit">
        @include('admin.components.input', ['name' => 'quantity', 'label' => 'المخزون', 'type' => 'number', 'value' => old('quantity', $product->quantity ?? 0)])

        @php
            $currentPrimary = !empty($product->primary_image) ? $product->image_url : null;
        @endphp

        @include('admin.components.dropzone', ['name' => 'primary_image', 'label' => 'الصورة الرئيسية', 'current' => $currentPrimary])
        @include('admin.components.input', [
            'name' => 'primary_image_url',
            'label' => 'أو أدخل رابط صورة',
            'value' => old('primary_image_url', (isset($product->primary_image) && (str_starts_with($product->primary_image, 'http://') || str_starts_with($product->primary_image, 'https://'))) ? $product->primary_image : ''),
            'placeholder' => 'https://example.com/image.jpg',
            'hint' => 'إذا رفعت ملف ورابط معًا، يتم استخدام الملف المرفوع',
        ])

        @include('admin.components.dropzone', ['name' => 'gallery[]', 'label' => 'صور المعرض', 'multiple' => true])

        @include('admin.components.toggle', ['name' => 'is_active', 'label' => 'منتج نشط', 'checked' => old('is_active', $product->is_active ?? true)])
        @include('admin.components.toggle', ['name' => 'featured', 'label' => 'منتج مميز', 'checked' => old('featured', $product->featured ?? false)])

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">حفظ</button>
            @if(!empty($product->id))
                <a href="{{ route('admin.products.barcode', $product) }}" target="_blank" class="btn-secondary">طباعة الباركود</a>
            @endif
            <a href="{{ route('admin.products.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </div>
</div>

@if(isset($product) && $product->images->count())
<div class="card-premium p-4 mt-4">
    <h3 class="font-black mb-3">صور المعرض الحالية</h3>
    <div class="grid md:grid-cols-6 gap-3">
        @foreach($product->images as $img)
            <label class="card-premium p-2 block text-center">
                <img src="{{ str_starts_with($img->path, 'http://') || str_starts_with($img->path, 'https://') ? $img->path : asset(str_starts_with($img->path,'images/') ? $img->path : 'storage/'.$img->path) }}" class="w-full h-24 object-cover rounded mb-2">
                <input type="checkbox" name="delete_gallery[]" value="{{ $img->id }}"> حذف
            </label>
        @endforeach
    </div>
</div>
@endif

<script>
    const barcodeInput = document.querySelector('input[name="barcode"]');
    const skuInput = document.querySelector('input[name="sku"]');
    const nameInput = document.querySelector('input[name="name"]');
    const btn = document.getElementById('generateBarcodeBtn');

    function norm(v) {
        return (v || '').toUpperCase().replace(/[^A-Z0-9\-\.\$\/\+\% ]/g, '').trim();
    }

    btn?.addEventListener('click', () => {
        const sku = norm(skuInput?.value || '');
        const name = norm(nameInput?.value || '').replace(/\s+/g, '');
        const random = Math.floor(Math.random() * 900000 + 100000).toString();
        barcodeInput.value = sku || (name ? `${name}-${random}` : `PRD-${random}`);
    });
</script>
