<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProductBarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    private function defaultProductPlaceholders(): array
    {
        return [
            'images/placeholders/product-1.svg',
            'images/placeholders/product-2.svg',
            'images/placeholders/product-3.svg',
            'images/placeholders/product-4.svg',
            'images/placeholders/product-5.svg',
            'images/placeholders/product-6.svg',
        ];
    }

    private function randomProductPlaceholder(): string
    {
        $list = $this->defaultProductPlaceholders();
        return $list[array_rand($list)];
    }

    private function realProductImageUrl(?string $name): string
    {
        $query = trim((string) $name);
        if ($query === '') {
            $query = 'pharmacy medicine product';
        }

        $query = preg_replace('/\s+/', ' ', $query . ' pharmacy product');

        return 'https://tse1.mm.bing.net/th?q=' . rawurlencode($query) . '&w=700&h=700&c=7&rs=1&p=0&o=5&pid=1.7';
    }

    private function isExternalUrl(?string $path): bool
    {
        if (!$path) {
            return false;
        }
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    public function index()
    {
        $query = Product::with('category');

        if (request('search')) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('sku', 'like', '%' . request('search') . '%')
                    ->orWhere('barcode', 'like', '%' . request('search') . '%');
            });
        }

        if (request('category_id')) {
            $query->where('category_id', request('category_id'));
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->get();
        $product = new Product();

        return view('admin.products.create', compact('categories', 'product'));
    }

    public function store(StoreProductRequest $request, ProductBarcodeService $barcodeService)
    {
        $data = $request->validated();
        $imageUrl = trim((string)($request->input('primary_image_url') ?? ''));
        $data['barcode'] = $barcodeService->normalize((string) ($data['barcode'] ?? ''));

        if ($request->hasFile('primary_image')) {
            $data['primary_image'] = $request->file('primary_image')->store('products', 'public');
        } elseif ($imageUrl !== '') {
            $data['primary_image'] = $imageUrl;
        } else {
            $data['primary_image'] = $this->realProductImageUrl($data['name'] ?? '');
        }

        $product = Product::create($data);
        $barcodeService->assignIfMissing($product, (string) ($data['barcode'] ?: ($data['sku'] ?? '')));
        $this->syncProductStockFromQuantity($product);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $index => $file) {
                $product->images()->create([
                    'path' => $file->store('products/gallery', 'public'),
                    'sort_order' => $index,
                ]);
            }
        } else {
            $product->images()->create([
                'path' => $this->realProductImageUrl($product->name),
                'sort_order' => 0,
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'تم إنشاء المنتج بنجاح');
    }

    public function edit(Product $product)
    {
        $product->load('images');
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function barcode(Product $product, ProductBarcodeService $barcodeService)
    {
        $barcodeValue = $barcodeService->assignIfMissing($product, (string) ($product->barcode ?: $product->sku));
        $barcodeSvg = $barcodeService->svg($barcodeValue, 80);

        return view('admin.products.barcode', compact('product', 'barcodeValue', 'barcodeSvg'));
    }

    public function labelsForm()
    {
        $products = Product::query()
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'sku', 'barcode', 'price']);

        return view('admin.products.labels', compact('products'));
    }

    public function labelsPrint(Request $request, ProductBarcodeService $barcodeService)
    {
        $data = $request->validate([
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'copies' => ['required', 'array'],
            'copies.*' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $rows = [];
        foreach ($data['product_id'] as $i => $productId) {
            $copies = (int) ($data['copies'][$i] ?? 1);
            if ($copies < 1) {
                continue;
            }
            $rows[] = ['product_id' => (int) $productId, 'copies' => $copies];
        }

        $products = Product::query()->whereIn('id', collect($rows)->pluck('product_id')->values())->get()->keyBy('id');
        $labels = [];

        foreach ($rows as $row) {
            $product = $products->get($row['product_id']);
            if (!$product) {
                continue;
            }

            $barcodeValue = $barcodeService->assignIfMissing($product, (string) ($product->barcode ?: $product->sku));
            $barcodeSvg = $barcodeService->svg($barcodeValue, 52);

            for ($i = 0; $i < $row['copies']; $i++) {
                $labels[] = [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'barcode' => $barcodeValue,
                    'svg' => $barcodeSvg,
                ];
            }
        }

        return view('admin.products.labels-print', compact('labels'));
    }

    public function update(UpdateProductRequest $request, Product $product, ProductBarcodeService $barcodeService)
    {
        $data = $request->validated();
        $imageUrl = trim((string)($request->input('primary_image_url') ?? ''));
        $data['barcode'] = $barcodeService->normalize((string) ($data['barcode'] ?? ''));

        if ($request->hasFile('primary_image')) {
            if (
                $product->primary_image &&
                !str_starts_with($product->primary_image, 'images/') &&
                !$this->isExternalUrl($product->primary_image) &&
                Storage::disk('public')->exists($product->primary_image)
            ) {
                Storage::disk('public')->delete($product->primary_image);
            }

            $data['primary_image'] = $request->file('primary_image')->store('products', 'public');
        } elseif ($imageUrl !== '' && $imageUrl !== $product->primary_image) {
            if (
                $product->primary_image &&
                !str_starts_with($product->primary_image, 'images/') &&
                !$this->isExternalUrl($product->primary_image) &&
                Storage::disk('public')->exists($product->primary_image)
            ) {
                Storage::disk('public')->delete($product->primary_image);
            }

            $data['primary_image'] = $imageUrl;
        }

        $product->update($data);
        $barcodeService->assignIfMissing($product, (string) ($data['barcode'] ?: ($data['sku'] ?? '')));
        $this->syncProductStockFromQuantity($product->fresh());

        if ($request->filled('delete_gallery')) {
            foreach ($product->images()->whereIn('id', $request->input('delete_gallery', []))->get() as $image) {
                if (!str_starts_with($image->path, 'images/') && Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $index => $file) {
                $product->images()->create([
                    'path' => $file->store('products/gallery', 'public'),
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'تم تعديل المنتج بنجاح');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'تم نقل المنتج إلى المحذوفات');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $deleted = Product::query()
            ->whereIn('id', $data['product_ids'])
            ->delete();

        return back()->with('success', "تم نقل {$deleted} منتج إلى المحذوفات");
    }

    public function destroyAll()
    {
        $deleted = Product::query()->delete();

        return back()->with('success', "تم نقل كل الأدوية إلى المحذوفات ({$deleted} منتج)");
    }

    public function refreshRealImages()
    {
        $updated = 0;

        Product::query()->orderBy('id')->chunkById(300, function ($products) use (&$updated) {
            foreach ($products as $product) {
                $imageUrl = $this->realProductImageUrl($product->name);

                $product->forceFill([
                    'primary_image' => $imageUrl,
                ])->save();

                $product->images()
                    ->where(function ($query) {
                        $query->whereNull('path')
                            ->orWhere('path', '')
                            ->orWhere('path', 'like', 'images/placeholders/%')
                            ->orWhere('path', 'images/placeholder.png');
                    })
                    ->delete();

                $product->images()->updateOrCreate(
                    ['sort_order' => 0],
                    ['path' => $imageUrl]
                );

                $updated++;
            }
        });

        return back()->with('success', "تم تحديث صور {$updated} منتج بصور مرتبطة باسم المنتج.");
    }

    public function trash()
    {
        $products = Product::onlyTrashed()->latest()->paginate(15);

        return view('admin.products.trash', compact('products'));
    }

    public function restore(int $id)
    {
        Product::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'تم استرجاع المنتج');
    }

    public function forceDelete(int $id)
    {
        Product::withTrashed()->findOrFail($id)->forceDelete();

        return back()->with('success', 'تم حذف المنتج نهائيًا');
    }

    public function exportCsv()
    {
        $filename = 'products-' . now()->format('Y-m-d-His') . '.csv';
        $columns = ['id', 'name', 'sku', 'barcode', 'category_id', 'price', 'compare_price', 'quantity', 'is_active', 'featured', 'tags', 'short_description', 'description', 'primary_image'];

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, chr(239) . chr(187) . chr(191));
            fputcsv($handle, $columns);

            Product::with('category')->orderBy('id')->chunk(200, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, array_map([$this, 'csvSafe'], [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->barcode,
                        $product->category_id,
                        $product->price,
                        $product->compare_price,
                        $product->quantity,
                        $product->is_active ? 1 : 0,
                        $product->featured ? 1 : 0,
                        $product->tags,
                        $product->short_description,
                        $product->description,
                        $product->primary_image,
                    ]));
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:15360',
        ]);

        $rows = $this->readImportRows(
            $request->file('file')->getRealPath(),
            strtolower((string) $request->file('file')->getClientOriginalExtension())
        );

        if (empty($rows)) {
            return back()->with('error', 'ملف الاستيراد فارغ أو غير صالح.');
        }

        $barcodeService = app(ProductBarcodeService::class);
        $warehouseId = Warehouse::query()->where('is_active', true)->value('id') ?? Warehouse::query()->value('id');
        $imported = 0;
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $categoryId = $this->resolveCategoryId($row);
            if (!$categoryId) {
                continue;
            }

            $sku = trim((string)($row['sku'] ?? ''));
            $barcode = trim((string)($row['barcode'] ?? ''));
            $product = null;

            if ($sku !== '') {
                $product = Product::withTrashed()->where('sku', $sku)->first();
            }

            if (!$product) {
                $product = Product::withTrashed()->where('name', $name)->first();
            }

            $data = [
                'category_id' => $categoryId,
                'name' => $name,
                'sku' => $sku !== '' ? $sku : null,
                'barcode' => $barcode !== '' ? strtoupper($barcode) : null,
                'price' => (float)($row['price'] ?? 0),
                'compare_price' => ($row['compare_price'] ?? '') === '' ? null : (float)$row['compare_price'],
                'quantity' => (int)($row['quantity'] ?? 0),
                'is_active' => $this->toBool($row['is_active'] ?? true),
                'featured' => $this->toBool($row['featured'] ?? false),
                'tags' => trim((string)($row['tags'] ?? '')) ?: null,
                'short_description' => trim((string)($row['short_description'] ?? '')) ?: null,
                'description' => trim((string)($row['description'] ?? '')) ?: null,
                'primary_image' => $this->safeImportedImage((string)($row['primary_image'] ?? ''), $name),
            ];

            if ($product) {
                if ($product->trashed()) {
                    $product->restore();
                }
                $product->update($data);
            } else {
                $product = Product::create($data);
            }

            if ($product instanceof Product) {
                $barcodeService->assignIfMissing($product, (string) ($product->barcode ?: $product->sku));
                if ($warehouseId) {
                    ProductStock::query()->updateOrCreate(
                        [
                            'warehouse_id' => (int) $warehouseId,
                            'product_id' => (int) $product->id,
                        ],
                        [
                            'qty' => (float) ($data['quantity'] ?? 0),
                            'avg_cost' => (float) ($product->avg_cost ?? 0),
                        ]
                    );
                    app(InventoryService::class)->syncProductTotals((int) $product->id);
                }
            }

            $imported++;
        }

        return back()->with('success', "تم استيراد/تحديث {$imported} منتج من الملف.");
    }

    private function syncProductStockFromQuantity(?Product $product): void
    {
        if (! $product) {
            return;
        }

        $inventory = app(InventoryService::class);
        $warehouse = $inventory->ensureDefaultWarehouse();

        ProductStock::query()->updateOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ],
            [
                'qty' => (float) ($product->quantity ?? 0),
                'avg_cost' => (float) ($product->avg_cost ?? 0),
            ]
        );

        $inventory->syncProductTotals((int) $product->id);
    }

    private function resolveCategoryId(array $row): ?int
    {
        $inferredCategory = $this->inferPharmacyCategoryName($row);
        if ($inferredCategory) {
            $row['category_name'] = $inferredCategory;
        }

        if (!empty($row['category_id']) && is_numeric($row['category_id'])) {
            $category = Category::find((int)$row['category_id']);
            if ($category) {
                return $category->id;
            }
        }

        $slug = trim((string)($row['category_slug'] ?? ''));
        if ($slug !== '') {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                return $category->id;
            }
        }

        $name = trim((string)($row['category_name'] ?? ''));
        if ($name !== '') {
            $category = Category::where('name', $name)
                ->orWhere('name_ar', $name)
                ->orWhere('name_en', $name)
                ->first();
            if ($category) {
                return $category->id;
            }

            $category = Category::create([
                'name' => $name,
                'name_ar' => $name,
                'is_active' => true,
                'sort_order' => (int) Category::max('sort_order') + 1,
            ]);

            return $category->id;
        }

        return Category::query()->value('id');
    }

    private function inferPharmacyCategoryName(array $row): ?string
    {
        $currentCategory = trim((string) ($row['category_name'] ?? ''));
        $text = mb_strtolower(trim(implode(' ', [
            $row['name'] ?? '',
            $row['tags'] ?? '',
            $row['short_description'] ?? '',
            $row['description'] ?? '',
        ])));

        if ($text === '') {
            return $currentCategory !== '' && $currentCategory !== 'أدوية' ? $currentCategory : null;
        }

        $rules = [
            'أجهزة ومستلزمات طبية' => ['جهاز', 'ترمومتر', 'ميزان', 'نيبولايزر', 'بخاخة', 'كمامة', 'قناع', 'جوانتي', 'قفاز', 'حقنة', 'سرنجة', 'accu-chek', 'accu chek', 'device'],
            'السكري والضغط' => ['سكر', 'السكر', 'سكري', 'انسولين', 'إنسولين', 'جلوكوفاج', 'ميتفورمين', 'شرائط قياس', 'ضغط', 'كونكور', 'تارج', 'diabetes', 'insulin', 'glucose', 'blood pressure'],
            'العناية بالبشرة' => ['بشرة', 'جلد', 'غسول', 'لوشن', 'مرطب', 'واقي شمس', 'صن بلوك', 'كريم للوجه', 'بانثينول', 'سيرافي', 'لاروش', 'فيشي', 'بيوديرما', 'يورياج', 'cerave', 'bioderma', 'vichy', 'uriage', 'panthenol', 'sunscreen', 'skin'],
            'العناية بالشعر' => ['شعر', 'شامبو', 'بلسم', 'قشرة', 'تساقط', 'صبغة', 'نخاع', 'hair', 'shampoo', 'conditioner', 'dandruff'],
            'الأم والطفل' => ['طفل', 'أطفال', 'اطفال', 'بيبي', 'رضيع', 'رضاعة', 'حفاض', 'بامبرز', 'مستيلا', 'baby', 'kids', 'infant', 'diaper', 'mustela'],
            'الفيتامينات والمكملات' => ['فيتامين', 'مكمل', 'كالسيوم', 'حديد', 'زنك', 'اوميجا', 'أوميجا', 'سنتروم', 'centrum', 'vitamin', 'omega', 'zinc', 'calcium', 'supplement'],
            'الإسعافات الأولية' => ['شاش', 'قطن', 'بلاستر', 'ضمادة', 'مطهر', 'كحول', 'بيتادين', 'جرح', 'لاصق', 'bandage', 'gauze', 'alcohol', 'betadine'],
            'الفم والأسنان' => ['أسنان', 'اسنان', 'فم', 'معجون', 'فرشاة', 'مضمضة', 'غسول فم', 'سنسوداين', 'oral', 'dental', 'tooth', 'mouth'],
            'العناية الشخصية' => ['رول اون', 'مزيل', 'عرق', 'فوط', 'غسول نسائي', 'صابون', 'مناديل', 'deodorant', 'personal', 'soap'],
        ];

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, mb_strtolower($keyword))) {
                    return $category;
                }
            }
        }

        return $currentCategory !== '' && $currentCategory !== 'أدوية' ? $currentCategory : 'الأدوية والروشتات';
    }

    private function readImportRows(string $path, string $extension): array
    {
        if (in_array($extension, ['xls', 'xlsx'], true)) {
            return $this->readSpreadsheetRows($path);
        }

        return $this->readCsvRows($path);
    }

    private function readSpreadsheetRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $pharmacyHeaderRow = null;
        for ($row = 1; $row <= min($highestRow, 30); $row++) {
            $values = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $values[] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue());
            }

            if (in_array('اسم الصنف', $values, true) && in_array('الكود', $values, true)) {
                $pharmacyHeaderRow = $row;
                break;
            }
        }

        if ($pharmacyHeaderRow) {
            return $this->readPharmacyInventorySheet($sheet, $pharmacyHeaderRow, $highestRow);
        }

        $headerRow = null;
        $header = [];
        for ($row = 1; $row <= min($highestRow, 20); $row++) {
            $values = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $values[] = $this->normalizeImportHeader((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue());
            }

            if (in_array('name', $values, true) || in_array('sku', $values, true) || in_array('barcode', $values, true)) {
                $headerRow = $row;
                $header = $values;
                break;
            }
        }

        if (!$headerRow) {
            return [];
        }

        $rows = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $item = [];
            $hasValue = false;
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $key = $header[$column - 1] ?? null;
                if (!$key) {
                    continue;
                }

                $value = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue();
                $item[$key] = trim((string) $value);
                $hasValue = $hasValue || $item[$key] !== '';
            }

            if ($hasValue) {
                $rows[] = $item;
            }
        }

        return $rows;
    }

    private function readPharmacyInventorySheet($sheet, int $headerRow, int $highestRow): array
    {
        $rows = [];
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $name = trim((string) $sheet->getCell('Q' . $row)->getFormattedValue());
            $code = trim((string) $sheet->getCell('V' . $row)->getFormattedValue());

            if ($name === '' && $code === '') {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $price = $this->numberFromSheet($sheet->getCell('E' . $row)->getFormattedValue());
            $quantity = $this->numberFromSheet($sheet->getCell('H' . $row)->getFormattedValue());
            $unit = trim((string) $sheet->getCell('M' . $row)->getFormattedValue());
            $company = trim((string) $sheet->getCell('O' . $row)->getFormattedValue());
            $expiry = trim((string) $sheet->getCell('J' . $row)->getFormattedValue());
            $notes = collect([
                $company !== '' ? 'الشركة: ' . $company : null,
                $unit !== '' ? 'الوحدة: ' . $unit : null,
                $expiry !== '' ? 'الصلاحية: ' . $expiry : null,
            ])->filter()->implode(' | ');

            $rows[] = [
                'name' => $name,
                'sku' => $code,
                'barcode' => $code,
                'price' => $price,
                'quantity' => $quantity,
                'category_name' => $this->inferPharmacyCategoryName([
                    'name' => $name,
                    'tags' => $company,
                    'short_description' => $notes,
                    'description' => $notes,
                ]) ?: 'الأدوية والروشتات',
                'is_active' => true,
                'featured' => false,
                'tags' => $company,
                'short_description' => $notes,
                'description' => $notes,
            ];
        }

        return $rows;
    }

    private function normalizeImportHeader(string $header): ?string
    {
        $header = trim(preg_replace('/^\xEF\xBB\xBF/', '', $header));
        $normalized = mb_strtolower($header);
        $normalized = str_replace([' ', '-', '_', ':'], '', $normalized);

        return match ($normalized) {
            'name', 'productname', 'product', 'اسم', 'اسمالمنتج', 'اسمالصنف', 'الصنف' => 'name',
            'sku', 'كود', 'كودالصنف' => 'sku',
            'barcode', 'bar', 'باركود', 'الباركود', 'الكود' => 'barcode',
            'categoryid', 'category_id', 'رقمالتصنيف' => 'category_id',
            'categoryslug', 'category_slug' => 'category_slug',
            'categoryname', 'category', 'التصنيف', 'اسمالتصنيف' => 'category_name',
            'price', 'saleprice', 'sellingprice', 'السعر', 'سعرالبيع', 'البيع' => 'price',
            'compareprice', 'compare_price', 'oldprice', 'السعرالقديم' => 'compare_price',
            'quantity', 'qty', 'stock', 'المخزون', 'الكمية', 'الرصيد' => 'quantity',
            'isactive', 'is_active', 'active', 'الحالة', 'نشط' => 'is_active',
            'featured', 'مميز' => 'featured',
            'tags', 'الشركة', 'company' => 'tags',
            'shortdescription', 'short_description', 'وصفمختصر' => 'short_description',
            'description', 'الوصف' => 'description',
            'primaryimage', 'primary_image', 'image', 'الصورة' => 'primary_image',
            default => null,
        };
    }

    private function numberFromSheet(mixed $value): float
    {
        $value = str_replace([',', 'ج.م', 'جم'], '', trim((string) $value));
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value) {
            $value = (string)$value;
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return $this->normalizeImportHeader($value) ?: trim($value);
        }, $header);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($header, array_pad($row, count($header), null));
        }

        fclose($handle);
        return $rows;
    }

    private function toBool(mixed $value): bool
    {
        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on', 'active'], true);
    }

    private function safeImportedImage(string $value, string $name): string
    {
        $value = trim($value);

        if ($value === '') {
            return $this->realProductImageUrl($name);
        }

        if (str_starts_with($value, 'https://') || str_starts_with($value, 'images/')) {
            return $value;
        }

        return $this->realProductImageUrl($name);
    }

    private function csvSafe(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
