@extends('admin.layouts.app')

@section('page-title', 'إعدادات الفوتر')
@section('page-subtitle', 'التحكم الكامل في شكل ومحتوى الفوتر أسفل الموقع')

@section('content')
<form method="POST" action="{{ route('admin.footer.update') }}" class="space-y-5">
    @csrf
    @method('PUT')

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 card-premium p-4 space-y-4">
            @include('admin.components.input', [
                'name' => 'footer_brand_title',
                'label' => 'عنوان العلامة في الفوتر',
                'value' => old('footer_brand_title', $settings['footer_brand_title'] ?? ''),
            ])

            @include('admin.components.textarea', [
                'name' => 'footer_about',
                'label' => 'وصف قصير في الفوتر',
                'rows' => 3,
                'value' => old('footer_about', $settings['footer_about'] ?? ''),
                'hint' => 'مثال: خدمة دوائية موثوقة وسريعة لجميع أفراد الأسرة.',
            ])

            @include('admin.components.input', [
                'name' => 'footer_copyright',
                'label' => 'نص الحقوق',
                'value' => old('footer_copyright', $settings['footer_copyright'] ?? ''),
                'hint' => 'مثال: © 2026 صيدلية د. محمد رمضان',
            ])

            <div class="grid md:grid-cols-2 gap-4">
                @include('admin.components.input', [
                    'name' => 'footer_links_title',
                    'label' => 'عنوان عمود الروابط',
                    'value' => old('footer_links_title', $settings['footer_links_title'] ?? ''),
                ])

                @include('admin.components.input', [
                    'name' => 'footer_newsletter_title',
                    'label' => 'عنوان عمود النشرة',
                    'value' => old('footer_newsletter_title', $settings['footer_newsletter_title'] ?? ''),
                ])
            </div>

            @include('admin.components.textarea', [
                'name' => 'footer_newsletter_text',
                'label' => 'وصف النشرة الإخبارية',
                'rows' => 2,
                'value' => old('footer_newsletter_text', $settings['footer_newsletter_text'] ?? ''),
            ])

            <div class="grid md:grid-cols-2 gap-4">
                @include('admin.components.input', [
                    'name' => 'footer_contact_title',
                    'label' => 'عنوان عمود التواصل',
                    'value' => old('footer_contact_title', $settings['footer_contact_title'] ?? ''),
                ])

                @include('admin.components.input', [
                    'name' => 'footer_contact_phone',
                    'label' => 'رقم الهاتف',
                    'value' => old('footer_contact_phone', $settings['footer_contact_phone'] ?? ''),
                ])
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                @include('admin.components.input', [
                    'name' => 'footer_contact_email',
                    'label' => 'البريد الإلكتروني',
                    'type' => 'email',
                    'value' => old('footer_contact_email', $settings['footer_contact_email'] ?? ''),
                ])

                @include('admin.components.input', [
                    'name' => 'footer_contact_address',
                    'label' => 'العنوان',
                    'value' => old('footer_contact_address', $settings['footer_contact_address'] ?? ''),
                ])
            </div>
        </div>

        <div class="card-premium p-4 space-y-4 h-fit">
            @include('admin.components.toggle', [
                'name' => 'footer_enabled',
                'label' => 'تفعيل الفوتر',
                'checked' => old('footer_enabled', $settings['footer_enabled'] ?? true),
            ])

            @include('admin.components.toggle', [
                'name' => 'footer_show_pages',
                'label' => 'إظهار روابط الصفحات',
                'checked' => old('footer_show_pages', $settings['footer_show_pages'] ?? true),
            ])

            @include('admin.components.toggle', [
                'name' => 'footer_newsletter_enabled',
                'label' => 'إظهار عمود النشرة',
                'checked' => old('footer_newsletter_enabled', $settings['footer_newsletter_enabled'] ?? true),
            ])

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">حفظ الإعدادات</button>
            </div>
        </div>
    </div>

    <div class="card-premium p-4 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-black">روابط مخصصة في الفوتر</h3>
            <button type="button" class="btn-secondary" id="addFooterLink">إضافة رابط</button>
        </div>

        <div id="footerLinksList" class="space-y-3">
            @php
                $oldLinks = old('links');
                $links = is_array($oldLinks) ? $oldLinks : ($settings['links'] ?? []);
            @endphp

            @forelse($links as $index => $link)
                <div class="grid md:grid-cols-[1fr_2fr_auto] gap-3 footer-link-row">
                    <input class="input-premium" name="links[{{ $index }}][label]" placeholder="اسم الرابط" value="{{ $link['label'] ?? '' }}">
                    <input class="input-premium" name="links[{{ $index }}][url]" placeholder="https://example.com" value="{{ $link['url'] ?? '' }}">
                    <button type="button" class="btn-danger remove-footer-link">حذف</button>
                </div>
            @empty
                <div class="grid md:grid-cols-[1fr_2fr_auto] gap-3 footer-link-row">
                    <input class="input-premium" name="links[0][label]" placeholder="اسم الرابط">
                    <input class="input-premium" name="links[0][url]" placeholder="https://example.com">
                    <button type="button" class="btn-danger remove-footer-link">حذف</button>
                </div>
            @endforelse
        </div>

        @error('links')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</form>

<template id="footerLinkTemplate">
    <div class="grid md:grid-cols-[1fr_2fr_auto] gap-3 footer-link-row">
        <input class="input-premium" placeholder="اسم الرابط" data-name="label">
        <input class="input-premium" placeholder="https://example.com" data-name="url">
        <button type="button" class="btn-danger remove-footer-link">حذف</button>
    </div>
</template>

<script>
(() => {
    const list = document.getElementById('footerLinksList');
    const addBtn = document.getElementById('addFooterLink');
    const tpl = document.getElementById('footerLinkTemplate');
    if (!list || !addBtn || !tpl) return;

    const reindex = () => {
        [...list.querySelectorAll('.footer-link-row')].forEach((row, i) => {
            const label = row.querySelector('[data-name="label"], input[name*="[label]"]');
            const url = row.querySelector('[data-name="url"], input[name*="[url]"]');
            if (label) label.name = `links[${i}][label]`;
            if (url) url.name = `links[${i}][url]`;
            label?.removeAttribute('data-name');
            url?.removeAttribute('data-name');
        });
    };

    const wireRemove = (context) => {
        context.querySelectorAll('.remove-footer-link').forEach((btn) => {
            btn.onclick = () => {
                const rows = list.querySelectorAll('.footer-link-row');
                if (rows.length <= 1) {
                    const first = rows[0];
                    first?.querySelectorAll('input').forEach((input) => input.value = '');
                    return;
                }
                btn.closest('.footer-link-row')?.remove();
                reindex();
            };
        });
    };

    addBtn.addEventListener('click', () => {
        const node = tpl.content.firstElementChild.cloneNode(true);
        list.appendChild(node);
        reindex();
        wireRemove(node);
    });

    wireRemove(list);
    reindex();
})();
</script>
@endsection
