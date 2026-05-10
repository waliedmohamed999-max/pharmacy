@extends('admin.layouts.app')

@section('page-title', 'المنتجات')
@section('page-subtitle', 'إدارة الكتالوج والأسعار والمخزون')

@section('page-actions')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.products.trash') }}" class="btn-secondary">المحذوفات</a>
    <a href="{{ route('admin.products.labels') }}" class="btn-secondary">طباعة ملصقات</a>
    <a href="{{ route('admin.products.export') }}" class="btn-secondary">تصدير CSV</a>
    <form method="POST" action="{{ route('admin.products.refresh-real-images') }}">
        @csrf
        <button class="btn-secondary">تحديث صور المنتجات</button>
    </form>
    <form method="POST" action="{{ route('admin.products.destroy-all') }}" data-confirm-delete>
        @csrf
        @method('DELETE')
        <button class="btn-danger">مسح كل الأدوية</button>
    </form>
    <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
        @csrf
        <input type="file" name="file" accept=".xls,.xlsx,.csv,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="input-premium !py-2 !px-2 w-64" required>
        <button class="btn-primary">استيراد Excel</button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="btn-primary">إضافة منتج</a>
</div>
@endsection

@section('content')
<form class="card-premium p-4 mb-4 grid md:grid-cols-4 gap-3">
    <input name="search" class="input-premium" placeholder="بحث بالاسم أو SKU أو الباركود" value="{{ request('search') }}">
    <select name="category_id" class="select-premium">
        <option value="">كل التصنيفات</option>
        @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->display_name }}</option>
        @endforeach
    </select>
    <button class="btn-primary">تطبيق</button>
</form>

<div class="card-premium p-4">
    <form method="POST" action="{{ route('admin.products.bulk-destroy') }}" data-confirm-delete id="bulkProductsForm">
        @csrf
        @method('DELETE')
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm font-bold text-slate-500">
                حدد المنتجات المطلوبة ثم اضغط حذف المحدد. المنتجات تنتقل إلى المحذوفات ويمكن استرجاعها.
            </div>
            <div class="flex items-center gap-2">
                <span id="selectedProductsCount" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">0 محدد</span>
                <button class="btn-danger" id="bulkDeleteBtn" disabled>حذف المحدد</button>
            </div>
        </div>

        <div class="table-wrap">
        <table class="table-premium">
            <thead>
            <tr>
                <th class="w-10">
                    <input type="checkbox" id="selectAllProducts" class="h-4 w-4 rounded border-slate-300 text-emerald-600">
                </th>
                <th>المنتج</th>
                <th>التصنيف</th>
                <th>SKU</th>
                <th>الباركود</th>
                <th>السعر</th>
                <th>المخزون</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox h-4 w-4 rounded border-slate-300 text-emerald-600">
                    </td>
                    <td class="font-semibold">{{ $product->name }}</td>
                    <td>{{ $product->category?->display_name }}</td>
                    <td>{{ $product->sku ?: '-' }}</td>
                    <td>{{ $product->barcode ?: '-' }}</td>
                    <td>{{ number_format((float) $product->price, 2) }}</td>
                    <td>{{ $product->quantity }}</td>
                    <td>
                        <div class="flex gap-1">
                            <span class="{{ $product->is_active ? 'badge-success' : 'badge-danger' }}">{{ $product->is_active ? 'نشط' : 'غير نشط' }}</span>
                            @if($product->featured)
                                <span class="badge-warning">مميز</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.products.barcode', $product) }}" target="_blank" class="btn-secondary">باركود</a>
                            <a href="{{ route('admin.finance.index', ['product_id' => $product->id]) }}" class="btn-secondary">المالية</a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-secondary">تعديل</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm-delete>
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty-state">لا توجد منتجات مطابقة.</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </form>

    <div class="mt-4">{{ $products->links() }}</div>
</div>

<script>
(() => {
    const selectAll = document.getElementById('selectAllProducts');
    const checkboxes = [...document.querySelectorAll('.product-checkbox')];
    const selectedCount = document.getElementById('selectedProductsCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    const refreshBulkState = () => {
        const count = checkboxes.filter((checkbox) => checkbox.checked).length;
        selectedCount.textContent = `${count} محدد`;
        bulkDeleteBtn.disabled = count === 0;
        bulkDeleteBtn.classList.toggle('opacity-50', count === 0);
        bulkDeleteBtn.classList.toggle('cursor-not-allowed', count === 0);

        if (selectAll) {
            selectAll.checked = count > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        refreshBulkState();
    });

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshBulkState));
    refreshBulkState();
})();
</script>
@endsection
