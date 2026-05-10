@extends('admin.layouts.app')

@section('page-title', 'البنرات')
@section('page-subtitle', 'إدارة سلايدر الصفحة الرئيسية وروابطه')

@section('page-actions')
<a href="{{ route('admin.banners.create') }}" class="btn-primary">إضافة بنر</a>
@endsection

@section('content')
<div class="card-premium p-4 mb-4">
    <form method="POST" action="{{ route('admin.banners.autoplay') }}" class="flex flex-wrap items-center gap-3">
        @csrf
        @method('PATCH')
        @include('admin.components.toggle', [
            'name' => 'home_banner_autoplay',
            'label' => 'تشغيل تلقائي لسلايدر البنرات',
            'checked' => $bannerAutoplay,
        ])
        <button class="btn-primary">حفظ الإعداد</button>
    </form>
</div>

<div class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th>الصورة</th>
                <th>العنوان</th>
                <th>نوع الرابط</th>
                <th>القيمة</th>
                <th>الفترة</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($banners as $banner)
                <tr>
                    <td><img src="{{ $banner->resolved_image }}" class="w-20 h-12 rounded-lg object-cover"></td>
                    <td>{{ $banner->title }}</td>
                    <td>{{ $banner->link_type }}</td>
                    <td>{{ $banner->link_target ?: '—' }}</td>
                    <td>{{ optional($banner->start_date)->format('Y-m-d') ?: '—' }} / {{ optional($banner->end_date)->format('Y-m-d') ?: '—' }}</td>
                    <td><span class="{{ $banner->is_active ? 'badge-success' : 'badge-danger' }}">{{ $banner->is_active ? 'نشط' : 'غير نشط' }}</span></td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn-secondary">تعديل</a>
                            <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">لا توجد بنرات حتى الآن.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $banners->links() }}</div>
</div>
@endsection
