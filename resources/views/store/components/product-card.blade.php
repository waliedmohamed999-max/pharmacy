@props(['product'])

@php
    if (!$product->price) {
        return;
    }

    $discount = (int) $product->discount_percent;
    $isNew = optional($product->created_at)?->gt(now()->subDays(10));
    $availableQty = (int) max(0, (float) ($product->available_qty ?? $product->quantity ?? 0));
    $rating = number_format(4.4 + (($product->id % 5) / 10), 1);
    $reviews = 18 + (($product->id * 11) % 140);
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-2xl hover:shadow-emerald-950/10">
    <a href="{{ route('store.product.show', $product->slug) }}" class="relative grid h-48 place-items-center overflow-hidden rounded-[1.25rem] bg-gradient-to-br from-slate-50 via-white to-emerald-50">
        @if($discount > 0)
            <span class="absolute right-3 top-3 z-10 rounded-full bg-rose-600 px-2.5 py-1 text-xs font-black text-white shadow-lg shadow-rose-600/20">خصم {{ $discount }}%</span>
        @elseif($isNew)
            <span class="absolute right-3 top-3 z-10 rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-black text-white shadow-lg shadow-emerald-600/20">جديد</span>
        @endif

        <span class="absolute left-3 top-3 rounded-full {{ $availableQty > 0 ? 'bg-white/90 text-emerald-700' : 'bg-white/90 text-rose-700' }} px-2.5 py-1 text-xs font-black ring-1 ring-slate-200">
            {{ $availableQty > 0 ? 'متوفر' : 'نافد' }}
        </span>

        <img src="{{ $product->image_url }}" class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-105" alt="{{ $product->name }}">
    </a>

    <div class="flex flex-1 flex-col px-1 pb-1 pt-3">
        <a href="{{ route('store.product.show', $product->slug) }}" class="line-clamp-2 min-h-12 text-sm font-black leading-6 text-slate-950 transition hover:text-emerald-700 md:text-base">
            {{ $product->name }}
        </a>

        <div class="mt-2 flex items-center gap-2 text-xs font-bold">
            <span class="text-amber-400">★★★★★</span>
            <span class="text-slate-700">{{ $rating }}</span>
            <span class="text-slate-400">({{ $reviews }})</span>
        </div>

        <div class="mt-3 flex flex-wrap items-end gap-2">
            <span class="text-xl font-black text-emerald-700">{{ number_format($product->price, 2) }} ج.م</span>
            @if($product->compare_price && $product->compare_price > $product->price)
                <span class="pb-0.5 text-sm font-bold text-slate-400 line-through">{{ number_format($product->compare_price, 2) }} ج.م</span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
            @if($product->sku)
                <span class="rounded-full bg-slate-100 px-2.5 py-1">SKU: {{ $product->sku }}</span>
            @endif
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">المخزون: {{ $availableQty }}</span>
        </div>

        <form action="{{ route('store.cart.add') }}" method="POST" class="mt-auto pt-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" value="1">
            <button class="flex h-11 w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none" type="submit" @disabled($availableQty < 1)>
                إضافة للسلة
            </button>
        </form>
    </div>
</article>
