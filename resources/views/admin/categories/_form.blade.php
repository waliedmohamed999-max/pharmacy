@csrf
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card-premium p-4 space-y-4">
        @include('admin.components.input', [
            'name' => 'name_ar',
            'label' => 'اسم التصنيف (عربي)',
            'value' => old('name_ar', $category->name_ar ?? $category->name ?? ''),
            'required' => true,
        ])

        @include('admin.components.input', [
            'name' => 'name_en',
            'label' => 'اسم التصنيف (English)',
            'value' => old('name_en', $category->name_en ?? ''),
        ])

        @include('admin.components.select', [
            'name' => 'parent_id',
            'label' => 'التصنيف الأب',
            'selected' => old('parent_id', $category->parent_id ?? ''),
            'options' => $parents->pluck('display_name','id')->toArray(),
            'placeholder' => 'بدون',
        ])

        @php
            $current = !empty($category->image)
                ? (str_starts_with($category->image, 'images/') ? asset($category->image) : asset('storage/'.$category->image))
                : null;
        @endphp

        @include('admin.components.dropzone', [
            'name' => 'image',
            'label' => 'صورة التصنيف',
            'hint' => 'JPG/PNG/WEBP - الحد الأقصى 2MB',
            'current' => $current,
        ])
    </div>

    <div class="card-premium p-4 space-y-4 h-fit">
        @include('admin.components.input', [
            'name' => 'sort_order',
            'label' => 'الترتيب',
            'type' => 'number',
            'value' => old('sort_order', $category->sort_order ?? 0),
        ])

        @include('admin.components.toggle', [
            'name' => 'is_active',
            'label' => 'تصنيف نشط',
            'checked' => old('is_active', $category->is_active ?? true),
        ])

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">حفظ</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </div>
</div>
