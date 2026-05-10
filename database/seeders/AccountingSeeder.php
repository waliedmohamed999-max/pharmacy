<?php

namespace Database\Seeders;

use App\Models\FinanceAccount;
use App\Models\FinanceContact;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            ['code' => '1000', 'name' => 'الأصول', 'type' => 'asset', 'parent_code' => null],
            ['code' => '1100', 'name' => 'الأصول المتداولة', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1110', 'name' => 'الصندوق', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1120', 'name' => 'البنك', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1130', 'name' => 'العملاء (مدينون)', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1140', 'name' => 'المخزون', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1150', 'name' => 'سلف وعهد', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1200', 'name' => 'الأصول الثابتة', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1210', 'name' => 'أثاث وتجهيزات', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'مجمع إهلاك الأثاث', 'type' => 'asset', 'parent_code' => '1200'],

            ['code' => '2000', 'name' => 'الالتزامات', 'type' => 'liability', 'parent_code' => null],
            ['code' => '2100', 'name' => 'الالتزامات المتداولة', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2110', 'name' => 'الموردون (دائنون)', 'type' => 'liability', 'parent_code' => '2100'],
            ['code' => '2120', 'name' => 'مصروفات مستحقة', 'type' => 'liability', 'parent_code' => '2100'],
            ['code' => '2130', 'name' => 'ضرائب مستحقة', 'type' => 'liability', 'parent_code' => '2100'],

            ['code' => '3000', 'name' => 'حقوق الملكية', 'type' => 'equity', 'parent_code' => null],
            ['code' => '3100', 'name' => 'رأس المال', 'type' => 'equity', 'parent_code' => '3000'],
            ['code' => '3200', 'name' => 'أرباح محتجزة', 'type' => 'equity', 'parent_code' => '3000'],

            ['code' => '4000', 'name' => 'الإيرادات', 'type' => 'revenue', 'parent_code' => null],
            ['code' => '4100', 'name' => 'إيراد المبيعات', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4200', 'name' => 'خصم مكتسب', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4300', 'name' => 'إيرادات أخرى', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4310', 'name' => 'مكاسب تسويات مخزون', 'type' => 'revenue', 'parent_code' => '4300'],

            ['code' => '5000', 'name' => 'المصروفات', 'type' => 'expense', 'parent_code' => null],
            ['code' => '5100', 'name' => 'المشتريات', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5110', 'name' => 'تكلفة البضاعة المباعة', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5200', 'name' => 'مصروفات تشغيلية', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5210', 'name' => 'مرتبات وأجور', 'type' => 'expense', 'parent_code' => '5200'],
            ['code' => '5220', 'name' => 'إيجارات', 'type' => 'expense', 'parent_code' => '5200'],
            ['code' => '5230', 'name' => 'كهرباء ومياه', 'type' => 'expense', 'parent_code' => '5200'],
            ['code' => '5240', 'name' => 'صيانة', 'type' => 'expense', 'parent_code' => '5200'],
            ['code' => '5300', 'name' => 'مصروفات بيع وتسويق', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5310', 'name' => 'خسائر تسويات مخزون', 'type' => 'expense', 'parent_code' => '5300'],
        ];

        foreach ($tree as $row) {
            $parentId = null;
            if (!empty($row['parent_code'])) {
                $parentId = FinanceAccount::query()->where('code', $row['parent_code'])->value('id');
            }

            FinanceAccount::updateOrCreate(
                ['code' => $row['code']],
                [
                    'parent_id' => $parentId,
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'is_active' => true,
                    'is_system' => true,
                ]
            );
        }

        Warehouse::updateOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'المخزن الرئيسي', 'location' => 'الفرع الرئيسي', 'is_active' => true]
        );

        Order::query()->whereNotNull('customer_name')->chunk(100, function ($orders) {
            foreach ($orders as $order) {
                FinanceContact::updateOrCreate(
                    ['phone' => $order->phone ?: ('order-' . $order->id)],
                    [
                        'type' => 'customer',
                        'name' => $order->customer_name,
                        'phone' => $order->phone,
                        'city' => $order->city,
                        'address' => $order->address,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
