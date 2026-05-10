@extends('admin.layouts.app')

@section('page-title', 'الصفحات')
@section('page-subtitle', 'إدارة صفحات المحتوى الثابت مثل: من نحن، سياسة الخصوصية، الشروط')

@section('page-actions')
<a href="{{ route('admin.pages.create') }}" class="btn-primary">إضافة صفحة</a>
@endsection

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-4 gap-3">
    <input name="search" class="input-premium" placeholder="بحث بالعنوان أو الرابط المختصر" value="{{ request('search') }}">
    <div></div>
    <div></div>
    <button class="btn-primary">تطبيق</button>
</form>

<div class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>العنوان</th>
                <th>Slug</th>
                <th>الترتيب</th>
                <th>الحالة</th>
                <th>الرابط</th>
                <th>الإجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="font-semibold">{{ $page->title }}</td>
                    <td><code>{{ $page->slug }}</code></td>
                    <td>{{ $page->sort_order }}</td>
                    <td><span class="{{ $page->is_active ? 'badge-success' : 'badge-danger' }}">{{ $page->is_active ? 'نشطة' : 'مخفية' }}</span></td>
                    <td><a href="{{ route('store.pages.show', $page->slug) }}" target="_blank" class="text-sky-700 hover:underline">فتح الصفحة</a></td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn-secondary">تعديل</a>
                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد صفحات حتى الآن.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pages->links() }}</div>
</div>
@endsection
