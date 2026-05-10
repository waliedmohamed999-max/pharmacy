@csrf
<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card-premium p-4 space-y-4">
        @include('admin.components.input', [
            'name' => 'title',
            'label' => 'عنوان الصفحة',
            'value' => old('title', $page->title ?? ''),
            'required' => true,
        ])

        @include('admin.components.input', [
            'name' => 'slug',
            'label' => 'الرابط المختصر (Slug)',
            'value' => old('slug', $page->slug ?? ''),
            'hint' => 'اتركه فارغًا ليتم توليده تلقائيًا من العنوان',
        ])

        @include('admin.components.textarea', [
            'name' => 'excerpt',
            'label' => 'ملخص قصير',
            'rows' => 3,
            'value' => old('excerpt', $page->excerpt ?? ''),
        ])

        @include('admin.components.textarea', [
            'name' => 'content',
            'label' => 'محتوى الصفحة',
            'rows' => 14,
            'value' => old('content', $page->content ?? ''),
        ])
    </div>

    <div class="card-premium p-4 space-y-4 h-fit">
        @include('admin.components.input', [
            'name' => 'sort_order',
            'label' => 'الترتيب',
            'type' => 'number',
            'value' => old('sort_order', $page->sort_order ?? 0),
        ])

        @include('admin.components.toggle', [
            'name' => 'is_active',
            'label' => 'صفحة نشطة',
            'checked' => old('is_active', $page->is_active ?? true),
        ])

        @if(!empty($page->slug))
            <a href="{{ route('store.pages.show', $page->slug) }}" target="_blank" class="btn-secondary inline-flex">معاينة الصفحة</a>
        @endif

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">حفظ</button>
            <a href="{{ route('admin.pages.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </div>
</div>
