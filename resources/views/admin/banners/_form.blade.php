@csrf
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card-premium p-4 space-y-4">
        @include('admin.components.input', ['name' => 'title', 'label' => 'العنوان', 'value' => old('title', $banner->title ?? ''), 'required' => true])
        @include('admin.components.input', ['name' => 'subtitle', 'label' => 'العنوان الفرعي', 'value' => old('subtitle', $banner->subtitle ?? '')])

        @include('admin.components.select', [
            'name' => 'link_type',
            'label' => 'نوع الرابط',
            'selected' => old('link_type', $banner->link_type ?? 'url'),
            'options' => ['url' => 'رابط خارجي', 'product' => 'منتج', 'category' => 'تصنيف'],
        ])

        @include('admin.components.input', ['name' => 'link_target', 'label' => 'قيمة الرابط', 'value' => old('link_target', $banner->link_target ?? ''), 'hint' => 'URL أو ID المنتج/التصنيف'])

        <div class="grid md:grid-cols-2 gap-4">
            @include('admin.components.input', ['name' => 'start_date', 'label' => 'تاريخ البداية', 'type' => 'date', 'value' => old('start_date', optional($banner->start_date ?? null)->format('Y-m-d'))])
            @include('admin.components.input', ['name' => 'end_date', 'label' => 'تاريخ النهاية', 'type' => 'date', 'value' => old('end_date', optional($banner->end_date ?? null)->format('Y-m-d'))])
        </div>

        @php
            $currentImage = !empty($banner->image)
                ? (str_starts_with($banner->image, 'images/') ? asset($banner->image) : asset('storage/'.$banner->image))
                : null;
        @endphp

        @include('admin.components.dropzone', ['name' => 'image', 'label' => 'صورة البنر', 'current' => $currentImage])
    </div>

    <div class="card-premium p-4 space-y-4 h-fit">
        @include('admin.components.input', ['name' => 'sort_order', 'label' => 'الترتيب', 'type' => 'number', 'value' => old('sort_order', $banner->sort_order ?? 0)])
        @include('admin.components.toggle', ['name' => 'is_active', 'label' => 'بنر نشط', 'checked' => old('is_active', $banner->is_active ?? true)])

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">حفظ</button>
            <a href="{{ route('admin.banners.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </div>
</div>
