<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportHubController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        $from = $dateFrom . ' 00:00:00';
        $to = $dateTo . ' 23:59:59';

        $days = max(1, now()->parse($dateFrom)->diffInDays(now()->parse($dateTo)) + 1);
        $previousTo = now()->parse($dateFrom)->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $periodOrders = Order::query()->whereBetween('created_at', [$from, $to]);
        $previousOrders = Order::query()->whereBetween('created_at', [$previousFrom, $previousTo]);

        $monthlySales = Order::query()
            ->selectRaw($monthExpression . ' as month_key')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupBy('month_key')
            ->orderByDesc('month_key')
            ->limit(6)
            ->get();

        $summary = [
            'orders' => (clone $periodOrders)->count(),
            'revenue' => (float) (clone $periodOrders)->sum('total'),
            'average_order' => (float) (clone $periodOrders)->avg('total'),
            'cancelled_orders' => (clone $periodOrders)->where('status', 'cancelled')->count(),
            'products' => Product::count(),
            'customers' => Customer::count(),
            'low_stock' => Product::whereColumn('quantity', '<=', 'reorder_level')->orWhere('quantity', '<=', 5)->count(),
            'stock_movements' => StockMovement::query()->whereBetween('created_at', [$from, $to])->count(),
        ];

        $previousSummary = [
            'orders' => (clone $previousOrders)->count(),
            'revenue' => (float) (clone $previousOrders)->sum('total'),
            'average_order' => (float) (clone $previousOrders)->avg('total'),
        ];

        $statusBreakdown = (clone $periodOrders)
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(total), 0) as revenue'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $lowStockProducts = Product::query()
            ->where(function ($query) {
                $query->whereColumn('quantity', '<=', 'reorder_level')
                    ->orWhere('quantity', '<=', 5);
            })
            ->orderBy('quantity')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'quantity', 'reorder_level']);

        $statusLabels = [
            'pending' => 'قيد الانتظار',
            'processing' => 'قيد التجهيز',
            'completed' => 'مكتمل',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
            'paid' => 'مدفوع',
        ];

        $reportGroups = [
            [
                'title' => 'التقارير المالية والمحاسبية',
                'description' => 'قوائم مالية جاهزة للمراجعة: المركز المالي، الدخل، التدفقات، دفتر الأستاذ وميزان المراجعة.',
                'accent' => 'emerald',
                'reports' => [
                    ['label' => 'المركز المالي', 'desc' => 'الأصول والالتزامات وحقوق الملكية', 'route' => route('admin.accounting.reports.balance-sheet'), 'export' => route('admin.accounting.reports.balance-sheet.pdf')],
                    ['label' => 'قائمة الدخل', 'desc' => 'الإيرادات والمصروفات وصافي الربح', 'route' => route('admin.accounting.reports.income-statement'), 'export' => route('admin.accounting.reports.income-statement.pdf')],
                    ['label' => 'التدفقات النقدية', 'desc' => 'حركة التحصيلات والمدفوعات', 'route' => route('admin.accounting.reports.cash-flow'), 'export' => route('admin.accounting.reports.cash-flow.pdf')],
                    ['label' => 'ميزان المراجعة', 'desc' => 'مطابقة إجمالي المدين والدائن', 'route' => route('admin.accounting.reports.trial-balance'), 'export' => route('admin.accounting.reports.trial-balance.pdf')],
                    ['label' => 'دفتر الأستاذ', 'desc' => 'كشف حساب تفصيلي لكل القيود', 'route' => route('admin.accounting.reports.ledger'), 'export' => route('admin.accounting.reports.ledger.pdf')],
                    ['label' => 'المركز التشغيلي المالي', 'desc' => 'الفواتير والربحية والضرائب والذمم', 'route' => route('admin.finance.index')],
                ],
            ],
            [
                'title' => 'تقارير المبيعات والطلبات',
                'description' => 'تحليل الطلبات، نقاط البيع، فواتير المبيعات، العملاء وحركات التحصيل.',
                'accent' => 'blue',
                'reports' => [
                    ['label' => 'كل الطلبات', 'desc' => 'فلترة حالات الطلب والتوصيل والمدفوعات', 'route' => route('admin.orders.index')],
                    ['label' => 'سجل نقاط البيع', 'desc' => 'فواتير POS وإيصالاتها', 'route' => route('admin.pos.history')],
                    ['label' => 'فواتير المبيعات', 'desc' => 'كل فواتير البيع المحاسبية', 'route' => route('admin.accounting.sales.index')],
                    ['label' => 'تصدير الطلبات', 'desc' => 'CSV للفواتير والطلبات', 'route' => route('admin.finance.export', ['type' => 'invoices'])],
                    ['label' => 'تقرير العملاء', 'desc' => 'بيانات العملاء وتاريخ التعامل', 'route' => route('admin.customers.index')],
                    ['label' => 'تحصيل / سداد', 'desc' => 'حركات الدفع والتحصيل', 'route' => route('admin.accounting.payments.index')],
                ],
            ],
            [
                'title' => 'تقارير المخزون والصيدلية',
                'description' => 'الأرصدة، كارت الصنف، حركات المخزون، الجرد، النواقص وتصدير النظرة العامة.',
                'accent' => 'cyan',
                'reports' => [
                    ['label' => 'أرصدة المخزون', 'desc' => 'بحث حسب المخزن أو الصنف', 'route' => route('admin.inventory.stocks')],
                    ['label' => 'حركات المخزون', 'desc' => 'الوارد والصادر والتحويلات', 'route' => route('admin.inventory.movements')],
                    ['label' => 'تنبيهات النواقص', 'desc' => 'أصناف تحت حد الطلب', 'route' => route('admin.inventory.alerts')],
                    ['label' => 'كارت الصنف', 'desc' => 'كشف حركة منتج محدد', 'route' => route('admin.inventory.stock-card')],
                    ['label' => 'جلسات الجرد', 'desc' => 'جرد فعلي واعتماد الفروقات', 'route' => route('admin.inventory.counts.index')],
                    ['label' => 'تصدير المخزون', 'desc' => 'CSV شامل للمخزون', 'route' => route('admin.inventory.export.overview')],
                ],
            ],
            [
                'title' => 'تقارير الكتالوج والواجهة',
                'description' => 'المنتجات، التصنيفات، البنرات، صفحات المتجر وترتيب الصفحة الرئيسية.',
                'accent' => 'slate',
                'reports' => [
                    ['label' => 'تقرير المنتجات', 'desc' => 'أسعار ومخزون وتصدير CSV', 'route' => route('admin.products.index')],
                    ['label' => 'تصدير المنتجات', 'desc' => 'CSV كامل للمنتجات والأدوية', 'route' => route('admin.products.export')],
                    ['label' => 'التصنيفات', 'desc' => 'ربط المنتجات بالأقسام', 'route' => route('admin.categories.index')],
                    ['label' => 'Home Builder', 'desc' => 'ترتيب أقسام الواجهة', 'route' => route('admin.home-sections.index')],
                    ['label' => 'البنرات والتسويق', 'desc' => 'إدارة العروض والسلايدر', 'route' => route('admin.banners.index')],
                    ['label' => 'الصفحات والفوتر', 'desc' => 'محتوى الصفحات وإعدادات الواجهة', 'route' => route('admin.pages.index')],
                ],
            ],
            [
                'title' => 'تقارير الإدارة والرقابة',
                'description' => 'صلاحيات المستخدمين، مؤشرات التشغيل، وروابط المراجعة اليومية.',
                'accent' => 'amber',
                'reports' => [
                    ['label' => 'لوحة التحكم', 'desc' => 'المؤشرات الرئيسية والتحليلات', 'route' => route('admin.dashboard')],
                    ['label' => 'الصلاحيات', 'desc' => 'أدوار الموظفين والوصول للنظام', 'route' => route('admin.users.permissions.index')],
                    ['label' => 'إضافة منتج', 'desc' => 'إدخال صنف جديد مع الباركود والمخزون', 'route' => route('admin.products.create')],
                    ['label' => 'سند استلام', 'desc' => 'تحديث المخزون من الوارد', 'route' => route('admin.inventory.receive.form')],
                    ['label' => 'تسوية مخزون', 'desc' => 'زيادة أو نقص مع أثر محاسبي', 'route' => route('admin.inventory.adjustment.form')],
                    ['label' => 'عرض المتجر', 'desc' => 'مراجعة الواجهة الخارجية', 'route' => route('store.home')],
                ],
            ],
        ];

        return view('admin.reports.index', compact(
            'summary',
            'previousSummary',
            'monthlySales',
            'reportGroups',
            'statusBreakdown',
            'statusLabels',
            'lowStockProducts',
            'dateFrom',
            'dateTo'
        ));
    }
}
