@props(['category'])

@php
    $image = $category->image
        ? (str_starts_with($category->image, 'images/') ? asset($category->image) : asset('storage/' . $category->image))
        : asset('images/placeholder.png');
@endphp

<a href="{{ route('store.category.show', $category->slug) }}" class="group flex flex-col items-center gap-2 min-w-[96px]">
    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden neo-card transition duration-300 group-hover:scale-105">
        <img src="{{ $image }}" alt="{{ $category->display_name }}" class="w-full h-full object-cover">
    </div>
    <div class="text-sm text-center font-semibold line-clamp-2">{{ $category->display_name }}</div>
    <div class="text-xs text-gray-500">{{ $category->products_count ?? 0 }} منتج</div>
</a>
