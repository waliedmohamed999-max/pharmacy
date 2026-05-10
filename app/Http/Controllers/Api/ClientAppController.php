<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FinanceContact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientAppController extends Controller
{
    public function home(InventoryService $inventory): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        $featured = $this->productsQuery()->where('featured', true)->latest()->limit(12)->get();
        $deals = $this->productsQuery()
            ->whereNotNull('compare_price')
            ->whereColumn('compare_price', '>', 'price')
            ->latest()
            ->limit(12)
            ->get();
        $newArrivals = $this->productsQuery()->latest()->limit(12)->get();

        return response()->json([
            'store' => [
                'name' => 'صيدلية د. محمد رمضان',
                'tagline' => 'رعاية موثوقة وتسوق أسرع',
                'currency' => 'ج.م',
                'support_phone' => '0509095816',
                'free_shipping_from' => 500,
            ],
            'banners' => Banner::activeForToday()->orderBy('sort_order')->get()->map(fn (Banner $banner) => [
                'id' => (int) $banner->id,
                'title' => (string) $banner->title,
                'subtitle' => (string) $banner->subtitle,
                'image' => (string) $banner->resolved_image,
                'url' => (string) $banner->resolved_url,
            ])->values(),
            'categories' => $categories->map(fn (Category $category) => $this->categoryPayload($category))->values(),
            'sections' => [
                'featured' => $this->productsPayload($featured, $inventory),
                'deals' => $this->productsPayload($deals, $inventory),
                'new_arrivals' => $this->productsPayload($newArrivals, $inventory),
                'best_sellers' => $this->productsPayload($this->bestSellers(12), $inventory),
                'concerns' => $this->healthConcerns(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (Category $category) => $this->categoryPayload($category))->values(),
        ]);
    }

    public function products(Request $request, InventoryService $inventory): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:price_asc,price_desc,name_asc,newest,latest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->productsQuery()->with('category:id,name,name_ar,name_en,slug');

        if (!empty($data['category_id'])) {
            $products->where('category_id', (int) $data['category_id']);
        }

        $search = trim((string) ($data['q'] ?? ''));
        if ($search !== '') {
            $escapedSearch = $this->escapeLike($search);
            $products->where(function (Builder $query) use ($escapedSearch): void {
                $query->where('name', 'like', "%{$escapedSearch}%")
                    ->orWhere('sku', 'like', "%{$escapedSearch}%")
                    ->orWhere('barcode', 'like', "%{$escapedSearch}%");
            });
        }

        match ($data['sort'] ?? 'newest') {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'name_asc' => $products->orderBy('name'),
            'newest', 'latest' => $products->latest(),
            default => $products->latest(),
        };

        $paginated = $products->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'data' => $this->productsPayload($paginated->getCollection(), $inventory),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function product(Product $product, InventoryService $inventory): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['category:id,name,name_ar,name_en,slug', 'images']);
        $payload = $this->productPayload($product, $inventory);
        $payload['description'] = (string) ($product->description ?: $product->short_description);
        $payload['images'] = collect([$product->image_url])
            ->merge($product->images->map(fn ($image) => asset('storage/' . $image->path)))
            ->filter()
            ->unique()
            ->values();

        return response()->json(['data' => $payload]);
    }

    public function storeOrder(Request $request, InventoryService $inventory, AccountingService $accounting): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $warehouseId = $inventory->defaultStorefrontWarehouseId();
        if (!$warehouseId) {
            throw ValidationException::withMessages(['items' => 'لا يوجد مخزن افتراضي للمتجر.']);
        }

        $order = DB::transaction(function () use ($data, $warehouseId, $inventory, $accounting) {
            $rows = collect($data['items'])
                ->groupBy('product_id')
                ->map(fn ($items, $productId) => [
                    'product_id' => (int) $productId,
                    'qty' => (int) collect($items)->sum('qty'),
                ])
                ->values();

            $products = Product::query()
                ->whereIn('id', $rows->pluck('product_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            foreach ($rows as $row) {
                $product = $products->get($row['product_id']);
                if (!$product) {
                    throw ValidationException::withMessages(['items' => 'منتج غير متاح داخل الطلب.']);
                }

                $available = (float) (ProductStock::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->value('qty') ?? $product->quantity ?? 0);

                if ($available < $row['qty']) {
                    throw ValidationException::withMessages(['items' => "الكمية غير كافية للمنتج: {$product->name}"]);
                }

                $subtotal += ((float) $product->price * (int) $row['qty']);
            }

            $customer = Customer::query()->updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['customer_name'],
                    'email' => null,
                    'city' => $data['city'] ?? '',
                    'address' => $data['address'] ?? '',
                    'is_active' => true,
                ]
            );

            $financeContact = FinanceContact::query()->updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'type' => 'customer',
                    'name' => $data['customer_name'],
                    'city' => $data['city'] ?? '',
                    'address' => $data['address'] ?? '',
                    'is_active' => true,
                ]
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'city' => $data['city'] ?? '',
                'address' => $data['address'] ?? '',
                'notes' => $data['notes'] ?? '',
                'status' => 'new',
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping' => 0,
                'total' => $subtotal,
            ]);

            $salesInvoice = SalesInvoice::create([
                'number' => $accounting->nextNumber('sales_invoices', 'number', 'SI-'),
                'contact_id' => $financeContact->id,
                'warehouse_id' => $warehouseId,
                'invoice_date' => now()->toDateString(),
                'due_date' => null,
                'status' => 'posted',
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'paid_amount' => 0,
                'balance' => $subtotal,
                'notes' => 'فاتورة تطبيق الموبايل من الطلب #' . $order->id,
            ]);

            $totalCost = 0.0;
            foreach ($rows as $row) {
                $product = $products->get($row['product_id']);
                $qty = (int) $row['qty'];
                $lineTotal = (float) $product->price * $qty;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'price' => $product->price,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ]);

                $salesInvoice->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => $qty,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                $unitCost = $inventory->issue(
                    $warehouseId,
                    (int) $product->id,
                    (float) $qty,
                    now()->toDateString(),
                    'mobile_order',
                    (int) $order->id,
                    'صرف طلب تطبيق موبايل',
                    null
                );

                $totalCost += ($qty * (float) $unitCost);
            }

            $accounting->postSalesInvoice($salesInvoice, null);
            $accounting->postSalesCost($salesInvoice->id, $salesInvoice->number, (string) $salesInvoice->invoice_date, $totalCost, (int) $salesInvoice->contact_id, null);

            return $order->load('items');
        }, 3);

        return response()->json(['data' => $this->orderPayload($order)], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
        ]);

        $phone = trim((string) $request->input('phone'));
        abort_if($phone === '', 422, 'رقم الجوال مطلوب.');

        $orders = Order::query()
            ->where('phone', $phone)
            ->with('items')
            ->latest()
            ->limit(30)
            ->get();

        return response()->json(['data' => $orders->map(fn (Order $order) => $this->orderPayload($order))->values()]);
    }

    public function order(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
        ]);

        abort_unless($order->phone === (string) $request->input('phone'), 403);

        return response()->json(['data' => $this->orderPayload($order->load('items'))]);
    }

    private function productsQuery(): Builder
    {
        return Product::query()->where('is_active', true);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function bestSellers(int $limit)
    {
        $ids = OrderItem::query()
            ->selectRaw('product_id, SUM(qty) as qty_sum')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('qty_sum')
            ->limit($limit)
            ->pluck('product_id');

        if ($ids->isEmpty()) {
            return $this->productsQuery()->latest()->limit($limit)->get();
        }

        $products = $this->productsQuery()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $products->get($id))->filter()->values();
    }

    private function productsPayload($products, InventoryService $inventory): array
    {
        return collect($products)->map(fn (Product $product) => $this->productPayload($product, $inventory))->values()->all();
    }

    private function productPayload(Product $product, InventoryService $inventory): array
    {
        $available = $this->availableQty($product, $inventory);

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'sku' => (string) ($product->sku ?? ''),
            'barcode' => (string) ($product->barcode ?? ''),
            'short_description' => (string) ($product->short_description ?? ''),
            'price' => (float) $product->price,
            'compare_price' => $product->compare_price ? (float) $product->compare_price : null,
            'discount_percent' => (int) $product->discount_percent,
            'image' => (string) $product->image_url,
            'available_qty' => $available,
            'in_stock' => $available > 0,
            'category' => $product->category ? [
                'id' => (int) $product->category->id,
                'name' => (string) $product->category->display_name,
                'slug' => (string) $product->category->slug,
            ] : null,
        ];
    }

    private function categoryPayload(Category $category): array
    {
        $image = $category->image
            ? (str_starts_with($category->image, 'images/') ? asset($category->image) : asset('storage/' . $category->image))
            : asset('images/categories/pharmacy-products.svg');

        return [
            'id' => (int) $category->id,
            'name' => (string) $category->display_name,
            'slug' => (string) $category->slug,
            'image' => $image,
            'products_count' => (int) ($category->products_count ?? 0),
        ];
    }

    private function availableQty(Product $product, InventoryService $inventory): int
    {
        $warehouseId = $inventory->defaultStorefrontWarehouseId();
        if ($warehouseId) {
            $stock = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->value('qty');

            if ($stock !== null) {
                return (int) max(0, (float) $stock);
            }
        }

        return (int) max(0, (float) ($product->quantity ?? 0));
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => (int) $order->id,
            'customer_name' => (string) $order->customer_name,
            'phone' => (string) $order->phone,
            'status' => (string) $order->status,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'shipping' => (float) $order->shipping,
            'total' => (float) $order->total,
            'created_at' => optional($order->created_at)->toDateTimeString(),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'product_id' => (int) $item->product_id,
                'name' => (string) $item->product_name_snapshot,
                'price' => (float) $item->price,
                'qty' => (int) $item->qty,
                'line_total' => (float) $item->line_total,
            ])->values(),
        ];
    }

    private function healthConcerns(): array
    {
        return [
            ['title' => 'المناعة', 'subtitle' => 'دعم يومي لصحة أقوى', 'icon' => 'shield'],
            ['title' => 'السكري', 'subtitle' => 'قياس ومتابعة ومنتجات أساسية', 'icon' => 'monitor'],
            ['title' => 'ضغط الدم', 'subtitle' => 'أجهزة ومنتجات متابعة منزلية', 'icon' => 'heart'],
            ['title' => 'العناية بالبشرة', 'subtitle' => 'حلول طبية لبشرة صحية', 'icon' => 'sparkles'],
            ['title' => 'النوم', 'subtitle' => 'روتين هادئ ومكملات مساعدة', 'icon' => 'moon'],
            ['title' => 'الوزن الصحي', 'subtitle' => 'مكملات ومتابعة نمط حياة', 'icon' => 'activity'],
        ];
    }
}
