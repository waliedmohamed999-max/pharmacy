import 'dart:convert';
import 'dart:ui' as ui;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';

const configuredApiBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: '');

String get apiBaseUrl {
  if (configuredApiBaseUrl.isNotEmpty) return configuredApiBaseUrl;
  if (kReleaseMode) {
    throw StateError('API_BASE_URL is required for release builds.');
  }
  return kIsWeb ? 'http://127.0.0.1:8000/api/mobile' : 'http://10.0.2.2:8000/api/mobile';
}

void main() => runApp(const PharmacyClientApp());

class PharmacyClientApp extends StatefulWidget {
  const PharmacyClientApp({super.key});

  @override
  State<PharmacyClientApp> createState() => _PharmacyClientAppState();
}

class _PharmacyClientAppState extends State<PharmacyClientApp> {
  final cart = CartStore();
  ThemeMode themeMode = ThemeMode.light;

  @override
  Widget build(BuildContext context) {
    return CartScope(
      cart: cart,
      child: MaterialApp(
        title: 'صيدلية د. محمد رمضان',
        debugShowCheckedModeBanner: false,
        locale: const Locale('ar'),
        themeMode: themeMode,
        theme: appTheme(Brightness.light),
        darkTheme: appTheme(Brightness.dark),
        builder: (context, child) => Directionality(textDirection: ui.TextDirection.rtl, child: child!),
        home: ClientShell(
          onToggleTheme: () => setState(() => themeMode = themeMode == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark),
        ),
      ),
    );
  }
}

ThemeData appTheme(Brightness brightness) {
  final dark = brightness == Brightness.dark;
  final scheme = ColorScheme.fromSeed(
    seedColor: const Color(0xff059669),
    brightness: brightness,
    primary: const Color(0xff059669),
    secondary: const Color(0xff14b8a6),
    surface: dark ? const Color(0xff111827) : Colors.white,
  );

  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    colorScheme: scheme,
    scaffoldBackgroundColor: dark ? const Color(0xff07111f) : const Color(0xfff1f7fa),
    fontFamily: 'Cairo',
    textTheme: ThemeData(brightness: brightness).textTheme.apply(
          fontFamily: 'Cairo',
          bodyColor: dark ? Colors.white : const Color(0xff0f172a),
          displayColor: dark ? Colors.white : const Color(0xff0f172a),
        ),
    appBarTheme: const AppBarTheme(centerTitle: false, elevation: 0),
    cardTheme: CardThemeData(
      elevation: 0,
      color: dark ? const Color(0xff111827) : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: dark ? const Color(0xff0f172a) : Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: BorderSide(color: dark ? const Color(0xff1f2937) : const Color(0xffdbe7ef))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: BorderSide(color: scheme.primary, width: 1.4)),
    ),
  );
}

class ApiClient {
  final http.Client _client = http.Client();

  Future<Map<String, dynamic>> getJson(String path, [Map<String, String>? query]) async {
    final uri = _secureUri(path).replace(queryParameters: query?..removeWhere((_, value) => value.trim().isEmpty));
    final response = await _client.get(uri, headers: _headers()).timeout(const Duration(seconds: 20));
    return _decode(response);
  }

  Future<Map<String, dynamic>> postJson(String path, Map<String, dynamic> body) async {
    final response = await _client.post(
      _secureUri(path),
      headers: _headers(json: true),
      body: jsonEncode(body),
    ).timeout(const Duration(seconds: 25));
    return _decode(response);
  }

  Uri _secureUri(String path) {
    final uri = Uri.parse('$apiBaseUrl$path');
    final localHosts = {'127.0.0.1', 'localhost', '10.0.2.2'};
    if (kReleaseMode && uri.scheme != 'https' && !localHosts.contains(uri.host)) {
      throw ApiException('اتصال غير آمن. يجب استخدام HTTPS في نسخة الإنتاج.');
    }
    return uri;
  }

  Map<String, String> _headers({bool json = false}) => {
        'Accept': 'application/json',
        'X-Client': 'flutter-customer-app',
        if (json) 'Content-Type': 'application/json',
      };

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 400) {
      throw ApiException((decoded['message'] ?? decoded['errors']?.toString() ?? 'حدث خطأ في الاتصال').toString());
    }
    return decoded;
  }
}

class ApiException implements Exception {
  ApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class Product {
  Product({
    required this.id,
    required this.name,
    required this.slug,
    required this.price,
    required this.image,
    required this.availableQty,
    this.comparePrice,
    this.discountPercent = 0,
    this.description = '',
    this.categoryName = '',
    this.sku = '',
    this.barcode = '',
    this.images = const [],
  });

  final int id;
  final String name;
  final String slug;
  final String sku;
  final String barcode;
  final double price;
  final double? comparePrice;
  final String image;
  final List<String> images;
  final int availableQty;
  final int discountPercent;
  final String description;
  final String categoryName;

  bool get inStock => availableQty > 0;

  factory Product.fromJson(Map<String, dynamic> json) {
    final image = json['image'] as String? ?? '';
    final images = (json['images'] as List? ?? []).map((item) => item.toString()).where((item) => item.isNotEmpty).toList();
    return Product(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      sku: json['sku'] as String? ?? '',
      barcode: json['barcode'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      comparePrice: (json['compare_price'] as num?)?.toDouble(),
      image: image,
      images: images.isEmpty && image.isNotEmpty ? [image] : images,
      availableQty: (json['available_qty'] as num?)?.toInt() ?? 0,
      discountPercent: (json['discount_percent'] as num?)?.toInt() ?? 0,
      description: (json['description'] as String?) ?? (json['short_description'] as String?) ?? '',
      categoryName: (json['category'] as Map<String, dynamic>?)?['name'] as String? ?? '',
    );
  }
}

class CategoryItem {
  CategoryItem({required this.id, required this.name, required this.image, required this.count});
  final int id;
  final String name;
  final String image;
  final int count;

  factory CategoryItem.fromJson(Map<String, dynamic> json) => CategoryItem(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        image: json['image'] as String? ?? '',
        count: (json['products_count'] as num?)?.toInt() ?? 0,
      );
}

class OrderSummary {
  OrderSummary({required this.id, required this.status, required this.total, required this.createdAt, required this.items});
  final int id;
  final String status;
  final double total;
  final String createdAt;
  final List<dynamic> items;

  factory OrderSummary.fromJson(Map<String, dynamic> json) => OrderSummary(
        id: (json['id'] as num?)?.toInt() ?? 0,
        status: json['status'] as String? ?? '',
        total: (json['total'] as num?)?.toDouble() ?? 0,
        createdAt: json['created_at'] as String? ?? '',
        items: json['items'] as List? ?? [],
      );
}

class CartItem {
  CartItem({required this.product, this.qty = 1});
  final Product product;
  int qty;
  double get total => product.price * qty;
}

class CartStore extends ChangeNotifier {
  final Map<int, CartItem> _items = {};
  List<CartItem> get items => _items.values.toList();
  int get count => _items.values.fold(0, (sum, item) => sum + item.qty);
  double get subtotal => _items.values.fold(0, (sum, item) => sum + item.total);
  double get delivery => subtotal >= 500 || subtotal == 0 ? 0 : 35;
  double get total => subtotal + delivery;
  double get freeShippingRemaining => (500 - subtotal).clamp(0, 500).toDouble();

  void add(Product product) {
    if (!product.inStock) return;
    final current = _items[product.id];
    if (current == null) {
      _items[product.id] = CartItem(product: product);
    } else if (current.qty < product.availableQty) {
      current.qty++;
    }
    notifyListeners();
  }

  void setQty(Product product, int qty) {
    if (qty <= 0) {
      _items.remove(product.id);
    } else {
      _items[product.id] = CartItem(product: product, qty: qty.clamp(1, product.availableQty).toInt());
    }
    notifyListeners();
  }

  void remove(Product product) {
    _items.remove(product.id);
    notifyListeners();
  }

  void clear() {
    _items.clear();
    notifyListeners();
  }
}

class CartScope extends InheritedNotifier<CartStore> {
  const CartScope({required CartStore cart, required super.child, super.key}) : super(notifier: cart);
  static CartStore of(BuildContext context) => context.dependOnInheritedWidgetOfExactType<CartScope>()!.notifier!;
}

class ClientShell extends StatefulWidget {
  const ClientShell({required this.onToggleTheme, super.key});
  final VoidCallback onToggleTheme;

  @override
  State<ClientShell> createState() => _ClientShellState();
}

class _ClientShellState extends State<ClientShell> {
  final api = ApiClient();
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(api: api),
      ProductsScreen(api: api),
      CartScreen(api: api),
      OrdersScreen(api: api),
    ];

    return AnimatedBuilder(
      animation: CartScope.of(context),
      builder: (context, _) => Scaffold(
        body: IndexedStack(index: index, children: pages),
        bottomNavigationBar: NavigationBar(
          height: 72,
          selectedIndex: index,
          onDestinationSelected: (value) => setState(() => index = value),
          destinations: [
            const NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'الرئيسية'),
            const NavigationDestination(icon: Icon(Icons.medication_liquid_outlined), selectedIcon: Icon(Icons.medication), label: 'المنتجات'),
            NavigationDestination(
              icon: Badge(label: Text('${CartScope.of(context).count}'), child: const Icon(Icons.shopping_cart_outlined)),
              selectedIcon: const Icon(Icons.shopping_cart),
              label: 'السلة',
            ),
            const NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'طلباتي'),
          ],
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: widget.onToggleTheme,
          icon: const Icon(Icons.dark_mode_outlined),
          label: const Text('الوضع'),
        ),
      ),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({required this.api, super.key});
  final ApiClient api;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late Future<Map<String, dynamic>> future;

  @override
  void initState() {
    super.initState();
    future = widget.api.getJson('/home');
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () {
        final nextFuture = widget.api.getJson('/home');
        setState(() => future = nextFuture);
        return nextFuture;
      },
      child: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) return const LoadingHome();
          if (snapshot.hasError) return ErrorState(message: snapshot.error.toString(), onRetry: () => setState(() => future = widget.api.getJson('/home')));

          final data = snapshot.data!;
          final store = Map<String, dynamic>.from(data['store'] as Map);
          final banners = (data['banners'] as List? ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
          final categories = (data['categories'] as List? ?? []).map((e) => CategoryItem.fromJson(e as Map<String, dynamic>)).toList();
          final sections = data['sections'] as Map<String, dynamic>? ?? {};
          final featured = productsFrom(sections['featured']);
          final deals = productsFrom(sections['deals']);
          final best = productsFrom(sections['best_sellers']);
          final newest = productsFrom(sections['new_arrivals']);
          final concerns = mapsFrom(sections['concerns']);

          return CustomScrollView(
            slivers: [
              SliverToBoxAdapter(child: StoreHeader(store: store)),
              SliverToBoxAdapter(child: HeroCarousel(banners: banners)),
              const SliverToBoxAdapter(child: TrustBadges()),
              SliverToBoxAdapter(child: CategoryStrip(categories: categories, onTap: (category) => openProducts(context, widget.api, category: category))),
              SliverToBoxAdapter(child: ProductRail(title: 'عروض اليوم', products: deals, urgent: true)),
              SliverToBoxAdapter(child: ProductRail(title: 'منتجات مميزة', products: featured)),
              SliverToBoxAdapter(child: ProductRail(title: 'الأكثر مبيعا', products: best)),
              SliverToBoxAdapter(child: ProductRail(title: 'وصل حديثا', products: newest)),
              const SliverToBoxAdapter(child: BrandCarousel()),
              SliverToBoxAdapter(child: ConcernGrid(concerns: concerns)),
              SliverToBoxAdapter(child: PharmacyToolsHub(api: widget.api)),
              const SliverToBoxAdapter(child: SizedBox(height: 96)),
            ],
          );
        },
      ),
    );
  }
}

List<Product> productsFrom(dynamic raw) => (raw as List? ?? []).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
List<Map<String, dynamic>> mapsFrom(dynamic raw) => (raw as List? ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();

void openProducts(BuildContext context, ApiClient api, {CategoryItem? category}) {
  Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductsScreen(api: api, initialCategory: category, standalone: true)));
}

class StoreHeader extends StatelessWidget {
  const StoreHeader({required this.store, super.key});
  final Map<String, dynamic> store;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 10, 14, 16),
      decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                height: 52,
                width: 52,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)),
                child: const Icon(Icons.medication_liquid_rounded, color: Colors.white, size: 28),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(store['name'] as String? ?? 'صيدلية د. محمد رمضان', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 20)),
                    Text(store['tagline'] as String? ?? 'رعاية موثوقة وتسوق أسرع', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .80), fontWeight: FontWeight.w700)),
                  ],
                ),
              ),
              IconButton.filledTonal(
                onPressed: () {},
                icon: const Icon(Icons.notifications_none_rounded),
                style: IconButton.styleFrom(backgroundColor: Colors.white.withValues(alpha: .14), foregroundColor: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SearchBox(readOnly: true, onTap: () => openProducts(context, ApiClient())),
        ],
      ),
    );
  }
}

class SearchBox extends StatelessWidget {
  const SearchBox({this.controller, this.onChanged, this.onTap, this.readOnly = false, super.key});
  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onTap;
  final bool readOnly;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      onChanged: onChanged,
      onTap: onTap,
      readOnly: readOnly,
      decoration: const InputDecoration(
        hintText: 'ابحث عن دواء، فيتامين، باركود أو منتج صحي',
        prefixIcon: Icon(Icons.search),
        suffixIcon: Icon(Icons.qr_code_scanner),
      ),
    );
  }
}

class HeroCarousel extends StatelessWidget {
  const HeroCarousel({required this.banners, super.key});
  final List<Map<String, dynamic>> banners;

  @override
  Widget build(BuildContext context) {
    final items = banners.isEmpty
        ? [
            {'title': 'عروض الصيدلية', 'subtitle': 'خصومات على منتجات العناية والصحة', 'image': ''}
          ]
        : banners;

    return SizedBox(
      height: 290,
      child: PageView.builder(
        controller: PageController(viewportFraction: .94),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final banner = items[index];
          return Container(
            margin: const EdgeInsets.fromLTRB(6, 16, 6, 10),
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(30),
              gradient: LinearGradient(
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
                colors: index.isEven ? const [Color(0xff065f46), Color(0xff059669), Color(0xff34d399)] : const [Color(0xff075985), Color(0xff0f766e), Color(0xff14b8a6)],
              ),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .10), blurRadius: 28, offset: const Offset(0, 14))],
            ),
            child: Stack(
              children: [
                const Positioned(left: -50, top: -40, child: CircleBlob(size: 160, opacity: .13)),
                const Positioned(right: -70, bottom: -80, child: CircleBlob(size: 220, opacity: .10)),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      Expanded(
                        flex: 6,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const PillLabel(label: 'منتجات أصلية 100%'),
                            const SizedBox(height: 12),
                            Text(banner['title'] as String? ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w900, height: 1.06)),
                            const SizedBox(height: 8),
                            Text(banner['subtitle'] as String? ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .88), fontWeight: FontWeight.w800, fontSize: 13, height: 1.45)),
                            const SizedBox(height: 14),
                            FilledButton(
                              onPressed: () => openProducts(context, ApiClient()),
                              style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xff065f46)),
                              child: const Text('تسوق الآن'),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        flex: 4,
                        child: PharmacyVisual(image: banner['image'] as String? ?? ''),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class CircleBlob extends StatelessWidget {
  const CircleBlob({required this.size, required this.opacity, super.key});
  final double size;
  final double opacity;
  @override
  Widget build(BuildContext context) => Container(width: size, height: size, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: opacity)));
}

class PillLabel extends StatelessWidget {
  const PillLabel({required this.label, super.key});
  final String label;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99)),
        child: Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11)),
      );
}

class PharmacyVisual extends StatelessWidget {
  const PharmacyVisual({required this.image, super.key});
  final String image;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 150,
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: .15), borderRadius: BorderRadius.circular(28)),
      child: Center(
        child: image.isEmpty
            ? const Icon(Icons.medication_liquid, color: Colors.white, size: 64)
            : AppImage(url: image, fit: BoxFit.contain),
      ),
    );
  }
}

class TrustBadges extends StatelessWidget {
  const TrustBadges({super.key});

  @override
  Widget build(BuildContext context) {
    const items = [
      [Icons.verified_user_outlined, 'منتجات أصلية', 'مصادر موثوقة'],
      [Icons.local_shipping_outlined, 'توصيل سريع', '24-48 ساعة'],
      [Icons.credit_card_outlined, 'دفع آمن', 'حماية كاملة'],
      [Icons.support_agent_outlined, 'دعم مستمر', 'متابعة الطلب'],
    ];

    return SizedBox(
      height: 96,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        scrollDirection: Axis.horizontal,
        itemBuilder: (context, index) {
          final item = items[index];
          return InfoTile(icon: item[0] as IconData, title: item[1] as String, subtitle: item[2] as String);
        },
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemCount: items.length,
      ),
    );
  }
}

class InfoTile extends StatelessWidget {
  const InfoTile({required this.icon, required this.title, required this.subtitle, super.key});
  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 180,
      padding: const EdgeInsets.all(14),
      decoration: softCard(context),
      child: Row(
        children: [
          CircleIcon(icon: icon),
          const SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [Text(title, style: const TextStyle(fontWeight: FontWeight.w900)), Text(subtitle, style: mutedStyle(context, 12))])),
        ],
      ),
    );
  }
}

class CategoryStrip extends StatelessWidget {
  const CategoryStrip({required this.categories, required this.onTap, super.key});
  final List<CategoryItem> categories;
  final ValueChanged<CategoryItem> onTap;

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const SizedBox.shrink();
    return SectionBlock(
      title: 'أقسام الصيدلية',
      action: 'كل الأقسام',
      child: SizedBox(
        height: 156,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          scrollDirection: Axis.horizontal,
          itemCount: categories.length,
          separatorBuilder: (_, __) => const SizedBox(width: 10),
          itemBuilder: (context, index) {
            final category = categories[index];
            return InkWell(
              onTap: () => onTap(category),
              borderRadius: BorderRadius.circular(24),
              child: Container(
                width: 132,
                padding: const EdgeInsets.all(12),
                decoration: softCard(context),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(width: 58, height: 58, child: AppImage(url: category.image, fit: BoxFit.contain)),
                    const SizedBox(height: 10),
                    Text(category.name, maxLines: 2, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w900, height: 1.2)),
                    Text('${category.count} منتج', style: mutedStyle(context, 11)),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class ProductRail extends StatelessWidget {
  const ProductRail({required this.title, required this.products, this.urgent = false, super.key});
  final String title;
  final List<Product> products;
  final bool urgent;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();
    return SectionBlock(
      title: title,
      urgent: urgent,
      child: SizedBox(
        height: 318,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          scrollDirection: Axis.horizontal,
          itemCount: products.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) => SizedBox(width: 212, child: ProductCard(product: products[index])),
        ),
      ),
    );
  }
}

class ProductCard extends StatelessWidget {
  const ProductCard({required this.product, super.key});
  final Product product;

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return InkWell(
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductDetailsScreen(product: product, api: ApiClient()))),
      borderRadius: BorderRadius.circular(26),
      child: Container(
        decoration: softCard(context),
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
              children: [
                Container(
                  height: 126,
                  width: double.infinity,
                  decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .07), borderRadius: BorderRadius.circular(22)),
                  child: Padding(padding: const EdgeInsets.all(10), child: AppImage(url: product.image, fit: BoxFit.contain)),
                ),
                if (product.discountPercent > 0)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5), decoration: BoxDecoration(color: Colors.redAccent, borderRadius: BorderRadius.circular(99)), child: Text('خصم ${product.discountPercent}%', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 10))),
                  ),
                Positioned(
                  left: 8,
                  bottom: 8,
                  child: StockBadge(qty: product.availableQty),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, height: 1.3)),
            const SizedBox(height: 6),
            Text(product.categoryName.isEmpty ? 'منتج صيدلي' : product.categoryName, maxLines: 1, overflow: TextOverflow.ellipsis, style: mutedStyle(context, 12)),
            const Spacer(),
            if (product.comparePrice != null && product.comparePrice! > product.price)
              Text(money(product.comparePrice!), style: const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey, fontWeight: FontWeight.w800, fontSize: 11)),
            Text(money(product.price), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900, fontSize: 18)),
            const SizedBox(height: 10),
            FilledButton.icon(
              onPressed: product.inStock ? () => cart.add(product) : null,
              icon: const Icon(Icons.add_shopping_cart, size: 17),
              label: Text(product.inStock ? 'أضف للسلة' : 'غير متاح'),
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(42), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16))),
            ),
          ],
        ),
      ),
    );
  }
}

class StockBadge extends StatelessWidget {
  const StockBadge({required this.qty, super.key});
  final int qty;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
        decoration: BoxDecoration(color: qty > 5 ? Colors.green.withValues(alpha: .10) : Colors.orange.withValues(alpha: .12), borderRadius: BorderRadius.circular(99)),
        child: Text(qty > 0 ? 'متوفر $qty' : 'غير متاح', style: TextStyle(color: qty > 5 ? Colors.green.shade700 : Colors.orange.shade800, fontWeight: FontWeight.w900, fontSize: 10)),
      );
}

class BrandCarousel extends StatelessWidget {
  const BrandCarousel({super.key});
  @override
  Widget build(BuildContext context) {
    const brands = ['Bioderma', 'La Roche-Posay', 'Vichy', 'Centrum', 'Mustela', 'Accu-Chek', 'Sebamed', 'Now'];
    return SectionBlock(
      title: 'أشهر الماركات الطبية',
      child: SizedBox(
        height: 82,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          scrollDirection: Axis.horizontal,
          itemCount: brands.length,
          separatorBuilder: (_, __) => const SizedBox(width: 10),
          itemBuilder: (context, index) => Container(width: 150, alignment: Alignment.center, decoration: softCard(context), child: Text(brands[index], style: const TextStyle(fontWeight: FontWeight.w900, color: Colors.grey))),
        ),
      ),
    );
  }
}

class ConcernGrid extends StatelessWidget {
  const ConcernGrid({required this.concerns, super.key});
  final List<Map<String, dynamic>> concerns;

  @override
  Widget build(BuildContext context) {
    final items = concerns.isEmpty
        ? [
            {'title': 'المناعة', 'subtitle': 'دعم يومي لصحة أقوى'},
            {'title': 'السكري', 'subtitle': 'قياس ومتابعة'},
            {'title': 'ضغط الدم', 'subtitle': 'أجهزة منزلية'},
          ]
        : concerns;
    return SectionBlock(
      title: 'تسوق حسب الاحتياج',
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        child: GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 1.45),
          itemCount: items.length,
          itemBuilder: (context, index) {
            final item = items[index];
            return Container(
              padding: const EdgeInsets.all(16),
              decoration: softCard(context),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [const CircleIcon(icon: Icons.medication_outlined), const SizedBox(height: 12), Text(item['title']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16)), Text(item['subtitle']?.toString() ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: mutedStyle(context, 12))]),
            );
          },
        ),
      ),
    );
  }
}

class PharmacyToolsHub extends StatelessWidget {
  const PharmacyToolsHub({required this.api, super.key});
  final ApiClient api;

  void _comingSoon(BuildContext context, String title) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$title قيد التجهيز')));
  }

  @override
  Widget build(BuildContext context) {
    final tools = [
      _PharmacyTool('كل المنتجات', 'تصفح الصيدلية كاملة', Icons.medication_liquid_outlined, const Color(0xff059669), () => openProducts(context, api)),
      _PharmacyTool('تتبع الطلب', 'اعرف حالة طلبك بالجوال', Icons.receipt_long_outlined, const Color(0xff0ea5e9), () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => OrdersScreen(api: api)))),
      _PharmacyTool('سلة الشراء', 'راجع المنتجات والدفع', Icons.shopping_cart_checkout_rounded, const Color(0xfff59e0b), () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CartScreen(api: api)))),
      _PharmacyTool('مسح باركود', 'ابحث عن المنتج بسرعة', Icons.qr_code_scanner_rounded, const Color(0xff7c3aed), () => openProducts(context, api)),
      _PharmacyTool('رفع روشتة', 'مراجعة صيدلي قبل الطلب', Icons.upload_file_rounded, const Color(0xffe11d48), () => _comingSoon(context, 'رفع الروشتة')),
      _PharmacyTool('دعم سريع', 'مساعدة مباشرة للطلب', Icons.support_agent_rounded, const Color(0xff0f766e), () => _comingSoon(context, 'الدعم السريع')),
    ];

    return SectionBlock(
      title: 'أدوات الصيدلية',
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(28),
                gradient: const LinearGradient(colors: [Color(0xff064e3b), Color(0xff0f766e), Color(0xff14b8a6)]),
                boxShadow: [BoxShadow(color: const Color(0xff059669).withValues(alpha: .18), blurRadius: 28, offset: const Offset(0, 14))],
              ),
              child: Row(
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)),
                    child: const Icon(Icons.local_pharmacy_rounded, color: Colors.white, size: 28),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('خدمات أسرع داخل التطبيق', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18)),
                        const SizedBox(height: 5),
                        Text('اختصارات ذكية للشراء، التتبع، الباركود، والروشتات.', style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w700, height: 1.5)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 1.42),
              itemCount: tools.length,
              itemBuilder: (context, index) => _ToolCard(tool: tools[index]),
            ),
          ],
        ),
      ),
    );
  }
}

class _PharmacyTool {
  const _PharmacyTool(this.title, this.subtitle, this.icon, this.color, this.onTap);
  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
}

class _ToolCard extends StatelessWidget {
  const _ToolCard({required this.tool});
  final _PharmacyTool tool;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: tool.onTap,
      borderRadius: BorderRadius.circular(24),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(color: tool.color.withValues(alpha: .12), borderRadius: BorderRadius.circular(16)),
              child: Icon(tool.icon, color: tool.color, size: 25),
            ),
            const SizedBox(height: 12),
            Text(tool.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15)),
            const SizedBox(height: 3),
            Text(tool.subtitle, maxLines: 2, overflow: TextOverflow.ellipsis, style: mutedStyle(context, 12)),
          ],
        ),
      ),
    );
  }
}

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({required this.api, this.initialCategory, this.standalone = false, super.key});
  final ApiClient api;
  final CategoryItem? initialCategory;
  final bool standalone;

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final search = TextEditingController();
  String sort = 'newest';
  bool inStockOnly = false;
  bool grid = true;
  CategoryItem? selectedCategory;
  late Future<List<CategoryItem>> categoriesFuture;
  late Future<List<Product>> productsFuture;

  @override
  void dispose() {
    search.dispose();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    selectedCategory = widget.initialCategory;
    categoriesFuture = widget.api.getJson('/categories').then((json) => (json['data'] as List).map((e) => CategoryItem.fromJson(e as Map<String, dynamic>)).toList());
    productsFuture = loadProducts();
  }

  Future<List<Product>> loadProducts() async {
    final query = <String, String>{
      'q': search.text,
      'sort': sort,
      'per_page': '50',
      if (selectedCategory != null) 'category_id': '${selectedCategory!.id}',
    };
    final json = await widget.api.getJson('/products', query);
    final items = (json['data'] as List).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
    return inStockOnly ? items.where((item) => item.inStock).toList() : items;
  }

  void reload() => setState(() => productsFuture = loadProducts());

  @override
  Widget build(BuildContext context) {
    final content = RefreshIndicator(
      onRefresh: () {
        final nextFuture = loadProducts();
        setState(() => productsFuture = nextFuture);
        return nextFuture;
      },
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: ProductsHeader(search: search, onSearch: (_) => reload(), grid: grid, onToggleGrid: () => setState(() => grid = !grid), selectedCategory: selectedCategory)),
          SliverToBoxAdapter(child: FilterPanel(categoriesFuture: categoriesFuture, selected: selectedCategory, sort: sort, inStockOnly: inStockOnly, onCategory: (value) => setState(() { selectedCategory = value; productsFuture = loadProducts(); }), onSort: (value) => setState(() { sort = value; productsFuture = loadProducts(); }), onStock: (value) => setState(() { inStockOnly = value; productsFuture = loadProducts(); }))),
          FutureSliverProducts(future: productsFuture, grid: grid, api: widget.api),
          const SliverToBoxAdapter(child: SizedBox(height: 96)),
        ],
      ),
    );

    if (!widget.standalone) return content;
    return Scaffold(appBar: AppBar(title: const Text('كل المنتجات')), body: content);
  }
}

class ProductsHeader extends StatelessWidget {
  const ProductsHeader({required this.search, required this.onSearch, required this.grid, required this.onToggleGrid, required this.selectedCategory, super.key});
  final TextEditingController search;
  final ValueChanged<String> onSearch;
  final bool grid;
  final VoidCallback onToggleGrid;
  final CategoryItem? selectedCategory;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 12, 14, 16),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(width: 52, height: 52, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.medication_liquid_rounded, color: Colors.white)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(selectedCategory?.name ?? 'كل المنتجات', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 27)),
                      Text('أدوية، فيتامينات، عناية ومنتجات صحية موثوقة.', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w800)),
                    ],
                  ),
                ),
                IconButton.filledTonal(
                  onPressed: onToggleGrid,
                  icon: Icon(grid ? Icons.view_list_rounded : Icons.grid_view_rounded),
                  style: IconButton.styleFrom(backgroundColor: Colors.white.withValues(alpha: .16), foregroundColor: Colors.white),
                ),
              ],
            ),
            const SizedBox(height: 14),
            SearchBox(controller: search, onChanged: onSearch),
            const SizedBox(height: 12),
            const Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ProductTrustPill(icon: Icons.verified_user_outlined, label: 'منتجات أصلية'),
                ProductTrustPill(icon: Icons.inventory_2_outlined, label: 'مخزون مباشر'),
                ProductTrustPill(icon: Icons.local_shipping_outlined, label: 'توصيل سريع'),
              ],
            ),
          ],
        ),
      );
}

class ProductTrustPill extends StatelessWidget {
  const ProductTrustPill({required this.icon, required this.label, super.key});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .15), borderRadius: BorderRadius.circular(99)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [Icon(icon, color: Colors.white, size: 16), const SizedBox(width: 6), Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 12))]),
      );
}

class FilterPanel extends StatelessWidget {
  const FilterPanel({required this.categoriesFuture, required this.selected, required this.sort, required this.inStockOnly, required this.onCategory, required this.onSort, required this.onStock, super.key});
  final Future<List<CategoryItem>> categoriesFuture;
  final CategoryItem? selected;
  final String sort;
  final bool inStockOnly;
  final ValueChanged<CategoryItem?> onCategory;
  final ValueChanged<String> onSort;
  final ValueChanged<bool> onStock;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Column(
          children: [
            FutureBuilder<List<CategoryItem>>(
              future: categoriesFuture,
              builder: (context, snapshot) {
                final categories = snapshot.data ?? [];
                return SizedBox(
                  height: 46,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    children: [
                      Padding(padding: const EdgeInsetsDirectional.only(end: 8), child: ChoiceChip(label: const Text('الكل'), selected: selected == null, onSelected: (_) => onCategory(null))),
                      ...categories.map((category) => Padding(padding: const EdgeInsetsDirectional.only(end: 8), child: ChoiceChip(label: Text(category.name), selected: selected?.id == category.id, onSelected: (_) => onCategory(category)))),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    initialValue: sort,
                    decoration: const InputDecoration(labelText: 'ترتيب المنتجات', prefixIcon: Icon(Icons.sort_rounded)),
                    items: const [
                      DropdownMenuItem(value: 'newest', child: Text('الأحدث')),
                      DropdownMenuItem(value: 'price_asc', child: Text('الأقل سعرا')),
                      DropdownMenuItem(value: 'price_desc', child: Text('الأعلى سعرا')),
                      DropdownMenuItem(value: 'name_asc', child: Text('الاسم')),
                    ],
                    onChanged: (value) => onSort(value ?? 'newest'),
                  ),
                ),
                const SizedBox(width: 10),
                FilterChip(showCheckmark: true, avatar: const Icon(Icons.inventory_2_outlined, size: 18), label: const Text('متوفر فقط'), selected: inStockOnly, onSelected: onStock),
              ],
            ),
          ],
        ),
      );
}

class FutureSliverProducts extends StatelessWidget {
  const FutureSliverProducts({required this.future, required this.grid, required this.api, super.key});
  final Future<List<Product>> future;
  final bool grid;
  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Product>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) return const SliverFillRemaining(child: ProductsLoadingState());
        if (snapshot.hasError) return SliverFillRemaining(child: ErrorState(message: snapshot.error.toString(), onRetry: () {}));
        final products = snapshot.data ?? [];
        if (products.isEmpty) return const SliverFillRemaining(child: EmptyState(title: 'لا توجد منتجات', subtitle: 'جرب تغيير الفلاتر أو البحث باسم آخر.'));

        if (!grid) {
          return SliverPadding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
            sliver: SliverList.separated(
              itemCount: products.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) => ProductListTile(product: products[index], api: api),
            ),
          );
        }

        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
          sliver: SliverGrid(
            delegate: SliverChildBuilderDelegate((context, index) => ProductCard(product: products[index]), childCount: products.length),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: .54),
          ),
        );
      },
    );
  }
}

class ProductsLoadingState extends StatelessWidget {
  const ProductsLoadingState({super.key});

  @override
  Widget build(BuildContext context) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 14),
            const Text('جاري تحميل منتجات الصيدلية...', style: TextStyle(fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class ProductListTile extends StatelessWidget {
  const ProductListTile({required this.product, required this.api, super.key});
  final Product product;
  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return InkWell(
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductDetailsScreen(product: product, api: api))),
      borderRadius: BorderRadius.circular(24),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: softCard(context),
        child: Row(
          children: [
            SizedBox(width: 84, height: 84, child: AppImage(url: product.image)),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900)), Text(product.categoryName, style: mutedStyle(context, 12)), const SizedBox(height: 6), Text(money(product.price), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900))])),
            IconButton.filledTonal(onPressed: product.inStock ? () => cart.add(product) : null, icon: const Icon(Icons.add_shopping_cart)),
          ],
        ),
      ),
    );
  }
}

class ProductDetailsScreen extends StatefulWidget {
  const ProductDetailsScreen({required this.product, required this.api, super.key});
  final Product product;
  final ApiClient api;

  @override
  State<ProductDetailsScreen> createState() => _ProductDetailsScreenState();
}

class _ProductDetailsScreenState extends State<ProductDetailsScreen> {
  late Future<Product> future;

  @override
  void initState() {
    super.initState();
    future = widget.api.getJson('/products/${widget.product.slug}').then((json) => Product.fromJson(json['data'] as Map<String, dynamic>));
  }

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('تفاصيل المنتج')),
      body: FutureBuilder<Product>(
        future: future,
        builder: (context, snapshot) {
          final product = snapshot.data ?? widget.product;
          final images = product.images.isEmpty ? [product.image] : product.images;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              SizedBox(
                height: 330,
                child: PageView.builder(
                  itemCount: images.length,
                  itemBuilder: (context, index) => Container(margin: const EdgeInsets.symmetric(horizontal: 3), decoration: softCard(context), child: Padding(padding: const EdgeInsets.all(20), child: AppImage(url: images[index], fit: BoxFit.contain))),
                ),
              ),
              const SizedBox(height: 18),
              Text(product.name, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Wrap(spacing: 8, runSpacing: 8, children: [Chip(label: Text(product.categoryName.isEmpty ? 'منتج صيدلي' : product.categoryName)), Chip(label: Text(product.inStock ? 'متوفر ${product.availableQty}' : 'غير متاح')), if (product.discountPercent > 0) Chip(label: Text('خصم ${product.discountPercent}%')), const Chip(label: Text('منتج أصلي'))]),
              const SizedBox(height: 16),
              Text(money(product.price), style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, color: Theme.of(context).colorScheme.primary)),
              if (product.comparePrice != null && product.comparePrice! > product.price) Text(money(product.comparePrice!), style: const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey, fontWeight: FontWeight.w800)),
              const SizedBox(height: 18),
              Text(product.description.isEmpty ? 'منتج صيدلي موثوق متاح للطلب من التطبيق، ويتم ربط الطلب مباشرة بلوحة التحكم والمخزون.' : product.description, style: const TextStyle(height: 1.8, fontWeight: FontWeight.w600)),
              const SizedBox(height: 96),
            ],
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(16),
        child: FilledButton.icon(
          onPressed: widget.product.inStock ? () => cart.add(widget.product) : null,
          icon: const Icon(Icons.add_shopping_cart),
          label: Text(widget.product.inStock ? 'إضافة للسلة - ${money(widget.product.price)}' : 'غير متاح حاليا'),
        ),
      ),
    );
  }
}

class CartScreen extends StatelessWidget {
  const CartScreen({required this.api, super.key});
  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return AnimatedBuilder(
      animation: cart,
      builder: (context, _) => Scaffold(
        body: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(child: CartHero(cart: cart, onBrowse: () => openProducts(context, api))),
            if (cart.items.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: CartEmptyState(onBrowse: () => openProducts(context, api)),
              )
            else ...[
              SliverToBoxAdapter(child: CartSummary(cart: cart)),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
                sliver: SliverList.separated(
                  itemCount: cart.items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, index) => CartItemCard(item: cart.items[index], cart: cart),
                ),
              ),
              const SliverToBoxAdapter(child: CartBenefits()),
              const SliverToBoxAdapter(child: SizedBox(height: 100)),
            ],
          ],
        ),
        bottomNavigationBar: cart.items.isEmpty
            ? null
            : SafeArea(
                minimum: const EdgeInsets.all(16),
                child: FilledButton.icon(
                  onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CheckoutScreen(api: api))),
                  icon: const Icon(Icons.lock_outline),
                  label: Text('إتمام الطلب - ${money(cart.total)}'),
                ),
              ),
      ),
    );
  }
}

class CartHero extends StatelessWidget {
  const CartHero({required this.cart, required this.onBrowse, super.key});
  final CartStore cart;
  final VoidCallback onBrowse;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 12, 14, 18),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
        child: Row(
          children: [
            Container(width: 52, height: 52, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.shopping_cart_checkout_rounded, color: Colors.white)),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('سلة المشتريات', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 27)), Text(cart.items.isEmpty ? 'ابدأ بإضافة منتجاتك المفضلة.' : '${cart.count} قطعة جاهزة لإتمام الطلب', style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w800))])),
            if (cart.items.isEmpty)
              IconButton.filledTonal(
                onPressed: onBrowse,
                icon: const Icon(Icons.add_shopping_cart_rounded),
                style: IconButton.styleFrom(backgroundColor: Colors.white.withValues(alpha: .16), foregroundColor: Colors.white),
              ),
          ],
        ),
      );
}

class CartEmptyState extends StatelessWidget {
  const CartEmptyState({required this.onBrowse, super.key});
  final VoidCallback onBrowse;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 116,
                height: 116,
                decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .10), shape: BoxShape.circle),
                child: Icon(Icons.shopping_bag_outlined, size: 58, color: Theme.of(context).colorScheme.primary),
              ),
              const SizedBox(height: 18),
              const Text('السلة فارغة', style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text('أضف الأدوية ومنتجات العناية ثم أكمل طلبك مباشرة من التطبيق.', textAlign: TextAlign.center, style: mutedStyle(context, 14)),
              const SizedBox(height: 20),
              FilledButton.icon(onPressed: onBrowse, icon: const Icon(Icons.medication_liquid_outlined), label: const Text('تصفح المنتجات')),
              const SizedBox(height: 10),
              OutlinedButton.icon(onPressed: onBrowse, icon: const Icon(Icons.qr_code_scanner_rounded), label: const Text('بحث أو باركود')),
            ],
          ),
        ),
      );
}

class CartSummary extends StatelessWidget {
  const CartSummary({required this.cart, super.key});
  final CartStore cart;

  @override
  Widget build(BuildContext context) {
    final progress = (cart.subtotal / 500).clamp(0, 1).toDouble();
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(28), gradient: const LinearGradient(colors: [Color(0xff065f46), Color(0xff14b8a6)]), boxShadow: [BoxShadow(color: const Color(0xff059669).withValues(alpha: .20), blurRadius: 26, offset: const Offset(0, 14))]),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [const Expanded(child: Text('ملخص السلة', style: TextStyle(color: Colors.white70, fontWeight: FontWeight.w900))), Text('${cart.count} قطعة', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900))]),
            const SizedBox(height: 8),
            Text(money(cart.total), style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w900)),
            const SizedBox(height: 12),
            ClipRRect(borderRadius: BorderRadius.circular(99), child: LinearProgressIndicator(value: progress, minHeight: 8, backgroundColor: Colors.white24, valueColor: const AlwaysStoppedAnimation(Colors.white))),
            const SizedBox(height: 8),
            Text(cart.freeShippingRemaining > 0 ? 'أضف ${money(cart.freeShippingRemaining)} للحصول على شحن مجاني' : 'طلبك مؤهل للشحن المجاني', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
            const SizedBox(height: 14),
            CartSummaryRow(label: 'إجمالي المنتجات', value: money(cart.subtotal)),
            CartSummaryRow(label: 'التوصيل', value: cart.delivery == 0 ? 'مجاني' : money(cart.delivery)),
          ],
        ),
      ),
    );
  }
}

class CartSummaryRow extends StatelessWidget {
  const CartSummaryRow({required this.label, required this.value, super.key});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: 7),
        child: Row(children: [Expanded(child: Text(label, style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.w800))), Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900))]),
      );
}

class CartItemCard extends StatelessWidget {
  const CartItemCard({required this.item, required this.cart, super.key});
  final CartItem item;
  final CartStore cart;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: softCard(context),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(width: 82, height: 82, padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .06), borderRadius: BorderRadius.circular(20)), child: AppImage(url: item.product.image, fit: BoxFit.contain)),
              const SizedBox(width: 12),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(item.product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15)),
                  const SizedBox(height: 4),
                  Text(item.product.categoryName.isEmpty ? 'منتج صيدلي' : item.product.categoryName, maxLines: 1, overflow: TextOverflow.ellipsis, style: mutedStyle(context, 12)),
                  const SizedBox(height: 7),
                  Text(money(item.product.price), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900)),
                ]),
              ),
              IconButton(onPressed: () => cart.remove(item.product), icon: const Icon(Icons.delete_outline_rounded), color: Colors.redAccent),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              QuantityStepper(value: item.qty, onChanged: (qty) => cart.setQty(item.product, qty)),
              const Spacer(),
              Text('الإجمالي ${money(item.total)}', style: const TextStyle(fontWeight: FontWeight.w900)),
            ],
          ),
        ],
      ),
    );
  }
}

class CartBenefits extends StatelessWidget {
  const CartBenefits({super.key});

  @override
  Widget build(BuildContext context) {
    const items = [
      [Icons.verified_user_outlined, 'منتجات أصلية'],
      [Icons.lock_outline_rounded, 'دفع آمن'],
      [Icons.support_agent_rounded, 'دعم الطلب'],
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Row(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                decoration: softCard(context),
                child: Column(children: [Icon(items[i][0] as IconData, color: Theme.of(context).colorScheme.primary), const SizedBox(height: 5), Text(items[i][1] as String, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 11))]),
              ),
            ),
            if (i != items.length - 1) const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({required this.api, super.key});
  final ApiClient api;

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final formKey = GlobalKey<FormState>();
  final name = TextEditingController();
  final phone = TextEditingController();
  final city = TextEditingController();
  final address = TextEditingController();
  final notes = TextEditingController();
  bool loading = false;

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('بيانات الطلب')),
      body: Form(
        key: formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            CheckoutStepHeader(total: cart.total, count: cart.count),
            const SizedBox(height: 16),
            field(name, 'اسم العميل', Icons.person_outline, required: true),
            field(phone, 'رقم الجوال', Icons.phone_outlined, keyboard: TextInputType.phone, required: true),
            field(city, 'المدينة', Icons.location_city_outlined),
            field(address, 'العنوان التفصيلي', Icons.location_on_outlined, maxLines: 3),
            field(notes, 'ملاحظات للصيدلية', Icons.note_alt_outlined, maxLines: 3),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(16),
        child: FilledButton.icon(
          onPressed: loading ? null : () => submit(cart),
          icon: loading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.check_circle_outline),
          label: const Text('تأكيد الطلب'),
        ),
      ),
    );
  }

  Widget field(TextEditingController controller, String label, IconData icon, {TextInputType? keyboard, int maxLines = 1, bool required = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboard,
        maxLines: maxLines,
        validator: (value) => required && (value == null || value.trim().isEmpty) ? 'مطلوب' : null,
        decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
      ),
    );
  }

  Future<void> submit(CartStore cart) async {
    if (!formKey.currentState!.validate()) return;
    setState(() => loading = true);
    try {
      final response = await widget.api.postJson('/orders', {
        'customer_name': name.text.trim(),
        'phone': phone.text.trim(),
        'city': city.text.trim(),
        'address': address.text.trim(),
        'notes': notes.text.trim(),
        'items': cart.items.map((item) => {'product_id': item.product.id, 'qty': item.qty}).toList(),
      });
      cart.clear();
      if (!mounted) return;
      Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => OrderSuccessScreen(order: response['data'] as Map<String, dynamic>)), (route) => route.isFirst);
    } catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.toString())));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }
}

class CheckoutStepHeader extends StatelessWidget {
  const CheckoutStepHeader({required this.total, required this.count, super.key});
  final double total;
  final int count;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(18),
        decoration: softCard(context),
        child: Row(children: [const CircleIcon(icon: Icons.receipt_long), const SizedBox(width: 12), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('إتمام الطلب', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)), Text('$count منتج - ${money(total)}', style: mutedStyle(context, 13))]))]),
      );
}

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({required this.api, super.key});
  final ApiClient api;

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  final phone = TextEditingController();
  Future<List<OrderSummary>>? future;

  @override
  void dispose() {
    phone.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async => load(),
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(child: OrdersHero(phone: phone, onSearch: load)),
            const SliverToBoxAdapter(child: OrdersStatusGuide()),
            if (future == null)
              SliverFillRemaining(
                hasScrollBody: false,
                child: OrdersPrompt(onBrowse: () => openProducts(context, widget.api)),
              )
            else
              FutureBuilder<List<OrderSummary>>(
                future: future,
                builder: (context, snapshot) {
                  if (snapshot.connectionState != ConnectionState.done) {
                    return const SliverFillRemaining(child: Center(child: CircularProgressIndicator()));
                  }
                  if (snapshot.hasError) {
                    return SliverFillRemaining(child: ErrorState(message: snapshot.error.toString(), onRetry: load));
                  }
                  final orders = snapshot.data ?? [];
                  if (orders.isEmpty) {
                    return SliverFillRemaining(
                      hasScrollBody: false,
                      child: OrdersEmpty(onBrowse: () => openProducts(context, widget.api)),
                    );
                  }
                  return SliverPadding(
                    padding: const EdgeInsets.fromLTRB(14, 4, 14, 96),
                    sliver: SliverList.separated(
                      itemCount: orders.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, index) => OrderCard(order: orders[index]),
                    ),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  Future<void> load() async {
    final value = phone.text.trim();
    if (value.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('اكتب رقم الجوال أولا')));
      return;
    }
    final nextFuture = widget.api.getJson('/orders', {'phone': value}).then((json) => (json['data'] as List).map((e) => OrderSummary.fromJson(e as Map<String, dynamic>)).toList());
    setState(() => future = nextFuture);
    await nextFuture;
  }
}

class OrdersHero extends StatelessWidget {
  const OrdersHero({required this.phone, required this.onSearch, super.key});
  final TextEditingController phone;
  final Future<void> Function() onSearch;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 12, 14, 18),
      decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(width: 50, height: 50, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.receipt_long_rounded, color: Colors.white)),
              const SizedBox(width: 12),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('طلباتي', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 28)), Text('تابع حالة طلباتك من الصيدلية لحظة بلحظة.', style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w700))])),
            ],
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(26)),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: phone,
                    keyboardType: TextInputType.phone,
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => onSearch(),
                    decoration: const InputDecoration(hintText: 'اكتب رقم الجوال لتتبع الطلبات', prefixIcon: Icon(Icons.phone_iphone_rounded)),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton.icon(onPressed: onSearch, icon: const Icon(Icons.search_rounded), label: const Text('تتبع')),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              OrderTrustPill(icon: Icons.verified_user_outlined, label: 'تحقق آمن'),
              OrderTrustPill(icon: Icons.local_shipping_outlined, label: 'تحديثات التوصيل'),
              OrderTrustPill(icon: Icons.support_agent_outlined, label: 'دعم الصيدلية'),
            ],
          ),
        ],
      ),
    );
  }
}

class OrderTrustPill extends StatelessWidget {
  const OrderTrustPill({required this.icon, required this.label, super.key});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .15), borderRadius: BorderRadius.circular(99)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [Icon(icon, color: Colors.white, size: 16), const SizedBox(width: 6), Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 12))]),
      );
}

class OrdersStatusGuide extends StatelessWidget {
  const OrdersStatusGuide({super.key});
  final steps = const [
    [Icons.inventory_2_outlined, 'استلام'],
    [Icons.medication_outlined, 'تحضير'],
    [Icons.local_shipping_outlined, 'توصيل'],
    [Icons.check_circle_outline, 'اكتمل'],
  ];

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: softCard(context),
          child: Row(
            children: [
              for (var i = 0; i < steps.length; i++) ...[
                Expanded(
                  child: Column(
                    children: [
                      CircleIcon(icon: steps[i][0] as IconData),
                      const SizedBox(height: 6),
                      Text(steps[i][1] as String, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900)),
                    ],
                  ),
                ),
                if (i != steps.length - 1) Container(width: 18, height: 2, color: Theme.of(context).colorScheme.primary.withValues(alpha: .25)),
              ],
            ],
          ),
        ),
      );
}

class OrdersPrompt extends StatelessWidget {
  const OrdersPrompt({required this.onBrowse, super.key});
  final VoidCallback onBrowse;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.manage_search_rounded, size: 82, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 14),
            const Text('ابدأ بتتبع طلبك', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text('اكتب رقم الجوال المستخدم في الطلب لعرض كل الطلبات والحالة الحالية.', textAlign: TextAlign.center, style: mutedStyle(context, 14)),
            const SizedBox(height: 18),
            OutlinedButton.icon(onPressed: onBrowse, icon: const Icon(Icons.add_shopping_cart_rounded), label: const Text('تسوق الآن')),
          ]),
        ),
      );
}

class OrdersEmpty extends StatelessWidget {
  const OrdersEmpty({required this.onBrowse, super.key});
  final VoidCallback onBrowse;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.assignment_late_outlined, size: 82, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 14),
            const Text('لا توجد طلبات لهذا الرقم', style: TextStyle(fontSize: 21, fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text('تأكد من رقم الجوال أو ابدأ طلب جديد من منتجات الصيدلية.', textAlign: TextAlign.center, style: mutedStyle(context, 14)),
            const SizedBox(height: 18),
            FilledButton.icon(onPressed: onBrowse, icon: const Icon(Icons.medication_liquid_outlined), label: const Text('تصفح المنتجات')),
          ]),
        ),
      );
}

class OrderCard extends StatelessWidget {
  const OrderCard({required this.order, super.key});
  final OrderSummary order;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(16),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [Expanded(child: Text('طلب #${order.id}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 19))), StatusChip(status: order.status)]),
            const SizedBox(height: 12),
            Row(children: [Icon(Icons.calendar_today_outlined, size: 16, color: Theme.of(context).colorScheme.primary), const SizedBox(width: 6), Text(order.createdAt, style: mutedStyle(context, 12))]),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(99),
              child: LinearProgressIndicator(value: orderProgress(order.status), minHeight: 8, backgroundColor: Theme.of(context).colorScheme.primary.withValues(alpha: .10)),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(child: OrderMetric(icon: Icons.shopping_bag_outlined, label: 'البنود', value: '${order.items.length}')),
                const SizedBox(width: 8),
                Expanded(child: OrderMetric(icon: Icons.payments_outlined, label: 'الإجمالي', value: money(order.total))),
              ],
            ),
          ],
        ),
      );
}

double orderProgress(String status) => switch (status) {
      'new' => .25,
      'preparing' || 'processing' => .50,
      'shipped' => .75,
      'completed' || 'delivered' => 1,
      'cancelled' => .10,
      _ => .25,
    };

class OrderMetric extends StatelessWidget {
  const OrderMetric({required this.icon, required this.label, required this.value, super.key});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .06), borderRadius: BorderRadius.circular(18)),
        child: Row(children: [Icon(icon, size: 18, color: Theme.of(context).colorScheme.primary), const SizedBox(width: 8), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label, style: mutedStyle(context, 11)), Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900))]))]),
      );
}

class StatusChip extends StatelessWidget {
  const StatusChip({required this.status, super.key});
  final String status;
  @override
  Widget build(BuildContext context) {
    final color = switch (status) { 'new' => Colors.blue, 'preparing' || 'processing' => Colors.orange, 'shipped' => Colors.purple, 'completed' || 'delivered' => Colors.green, 'cancelled' => Colors.red, _ => Colors.grey };
    final label = switch (status) { 'new' => 'جديد', 'preparing' || 'processing' => 'قيد التحضير', 'shipped' => 'قيد التوصيل', 'completed' || 'delivered' => 'مكتمل', 'cancelled' => 'ملغي', _ => status };
    return Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6), decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(99)), child: Text(label, style: TextStyle(color: color.shade700, fontWeight: FontWeight.w900, fontSize: 12)));
  }
}

class OrderSuccessScreen extends StatelessWidget {
  const OrderSuccessScreen({required this.order, super.key});
  final Map<String, dynamic> order;

  @override
  Widget build(BuildContext context) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.check_circle, size: 96, color: Theme.of(context).colorScheme.primary), const SizedBox(height: 16), const Text('تم إنشاء الطلب بنجاح', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)), const SizedBox(height: 8), Text('رقم الطلب #${order['id']} - الإجمالي ${money((order['total'] as num?)?.toDouble() ?? 0)}', textAlign: TextAlign.center), const SizedBox(height: 24), FilledButton(onPressed: () => Navigator.of(context).pop(), child: const Text('العودة للرئيسية'))]),
          ),
        ),
      );
}

class SectionBlock extends StatelessWidget {
  const SectionBlock({required this.title, required this.child, this.action, this.urgent = false, super.key});
  final String title;
  final Widget child;
  final String? action;
  final bool urgent;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(children: [Expanded(child: Text(title, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, color: urgent ? Colors.redAccent : null))), if (action != null) Text(action!, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900))]),
            ),
            const SizedBox(height: 12),
            child,
          ],
        ),
      );
}

class AppImage extends StatelessWidget {
  const AppImage({required this.url, this.fit = BoxFit.cover, super.key});
  final String url;
  final BoxFit fit;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) return fallback(context);
    return Image.network(url, fit: fit, loadingBuilder: (context, child, progress) => progress == null ? child : fallback(context), errorBuilder: (_, __, ___) => fallback(context));
  }

  Widget fallback(BuildContext context) => Container(decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .08), borderRadius: BorderRadius.circular(20)), child: Icon(Icons.medication_liquid, color: Theme.of(context).colorScheme.primary, size: 44));
}

class QuantityStepper extends StatelessWidget {
  const QuantityStepper({required this.value, required this.onChanged, super.key});
  final int value;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) => Row(mainAxisSize: MainAxisSize.min, children: [IconButton.filledTonal(onPressed: () => onChanged(value + 1), icon: const Icon(Icons.add)), Text('$value', style: const TextStyle(fontWeight: FontWeight.w900)), IconButton.filledTonal(onPressed: () => onChanged(value - 1), icon: const Icon(Icons.remove))]);
}

class CircleIcon extends StatelessWidget {
  const CircleIcon({required this.icon, super.key});
  final IconData icon;
  @override
  Widget build(BuildContext context) => Container(width: 44, height: 44, decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .10), borderRadius: BorderRadius.circular(16)), child: Icon(icon, color: Theme.of(context).colorScheme.primary));
}

BoxDecoration softCard(BuildContext context) => BoxDecoration(
      color: Theme.of(context).cardTheme.color,
      borderRadius: BorderRadius.circular(24),
      border: Border.all(color: Theme.of(context).brightness == Brightness.dark ? const Color(0xff1f2937) : const Color(0xffdbe7ef)),
      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: Theme.of(context).brightness == Brightness.dark ? 0 : .05), blurRadius: 24, offset: const Offset(0, 10))],
    );

TextStyle mutedStyle(BuildContext context, double size) => TextStyle(color: Theme.of(context).colorScheme.onSurface.withValues(alpha: .55), fontWeight: FontWeight.w700, fontSize: size);

class LoadingHome extends StatelessWidget {
  const LoadingHome({super.key});
  @override
  Widget build(BuildContext context) => ListView(padding: const EdgeInsets.all(16), children: [SizedBox(height: MediaQuery.paddingOf(context).top), skeleton(context, 120), const SizedBox(height: 16), skeleton(context, 220), const SizedBox(height: 16), ...List.generate(5, (_) => Padding(padding: const EdgeInsets.only(bottom: 12), child: skeleton(context, 90)))]);
  Widget skeleton(BuildContext context, double height) => Container(height: height, decoration: BoxDecoration(color: Theme.of(context).cardTheme.color, borderRadius: BorderRadius.circular(28)));
}

class ErrorState extends StatelessWidget {
  const ErrorState({required this.message, required this.onRetry, super.key});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.wifi_off_rounded, size: 56), const SizedBox(height: 12), Text(message, textAlign: TextAlign.center), const SizedBox(height: 16), FilledButton(onPressed: onRetry, child: const Text('إعادة المحاولة'))])));
}

class EmptyState extends StatelessWidget {
  const EmptyState({required this.title, required this.subtitle, super.key});
  final String title;
  final String subtitle;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.inventory_2_outlined, size: 64, color: Theme.of(context).colorScheme.primary), const SizedBox(height: 12), Text(title, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 20)), const SizedBox(height: 6), Text(subtitle, textAlign: TextAlign.center)])));
}

String money(double value) => '${NumberFormat('#,##0.00', 'ar_EG').format(value)} ج.م';
