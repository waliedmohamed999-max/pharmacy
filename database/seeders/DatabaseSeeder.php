<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Customer;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private array $productPlaceholders = [
        'images/placeholders/product-1.svg',
        'images/placeholders/product-2.svg',
        'images/placeholders/product-3.svg',
        'images/placeholders/product-4.svg',
        'images/placeholders/product-5.svg',
        'images/placeholders/product-6.svg',
    ];

    public function run(): void
    {
        $this->createPlaceholderAssets();

        User::updateOrCreate(
            ['email' => 'admin@drpharmacy.test'],
            ['name' => 'مدير النظام', 'password' => Hash::make('password')]
        );

        StoreSetting::setValue('home_banner_autoplay', '1');
        StoreSetting::setValue('footer_enabled', '1');
        StoreSetting::setValue('footer_show_pages', '1');
        StoreSetting::setValue('footer_newsletter_enabled', '1');
        StoreSetting::setValue('footer_brand_title', 'صيدلية د. محمد رمضان');
        StoreSetting::setValue('footer_links_title', 'روابط مفيدة');
        StoreSetting::setValue('footer_newsletter_title', 'النشرة الإخبارية');
        StoreSetting::setValue('footer_contact_title', 'اتصل بنا');
        StoreSetting::setValue('footer_about', 'نوفّر منتجات دوائية وعناية صحية بجودة عالية مع تجربة شراء سهلة وآمنة.');
        StoreSetting::setValue('footer_newsletter_text', 'اشترك لمعرفة أحدث العروض والمنتجات الجديدة أولًا.');
        StoreSetting::setValue('footer_contact_address', 'الرياض - طريق الملك عبدالعزيز');
        StoreSetting::setValue('footer_contact_phone', '0509095816');
        StoreSetting::setValue('footer_contact_email', 'info@drpharmacy.test');
        StoreSetting::setValue('footer_copyright', '© ' . date('Y') . ' صيدلية د. محمد رمضان');
        StoreSetting::setValue('footer_links_json', json_encode([
            ['label' => 'واتساب', 'url' => 'https://wa.me/201000000000'],
            ['label' => 'اتصل بنا', 'url' => 'tel:+201000000000'],
        ], JSON_UNESCAPED_UNICODE));

        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'من نحن',
                'excerpt' => 'تعرف على صيدلية د. محمد رمضان ورسالتنا في تقديم رعاية دوائية موثوقة.',
                'content' => "نحن صيدلية متخصصة في تقديم منتجات دوائية وعناية صحية بجودة عالية.\nنعمل على خدمة العملاء بسرعة واحترافية مع التزام كامل بسلامة المنتج.",
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'سياسة الخصوصية',
                'excerpt' => 'طريقة جمع البيانات واستخدامها داخل المتجر.',
                'content' => "نلتزم بحماية خصوصية بيانات العملاء وعدم مشاركتها مع أي طرف غير مصرح.\nتستخدم البيانات فقط لإتمام الطلبات وتحسين تجربة الاستخدام.",
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $pharmacyCategories = [
            ['ar' => 'الأدوية والروشتات', 'en' => 'Medicines & Prescriptions', 'sub_ar' => 'مسكنات وبرد وحساسية', 'sub_en' => 'Pain Relief, Cold & Allergy'],
            ['ar' => 'الفيتامينات والمكملات', 'en' => 'Vitamins & Supplements', 'sub_ar' => 'مناعة وطاقة وصحة العظام', 'sub_en' => 'Immunity, Energy & Bone Health'],
            ['ar' => 'العناية بالبشرة', 'en' => 'Skin Care', 'sub_ar' => 'غسول ومرطبات وواقي شمس', 'sub_en' => 'Cleansers, Moisturizers & Sunscreen'],
            ['ar' => 'العناية بالشعر', 'en' => 'Hair Care', 'sub_ar' => 'تساقط وقشرة وشامبو طبي', 'sub_en' => 'Hair Loss, Dandruff & Medicated Shampoo'],
            ['ar' => 'الأم والطفل', 'en' => 'Mother & Baby', 'sub_ar' => 'حفاضات ورضاعات وعناية أطفال', 'sub_en' => 'Diapers, Feeding & Baby Care'],
            ['ar' => 'أجهزة ومستلزمات طبية', 'en' => 'Medical Devices', 'sub_ar' => 'ضغط وسكر وحرارة ونيبولايزر', 'sub_en' => 'BP, Glucose, Thermometers & Nebulizers'],
            ['ar' => 'السكري والضغط', 'en' => 'Diabetes & Blood Pressure', 'sub_ar' => 'شرائط قياس وعناية القدم', 'sub_en' => 'Test Strips & Foot Care'],
            ['ar' => 'الإسعافات الأولية', 'en' => 'First Aid', 'sub_ar' => 'مطهرات وشاش ولاصقات طبية', 'sub_en' => 'Antiseptics, Gauze & Plasters'],
            ['ar' => 'العناية الشخصية', 'en' => 'Personal Care', 'sub_ar' => 'نظافة يومية ومزيلات وروائح', 'sub_en' => 'Daily Hygiene & Deodorants'],
            ['ar' => 'الفم والأسنان', 'en' => 'Oral & Dental Care', 'sub_ar' => 'معجون وفرشاة وغسول فم', 'sub_en' => 'Toothpaste, Brushes & Mouthwash'],
        ];

        foreach ($pharmacyCategories as $index => $category) {
            $i = $index + 1;
            $main = Category::factory()->create([
                'name' => $category['ar'],
                'name_ar' => $category['ar'],
                'name_en' => $category['en'],
                'image' => 'images/placeholder.png',
                'sort_order' => $i,
                'is_active' => true,
            ]);

            Category::factory()->create([
                'name' => $category['sub_ar'],
                'name_ar' => $category['sub_ar'],
                'name_en' => $category['sub_en'],
                'image' => 'images/placeholder.png',
                'parent_id' => $main->id,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        $allCategories = Category::all();

        Product::factory(80)->make()->each(function (Product $product) use ($allCategories) {
            $product->category_id = $allCategories->random()->id;
            $product->primary_image = $this->randomProductPlaceholder();
            $product->save();

            $galleryCount = rand(2, 4);
            for ($i = 0; $i < $galleryCount; $i++) {
                $product->images()->create([
                    'path' => $this->randomProductPlaceholder(),
                    'sort_order' => $i,
                ]);
            }
        });

        Banner::factory(6)->create();

        Order::factory(25)->create()->each(function (Order $order) {
            $products = Product::inRandomOrder()->take(rand(2, 5))->get();
            $subtotal = 0;

            foreach ($products as $product) {
                $qty = rand(1, 3);
                $line = $product->price * $qty;
                $subtotal += $line;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'price' => $product->price,
                    'qty' => $qty,
                    'line_total' => $line,
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping' => 0,
                'total' => $subtotal,
            ]);
        });

        $this->syncCustomersFromOrders();
        $this->call(AccountingSeeder::class);

        $this->seedHomeSections();
    }

    private function seedHomeSections(): void
    {
        $defaults = [
            ['key' => 'slider_banners', 'title_ar' => 'بنرات رئيسية', 'type' => 'static', 'sort_order' => 1, 'is_active' => true, 'data_source' => 'banners'],
            ['key' => 'categories_circles', 'title_ar' => 'التصنيفات', 'type' => 'auto', 'sort_order' => 2, 'is_active' => true, 'data_source' => 'categories', 'filters_json' => ['limit' => 12]],
            ['key' => 'flash_deals', 'title_ar' => 'عروض اليوم', 'type' => 'auto', 'sort_order' => 3, 'is_active' => true, 'data_source' => 'discounted', 'filters_json' => ['limit' => 12]],
            ['key' => 'featured_products', 'title_ar' => 'منتجات مميزة', 'type' => 'manual', 'sort_order' => 4, 'is_active' => true, 'data_source' => 'products', 'filters_json' => ['limit' => 12]],
            ['key' => 'best_sellers', 'title_ar' => 'الأكثر مبيعًا', 'type' => 'auto', 'sort_order' => 5, 'is_active' => true, 'data_source' => 'best_sellers', 'filters_json' => ['limit' => 12]],
            ['key' => 'new_arrivals', 'title_ar' => 'وصلنا حديثًا', 'type' => 'auto', 'sort_order' => 6, 'is_active' => true, 'data_source' => 'new_arrivals', 'filters_json' => ['limit' => 12]],
            ['key' => 'collections', 'title_ar' => 'حسب احتياجك', 'type' => 'auto', 'sort_order' => 7, 'is_active' => true, 'data_source' => 'tags', 'filters_json' => ['tags' => ['برد وزكام', 'فيتامينات', 'عناية شعر', 'أطفال']]],
        ];

        foreach ($defaults as $row) {
            HomeSection::updateOrCreate(['key' => $row['key']], $row);
        }

        $featured = HomeSection::where('key', 'featured_products')->first();
        if ($featured && $featured->items()->count() === 0) {
            Product::where('is_active', true)->inRandomOrder()->take(10)->get()->each(function (Product $product, int $index) use ($featured) {
                $featured->items()->create([
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'sort_order' => $index,
                ]);
            });
        }
    }

    private function randomProductPlaceholder(): string
    {
        return $this->productPlaceholders[array_rand($this->productPlaceholders)];
    }

    private function syncCustomersFromOrders(): void
    {
        Order::query()->whereNull('customer_id')->chunk(100, function ($orders) {
            foreach ($orders as $order) {
                $customer = Customer::query()
                    ->when($order->phone, function ($q) use ($order) {
                        $q->where('phone', $order->phone);
                    }, function ($q) use ($order) {
                        $q->where('name', $order->customer_name);
                    })
                    ->first();

                if (!$customer) {
                    $customer = Customer::create([
                        'name' => $order->customer_name,
                        'phone' => $order->phone,
                        'city' => $order->city,
                        'address' => $order->address,
                        'is_active' => true,
                    ]);
                }

                $order->update(['customer_id' => $customer->id]);
            }
        });
    }

    private function createPlaceholderAssets(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=');

        if (!is_dir(public_path('images'))) {
            mkdir(public_path('images'), 0777, true);
        }

        if (!is_dir(public_path('images/placeholders'))) {
            mkdir(public_path('images/placeholders'), 0777, true);
        }

        file_put_contents(public_path('images/placeholder.png'), $png);

        $palettes = [
            ['#dbeafe', '#0ea5e9', '#0369a1'],
            ['#fee2e2', '#ef4444', '#991b1b'],
            ['#dcfce7', '#22c55e', '#166534'],
            ['#fef3c7', '#f59e0b', '#92400e'],
            ['#ede9fe', '#8b5cf6', '#4c1d95'],
            ['#e0f2fe', '#06b6d4', '#155e75'],
        ];

        foreach ($palettes as $i => $colors) {
            [$bg, $primary, $dark] = $colors;
            $n = $i + 1;
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <rect width="800" height="800" fill="{$bg}"/>
  <circle cx="400" cy="320" r="180" fill="{$primary}" opacity="0.15"/>
  <rect x="250" y="250" width="300" height="340" rx="30" fill="white"/>
  <rect x="290" y="300" width="220" height="22" rx="11" fill="{$primary}" opacity="0.9"/>
  <rect x="290" y="340" width="170" height="16" rx="8" fill="{$primary}" opacity="0.55"/>
  <rect x="290" y="375" width="190" height="16" rx="8" fill="{$primary}" opacity="0.4"/>
  <rect x="290" y="510" width="120" height="32" rx="16" fill="{$dark}" opacity="0.85"/>
  <text x="400" y="640" text-anchor="middle" fill="{$dark}" font-family="Arial" font-size="36" font-weight="700">Product {$n}</text>
</svg>
SVG;
            file_put_contents(public_path("images/placeholders/product-{$n}.svg"), $svg);
        }
    }
}
