@extends('admin.layouts.app')

@section('page-title', 'المخازن')
@section('page-subtitle', 'إدارة بيانات المخازن النشطة')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <section class="card-premium p-4 xl:col-span-2">
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الاسم</th><th>الكود</th><th>الموقع</th><th>الحالة</th></tr></thead>
                <tbody>
                @forelse($warehouses as $w)
                    <tr>
                        <td>{{ $w->name }}</td>
                        <td>{{ $w->code }}</td>
                        <td>{{ $w->location ?: '-' }}</td>
                        <td>{{ $w->is_active ? 'نشط' : 'موقوف' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد مخازن.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $warehouses->links() }}</div>
    </section>

    <section class="card-premium p-4">
        <h2 class="text-lg font-black mb-3">إضافة مخزن</h2>
        <form action="{{ route('admin.inventory.warehouses.store') }}" method="POST" class="space-y-3">
            @csrf
            <div><label class="block text-xs text-slate-500 mb-1">الاسم</label><input name="name" class="input-premium" required></div>
            <div><label class="block text-xs text-slate-500 mb-1">الكود</label><input name="code" class="input-premium" required></div>
            <div><label class="block text-xs text-slate-500 mb-1">الموقع</label><input name="location" class="input-premium"></div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked>نشط</label>
            <button class="btn-primary w-full">حفظ</button>
        </form>
    </section>
</div>
@endsection
