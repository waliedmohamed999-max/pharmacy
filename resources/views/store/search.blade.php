@extends('store.layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">نتائج البحث: {{ $q ?: 'الكل' }}</h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach($products as $product)
        @include('store.components.product-card', ['product' => $product])
    @endforeach
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
