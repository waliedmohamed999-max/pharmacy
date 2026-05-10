@extends('admin.layouts.app')

@section('page-title', 'محذوفات المنتجات')
@section('page-subtitle', 'استرجاع أو حذف نهائي للعناصر المحذوفة')

@section('content')
<div class="card-premium p-4">
    <div class="table-wrap">
        <table class="table-premium">
            <thead><tr><th>المنتج</th><th>تاريخ الحذف</th><th>إجراءات</th></tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->deleted_at }}</td>
                    <td class="flex gap-2">
                        <form method="POST" action="{{ route('admin.products.restore', $product->id) }}">
                            @csrf @method('PATCH')
                            <button class="btn-secondary">استرجاع</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.forceDelete', $product->id) }}" data-confirm-delete>
                            @csrf @method('DELETE')
                            <button class="btn-danger">حذف نهائي</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="empty-state">لا توجد منتجات محذوفة.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
