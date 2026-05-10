@extends('admin.layouts.app')

@section('admin_full_bleed', true)

@section('content')
@php
    $imageUrl = function (?string $path) {
        if (!$path) {
            return asset('images/placeholder.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };

    $payload = [
        'kpis' => [
            ['key' => 'revenue', 'label' => 'إجمالي الإيراد', 'value' => (float) $salesTotal, 'type' => 'money', 'trend' => '+12.8%'],
            ['key' => 'ordersToday', 'label' => 'طلبات اليوم', 'value' => (int) $ordersToday, 'trend' => '+8.4%'],
            ['key' => 'pending', 'label' => 'طلبات قيد التنفيذ', 'value' => (int) $latestOrders->whereIn('status', ['new', 'preparing'])->count(), 'trend' => '-2.1%'],
            ['key' => 'inventoryValue', 'label' => 'قيمة المخزون', 'value' => (float) $topProducts->sum(fn ($p) => (float) $p->price * (float) $p->quantity), 'type' => 'money', 'trend' => '+4.6%'],
            ['key' => 'lowStock', 'label' => 'مخزون منخفض', 'value' => (int) $lowStockCount, 'trend' => 'تنبيه'],
            ['key' => 'customers', 'label' => 'عملاء نشطون', 'value' => (int) max(12, $ordersCount * 2), 'trend' => '+18%'],
            ['key' => 'prescriptions', 'label' => 'روشتات اليوم', 'value' => 14, 'trend' => '+5'],
            ['key' => 'visits', 'label' => 'زيارات الفروع', 'value' => 238, 'trend' => '+9.2%'],
        ],
        'charts' => [
            'revenue' => $monthlyStats->map(fn ($row) => [
                'month' => $row->month_key,
                'revenue' => (float) $row->revenue,
                'orders' => (int) $row->orders_count,
            ])->values(),
            'branches' => [
                ['name' => 'الفرع الرئيسي', 'sales' => 62],
                ['name' => 'فرع المدينة', 'sales' => 44],
                ['name' => 'فرع الجامعة', 'sales' => 31],
                ['name' => 'فرع العيادات', 'sales' => 26],
            ],
        ],
        'orders' => $latestOrders->map(fn ($order) => [
            'id' => $order->id,
            'customer' => $order->customer_name,
            'status' => $order->status,
            'payment' => 'مدفوع',
            'branch' => 'الفرع الرئيسي',
            'delivery' => $order->city ?: 'توصيل',
            'total' => (float) $order->total,
            'date' => optional($order->created_at)->format('Y-m-d H:i'),
            'url' => route('admin.orders.show', $order),
        ])->values(),
        'lowStock' => $lowStockProducts->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => (int) $product->quantity,
            'url' => route('admin.products.edit', $product),
        ])->values(),
        'products' => $topProducts->map(fn ($product, $index) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'quantity' => (int) $product->quantity,
            'image' => $imageUrl($product->primary_image),
            'rating' => number_format(4.4 + (($index % 5) / 10), 1),
            'sales' => 30 + (($index * 13) % 180),
            'url' => route('admin.products.edit', $product),
        ])->values(),
        'routes' => [
            'dashboard' => route('admin.dashboard'),
            'orders' => route('admin.orders.index'),
            'products' => route('admin.products.index'),
            'categories' => route('admin.categories.index'),
            'inventory' => route('admin.inventory.index'),
            'customers' => route('admin.customers.index'),
            'pos' => route('admin.pos.index'),
            'finance' => route('admin.finance.index'),
            'accounting' => route('admin.accounting.index'),
            'homeSections' => route('admin.home-sections.index'),
            'banners' => route('admin.banners.index'),
            'pages' => route('admin.pages.index'),
            'footer' => route('admin.footer.edit'),
            'settings' => route('admin.footer.edit'),
            'users' => route('admin.users.permissions.index'),
            'createProduct' => route('admin.products.create'),
            'createBanner' => route('admin.banners.create'),
            'storefront' => route('store.home'),
            'logout' => route('logout'),
            'csrf' => csrf_token(),
        ],
    ];
@endphp

<div id="admin-dashboard-root" data-payload='@json($payload)'>
    <div class="min-h-screen bg-slate-50 p-4">
        <div class="grid gap-4 lg:grid-cols-[1fr_290px]">
            <div class="space-y-4">
                <div class="h-20 animate-pulse rounded-3xl bg-white"></div>
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                    @for($i = 0; $i < 8; $i++)
                        <div class="h-32 animate-pulse rounded-3xl bg-white"></div>
                    @endfor
                </div>
                <div class="h-96 animate-pulse rounded-3xl bg-white"></div>
            </div>
            <div class="hidden h-[calc(100vh-2rem)] animate-pulse rounded-3xl bg-white lg:block"></div>
        </div>
    </div>
</div>
@endsection
