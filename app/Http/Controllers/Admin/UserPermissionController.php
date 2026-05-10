<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    private array $permissionGroups = [
        'operations' => [
            'label' => 'العمليات الأساسية',
            'description' => 'لوحة التحكم والطلبات والعملاء والمنتجات.',
            'items' => [
                'dashboard.view' => 'عرض لوحة التحكم',
                'orders.view' => 'عرض الطلبات',
                'orders.manage' => 'تعديل حالات الطلبات',
                'products.view' => 'عرض المنتجات',
                'products.manage' => 'إضافة وتعديل المنتجات',
                'products.delete' => 'حذف المنتجات',
                'categories.manage' => 'إدارة التصنيفات',
                'customers.view' => 'عرض العملاء',
                'customers.import_export' => 'استيراد/تصدير العملاء',
            ],
        ],
        'inventory' => [
            'label' => 'المخزون والصيدلية',
            'description' => 'الأرصدة، الحركات، الجرد، المخازن، والنواقص.',
            'items' => [
                'inventory.view' => 'عرض المخزون',
                'inventory.warehouse.manage' => 'إدارة المخازن',
                'inventory.receive' => 'سندات الاستلام',
                'inventory.issue' => 'سندات الصرف',
                'inventory.transfer' => 'التحويل المخزني',
                'inventory.adjust' => 'تسويات المخزون',
                'inventory.alerts' => 'تنبيهات المخزون',
                'inventory.stock_card' => 'كارت الصنف',
                'inventory.count' => 'إنشاء/تعديل الجرد',
                'inventory.count.post' => 'اعتماد الجرد',
                'inventory.pdf' => 'طباعة/تحميل سندات PDF',
            ],
        ],
        'pos' => [
            'label' => 'نقطة البيع POS',
            'description' => 'البيع المباشر والطلبات اليدوية وسجل الفواتير.',
            'items' => [
                'pos.sell' => 'إنشاء فاتورة POS',
                'pos.history' => 'عرض سجل POS',
                'pos.refund' => 'مرتجعات واسترداد',
                'pos.discount' => 'تطبيق خصومات يدوية',
            ],
        ],
        'finance' => [
            'label' => 'المالية والمحاسبة',
            'description' => 'الفواتير، القيود، المدفوعات، والتقارير المالية.',
            'items' => [
                'finance.view' => 'عرض المالية',
                'finance.export' => 'تصدير التقارير المالية',
                'accounting.view' => 'عرض النظام المالي',
                'accounting.sales' => 'فواتير المبيعات',
                'accounting.purchases' => 'فواتير المشتريات',
                'accounting.journal' => 'القيود اليومية',
                'accounting.payments' => 'المدفوعات والتحصيل',
                'accounting.reports' => 'التقارير المحاسبية',
            ],
        ],
        'storefront' => [
            'label' => 'الواجهة الخارجية والتسويق',
            'description' => 'Home Builder، البنرات، الصفحات، والفوتر.',
            'items' => [
                'storefront.view' => 'معاينة المتجر',
                'home_builder.manage' => 'إدارة Home Builder',
                'banners.manage' => 'إدارة البنرات',
                'pages.manage' => 'إدارة الصفحات',
                'footer.manage' => 'إعدادات الفوتر',
                'marketing.manage' => 'العروض والتسويق',
            ],
        ],
        'system' => [
            'label' => 'النظام والأمان',
            'description' => 'المستخدمين، الصلاحيات، الإعدادات، والتدقيق.',
            'items' => [
                'users.permissions' => 'إدارة صلاحيات المستخدمين',
                'settings.manage' => 'إعدادات النظام',
                'reports.view' => 'عرض التقارير العامة',
                'exports.manage' => 'التصدير والاستيراد',
                'audit.view' => 'سجل التدقيق والعمليات',
            ],
        ],
    ];

    private array $rolePresets = [
        'admin' => [
            'label' => 'مدير النظام',
            'description' => 'وصول كامل لكل النظام بدون قيود.',
            'permissions' => ['*'],
        ],
        'pharmacist' => [
            'label' => 'صيدلي',
            'description' => 'طلبات، منتجات، مخزون، وبيع مباشر.',
            'permissions' => [
                'dashboard.view', 'orders.view', 'orders.manage', 'products.view', 'customers.view',
                'inventory.view', 'inventory.receive', 'inventory.issue', 'inventory.alerts', 'inventory.stock_card',
                'pos.sell', 'pos.history',
            ],
        ],
        'cashier' => [
            'label' => 'كاشير',
            'description' => 'بيع مباشر وسجل نقاط البيع فقط.',
            'permissions' => ['dashboard.view', 'products.view', 'customers.view', 'inventory.view', 'pos.sell', 'pos.history', 'pos.discount'],
        ],
        'inventory_manager' => [
            'label' => 'مدير مخزون',
            'description' => 'إدارة كاملة للمخزون والجرد والمخازن.',
            'permissions' => [
                'dashboard.view', 'products.view', 'products.manage', 'categories.manage',
                'inventory.view', 'inventory.warehouse.manage', 'inventory.receive', 'inventory.issue',
                'inventory.transfer', 'inventory.adjust', 'inventory.alerts', 'inventory.stock_card',
                'inventory.count', 'inventory.count.post', 'inventory.pdf',
            ],
        ],
        'accountant' => [
            'label' => 'محاسب',
            'description' => 'مالية ومحاسبة وتقارير بدون تعديل المخزون.',
            'permissions' => [
                'dashboard.view', 'orders.view', 'finance.view', 'finance.export',
                'accounting.view', 'accounting.sales', 'accounting.purchases',
                'accounting.journal', 'accounting.payments', 'accounting.reports',
                'reports.view', 'exports.manage',
            ],
        ],
        'marketing' => [
            'label' => 'تسويق وواجهة',
            'description' => 'إدارة الواجهة الخارجية والعروض والمحتوى.',
            'permissions' => [
                'dashboard.view', 'products.view', 'storefront.view', 'home_builder.manage',
                'banners.manage', 'pages.manage', 'footer.manage', 'marketing.manage',
            ],
        ],
        'staff' => [
            'label' => 'موظف مخصص',
            'description' => 'اختيار يدوي للصلاحيات حسب الحاجة.',
            'permissions' => ['dashboard.view'],
        ],
    ];

    public function index()
    {
        $this->authorizeAdmin();

        $users = User::query()->orderBy('name')->get();
        $permissionGroups = $this->permissionGroups;
        $permissions = $this->flatPermissions();
        $rolePresets = $this->rolePresets;

        return view('admin.users.permissions', compact('users', 'permissionGroups', 'permissions', 'rolePresets'));
    }

    public function update(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:' . implode(',', array_keys($this->rolePresets))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $validKeys = array_keys($this->flatPermissions());
        $selected = array_values(array_intersect($data['permissions'] ?? [], $validKeys));

        $user = User::query()->findOrFail($data['user_id']);
        $user->update([
            'role' => $data['role'],
            'permissions_json' => $data['role'] === 'admin' ? [] : $selected,
        ]);

        return back()->with('success', 'تم تحديث صلاحيات المستخدم بنجاح.');
    }

    private function flatPermissions(): array
    {
        $permissions = [];

        foreach ($this->permissionGroups as $group) {
            $permissions = array_merge($permissions, $group['items']);
        }

        return $permissions;
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'admin', 403, 'صلاحية غير متاحة.');
    }
}
