@extends('admin.layouts.app')

@section('page-title', 'التصنيفات')
@section('page-subtitle', 'إدارة الأقسام الرئيسية والفرعية وصورها')

@section('page-actions')
<a href="{{ route('admin.categories.create') }}" class="btn-primary">إضافة تصنيف</a>
@endsection

@section('content')
<div class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الصورة</th>
                <th>الاسم</th>
                <th>الأب</th>
                <th>الترتيب</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                @php
                    $img = $category->image
                        ? (str_starts_with($category->image, 'images/') ? asset($category->image) : asset('storage/'.$category->image))
                        : asset('images/placeholder.png');
                @endphp
                <tr>
                    <td><img src="{{ $img }}" class="w-11 h-11 rounded-full object-cover"></td>
                    <td>{{ $category->display_name }}</td>
                    <td>{{ $category->parent?->display_name ?? '-' }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td>
                        <span class="{{ $category->is_active ? 'badge-success' : 'badge-danger' }}">{{ $category->is_active ? 'نشط' : 'غير نشط' }}</span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn-secondary">تعديل</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">لا توجد تصنيفات حتى الآن.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
</div>
@endsection
