<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'ordersToday' => Order::whereDate('created_at', today())->count(),
            'ordersCount' => Order::count(),
            'salesTotal' => Order::sum('total'),
            'productsCount' => Product::count(),
            'lowStockCount' => Product::where('quantity', '<=', 5)->count(),
            'lowStockProducts' => Product::where('quantity', '<=', 5)->orderBy('quantity')->take(8)->get(),
            'latestOrders' => Order::latest()->take(8)->get(),
            'pagesCount' => Page::count(),
            'bannersCount' => Banner::count(),
            'banners' => Banner::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('id')->take(100)->get(),
            'homeAfterNewBannerIds' => $this->getHomeAfterNewBannerIds(),
            'monthlyStats' => $this->monthlyStats(),
            'topProducts' => $this->topProducts(),
        ]);
    }

    public function updateHomeSettings(Request $request)
    {
        $data = $request->validate([
            'home_after_new_banner_ids' => ['nullable', 'array'],
            'home_after_new_banner_ids.*' => ['integer', 'exists:banners,id'],
        ]);

        $ids = collect($data['home_after_new_banner_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        StoreSetting::setValue('home_after_new_banner_ids', json_encode($ids));

        return back()->with('success', 'تم تحديث إعدادات واجهة المتجر من لوحة التحكم.');
    }

    private function getHomeAfterNewBannerIds(): array
    {
        $raw = StoreSetting::getValue('home_after_new_banner_ids');
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return collect($decoded)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        }

        $legacy = (int) (StoreSetting::getValue('home_after_new_banner_id', '0') ?: 0);
        return $legacy > 0 ? [$legacy] : [];
    }

    private function monthlyStats()
    {
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        return Order::query()
            ->selectRaw($monthExpression . ' as month_key')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(total) as revenue')
            ->groupBy('month_key')
            ->orderByDesc('month_key')
            ->limit(8)
            ->get()
            ->reverse()
            ->values();
    }

    private function topProducts()
    {
        return Product::query()
            ->select('id', 'name', 'sku', 'price', 'quantity', 'primary_image', 'featured')
            ->latest()
            ->take(10)
            ->get();
    }
}
