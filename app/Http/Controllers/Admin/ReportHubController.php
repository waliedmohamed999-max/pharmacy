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

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $periodOrders = Order::query()->whereBetween('created_at', [$from, $to]);

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

        $statusBreakdown = (clone $periodOrders)
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(total), 0) as revenue'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $reportGroups = [
            [
                'title' => 'تقارير مالية ومحاسبية',
                'description' => 'المركز المالي، قائمة الدخل، التدفقات النقدية، دفتر الأستاذ، وميزان المراجعة.',
                'accent' => 'emerald',
                'reports' => [
                    ['label' => 'المركز المالي', 'desc' => 'الأصول والالتزامات وحقوق الملكية', 'route' => route('admin.accounting.reports.balance-sheet')],
                    ['label' => 'قائمة الدخل', 'desc' => 'الإيرادات والمصروفات وصافي الربح', 'route' => route('admin.accounting.reports.income-statement')],
                    ['label' => 'التدفقات النقدية', 'desc' => 'حركة التحصيلات والمدفوعات', 'route' => route('admin.accounting.reports.cash-flow')],
                    ['label' => 'ميزان المراجعة', 'desc' => 'مطابقة المدين والدائن', 'route' => route('admin.accounting.reports.trial-balance')],
                    ['label' => 'دفتر الأستاذ', 'desc' => 'كشف حساب تفصيلي', 'route' => route('admin.accounting.reports.ledger')],
                    ['label' => 'مركز المالية', 'desc' => 'تحليل الفواتير والربحية والضرائب', 'route' => route('admin.finance.index')],
                ],
            ],
            [
                'title' => 'تقارير المبيعات والطلبات',
                'description' => 'طلبات المتجر، الطلبات اليدوية، نقاط البيع، العملاء، وسجل التحصيل.',
                'accent' => 'blue',
                'reports' => [
                    ['label' => 'كل الطلبات', 'desc' => 'فلترة حالات الطلب والتوصيل والمدفوعات', 'route' => route('admin.orders.index')],
                    ['label' => 'سجل نقاط البيع', 'desc' => 'فواتير POS وإيصالاتها', 'route' => route('admin.pos.history')],
                    ['label' => 'فواتير المبيعات', 'desc' => 'كل فواتير البيع المحاسبية', 'route' => route('admin.accounting.sales.index')],
                    ['label' => 'تصدير الطلبات', 'desc' => 'CSV للفواتير والطلبات', 'route' => route('admin.finance.export', ['type' => 'invoices'])],
                    ['label' => 'تقرير العملاء', 'desc' => 'بيانات العملاء وتصديرها', 'route' => route('admin.customers.index')],
                    ['label' => 'تحصيل / سداد', 'desc' => 'حركات الدفع والتحصيل', 'route' => route('admin.accounting.payments.index')],
                ],
            ],
            [
                'title' => 'تقارير المخزون والصيدلية',
                'description' => 'الأرصدة، حركات الصرف والاستلام، الجرد، النواقص، وكارت الصنف.',
                'accent' => 'cyan',
                'reports' => [
                    ['label' => 'أرصدة المخزون', 'desc' => 'بحث حسب المخزن أو الصنف', 'route' => route('admin.inventory.stocks')],
                    ['label' => 'حركات المخزون', 'desc' => 'الوارد والصادر والتحويلات', 'route' => route('admin.inventory.movements')],
                    ['label' => 'تنبيهات النواقص', 'desc' => 'أصناف تحت حد الطلب', 'route' => route('admin.inventory.alerts')],
                    ['label' => 'كارت الصنف', 'desc' => 'كشف حركة منتج محدد', 'route' => route('admin.inventory.stock-card')],
                    ['label' => 'جلسات الجرد', 'desc' => 'جرد فعلي واعتماد فروقات', 'route' => route('admin.inventory.counts.index')],
                    ['label' => 'تصدير النظرة العامة', 'desc' => 'CSV شامل للمخزون', 'route' => route('admin.inventory.export.overview')],
                ],
            ],
            [
                'title' => 'تقارير الكتالوج والواجهة',
                'description' => 'المنتجات، التصنيفات، البنرات، صفحات المتجر، وترتيب الصفحة الرئيسية.',
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
                'description' => 'الصلاحيات، المستخدمون، مؤشرات التشغيل، وروابط المراجعة اليومية.',
                'accent' => 'violet',
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

        return view('admin.reports.index', compact('summary', 'monthlySales', 'reportGroups', 'statusBreakdown', 'dateFrom', 'dateTo'));
    }
}
