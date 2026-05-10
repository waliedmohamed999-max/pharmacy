import 'dart:convert';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';

const apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000/api/mobile',
);

void main() {
  runApp(const PharmacyClientApp());
}

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
    final textTheme = ThemeData(brightness: Brightness.light).textTheme.apply(fontFamily: 'Cairo');

    return CartScope(
      cart: cart,
      child: MaterialApp(
        title: 'صيدلية د. محمد رمضان',
        debugShowCheckedModeBanner: false,
        locale: const Locale('ar'),
        themeMode: themeMode,
        theme: _theme(Brightness.light, textTheme),
        darkTheme: _theme(Brightness.dark, textTheme),
        builder: (context, child) => Directionality(
          textDirection: ui.TextDirection.rtl,
          child: child!,
        ),
        home: ClientShell(
          onToggleTheme: () => setState(() {
            themeMode = themeMode == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark;
          }),
        ),
      ),
    );
  }

  ThemeData _theme(Brightness brightness, TextTheme textTheme) {
    final isDark = brightness == Brightness.dark;
    final scheme = ColorScheme.fromSeed(
      seedColor: const Color(0xff079669),
      brightness: brightness,
      primary: const Color(0xff079669),
      secondary: const Color(0xff14b8a6),
      surface: isDark ? const Color(0xff0f172a) : Colors.white,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: isDark ? const Color(0xff07111f) : const Color(0xfff2f7fb),
      textTheme: textTheme.apply(
        bodyColor: isDark ? Colors.white : const Color(0xff0f172a),
        displayColor: isDark ? Colors.white : const Color(0xff0f172a),
      ),
      appBarTheme: const AppBarTheme(centerTitle: false, elevation: 0),
      cardTheme: CardThemeData(
        elevation: 0,
        color: isDark ? const Color(0xff111c2f) : Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      ),
    );
  }
}

class ApiClient {
  final http.Client _client = http.Client();

  Future<Map<String, dynamic>> getJson(String path, [Map<String, String>? query]) async {
    final uri = Uri.parse('$apiBaseUrl$path').replace(queryParameters: query);
    final response = await _client.get(uri, headers: {'Accept': 'application/json'});
    return _decode(response);
  }

  Future<Map<String, dynamic>> postJson(String path, Map<String, dynamic> body) async {
    final response = await _client.post(
      Uri.parse('$apiBaseUrl$path'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );
    return _decode(response);
  }

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 400) {
      final message = decoded['message'] ?? decoded['errors']?.toString() ?? 'حدث خطأ في الاتصال';
      throw ApiException(message.toString());
    }
    return decoded;
  }
}

class ApiException implements Exception {
  ApiException(this.message);
  final String message;
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
  });

  final int id;
  final String name;
  final String slug;
  final double price;
  final double? comparePrice;
  final String image;
  final int availableQty;
  final int discountPercent;
  final String description;
  final String categoryName;

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        slug: json['slug'] as String? ?? '',
        price: (json['price'] as num?)?.toDouble() ?? 0,
        comparePrice: (json['compare_price'] as num?)?.toDouble(),
        image: json['image'] as String? ?? '',
        availableQty: (json['available_qty'] as num?)?.toInt() ?? 0,
        discountPercent: (json['discount_percent'] as num?)?.toInt() ?? 0,
        description: (json['description'] as String?) ?? (json['short_description'] as String?) ?? '',
        categoryName: (json['category'] as Map<String, dynamic>?)?['name'] as String? ?? '',
      );
}

class CategoryItem {
  CategoryItem({required this.id, required this.name, required this.image, required this.count});
  final int id;
  final String name;
  final String image;
  final int count;

  factory CategoryItem.fromJson(Map<String, dynamic> json) => CategoryItem(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        image: json['image'] as String? ?? '',
        count: (json['products_count'] as num?)?.toInt() ?? 0,
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

  void add(Product product) {
    if (product.availableQty <= 0) return;
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
        body: pages[index],
        bottomNavigationBar: NavigationBar(
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
      onRefresh: () async => setState(() => future = widget.api.getJson('/home')),
      child: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) return const LoadingHome();
          if (snapshot.hasError) return ErrorState(message: snapshot.error.toString(), onRetry: () => setState(() => future = widget.api.getJson('/home')));

          final data = snapshot.data!;
          final store = data['store'] as Map<String, dynamic>;
          final banners = (data['banners'] as List).cast<Map<String, dynamic>>();
          final categories = (data['categories'] as List).map((e) => CategoryItem.fromJson(e as Map<String, dynamic>)).toList();
          final sections = data['sections'] as Map<String, dynamic>;
          final featured = _products(sections['featured']);
          final deals = _products(sections['deals']);
          final best = _products(sections['best_sellers']);
          final concerns = _maps(sections['concerns']);

          return CustomScrollView(
            slivers: [
              SliverToBoxAdapter(child: StoreHeader(store: store)),
              SliverToBoxAdapter(child: HeroCarousel(banners: banners)),
              const SliverToBoxAdapter(child: TrustBadges()),
              SliverToBoxAdapter(child: CategoryStrip(categories: categories)),
              SliverToBoxAdapter(child: ProductRail(title: 'عروض اليوم', products: deals, urgent: true)),
              SliverToBoxAdapter(child: ProductRail(title: 'منتجات مميزة', products: featured)),
              SliverToBoxAdapter(child: ProductRail(title: 'الأكثر مبيعًا', products: best)),
              const SliverToBoxAdapter(child: BrandCarousel()),
              SliverToBoxAdapter(child: ConcernGrid(concerns: concerns)),
              const SliverToBoxAdapter(child: TestimonialsStrip()),
              const SliverToBoxAdapter(child: AppDownloadBanner()),
              const SliverToBoxAdapter(child: SizedBox(height: 96)),
            ],
          );
        },
      ),
    );
  }

  List<Product> _products(dynamic raw) => (raw as List? ?? []).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
  List<Map<String, dynamic>> _maps(dynamic raw) => (raw as List? ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
}

class StoreHeader extends StatelessWidget {
  const StoreHeader({required this.store, super.key});
  final Map<String, dynamic> store;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 10, 14, 14),
      decoration: const BoxDecoration(
        gradient: LinearGradient(colors: [Color(0xff055f46), Color(0xff047857)]),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                height: 50,
                width: 50,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(18)),
                child: const Icon(Icons.medical_services_rounded, color: Colors.white),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      store['name'] as String? ?? '',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 20),
                    ),
                    Text(
                      store['tagline'] as String? ?? '',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(color: Colors.white.withValues(alpha: .78), fontWeight: FontWeight.w700),
                    ),
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
          TextField(
            decoration: InputDecoration(
              hintText: 'ابحث عن دواء، فيتامين، باركود أو منتج صحي',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: const Icon(Icons.qr_code_scanner),
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: BorderSide.none),
            ),
          ),
        ],
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
      height: 292,
      child: PageView.builder(
        padEnds: true,
        controller: PageController(viewportFraction: .94),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final banner = items[index];
          return LayoutBuilder(
            builder: (context, constraints) {
              final compact = constraints.maxWidth < 360;
              return Container(
                margin: const EdgeInsets.fromLTRB(6, 16, 6, 10),
                clipBehavior: Clip.antiAlias,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(30),
                  gradient: LinearGradient(
                    begin: Alignment.topRight,
                    end: Alignment.bottomLeft,
                    colors: index.isEven
                        ? const [Color(0xff065f46), Color(0xff059669), Color(0xff34d399)]
                        : const [Color(0xff075985), Color(0xff0f766e), Color(0xff14b8a6)],
                  ),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .10), blurRadius: 28, offset: const Offset(0, 14))],
                ),
                child: Stack(
                  children: [
                    Positioned(
                      left: -50,
                      top: -40,
                      child: Container(width: 160, height: 160, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: .12))),
                    ),
                    Positioned(
                      right: -70,
                      bottom: -80,
                      child: Container(width: 220, height: 220, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: .10))),
                    ),
                    Padding(
                      padding: EdgeInsets.all(compact ? 16 : 20),
                      child: Row(
                        children: [
                          Expanded(
                            flex: 6,
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99)),
                                  child: const Text(
                                    'منتجات أصلية 100%',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11),
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Text(
                                  banner['title'] as String? ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(color: Colors.white, fontSize: compact ? 25 : 31, fontWeight: FontWeight.w900, height: 1.06),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  banner['subtitle'] as String? ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(color: Colors.white.withValues(alpha: .88), fontWeight: FontWeight.w800, fontSize: compact ? 12 : 13, height: 1.45),
                                ),
                                const SizedBox(height: 14),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    FilledButton(
                                      onPressed: () {},
                                      style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xff065f46), visualDensity: VisualDensity.compact),
                                      child: const Text('تسوق الآن'),
                                    ),
                                    OutlinedButton(
                                      onPressed: () {},
                                      style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: BorderSide(color: Colors.white.withValues(alpha: .42)), visualDensity: VisualDensity.compact),
                                      child: const Text('العروض'),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            flex: 4,
                            child: Stack(
                              clipBehavior: Clip.none,
                              children: [
                                Container(
                                  height: compact ? 146 : 172,
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: .14),
                                    borderRadius: BorderRadius.circular(26),
                                  ),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(26),
                                    child: AppImage(url: banner['image'] as String? ?? '', fit: BoxFit.cover),
                                  ),
                                ),
                                Positioned(
                                  bottom: -12,
                                  right: 10,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .14), blurRadius: 18)]),
                                    child: const Column(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text('خصم حتى', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: Colors.grey)),
                                        Text('40%', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Color(0xffe11d48), height: 1)),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class TrustBadges extends StatelessWidget {
  const TrustBadges({super.key});
  final badges = const [
    (Icons.verified_user_outlined, 'منتجات أصلية'),
    (Icons.local_shipping_outlined, 'توصيل سريع'),
    (Icons.payment_outlined, 'دفع آمن'),
    (Icons.support_agent_outlined, 'دعم مستمر'),
  ];

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 82,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: badges.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final badge = badges[index];
          return Container(
            width: 170,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(22)),
            child: Row(children: [Icon(badge.$1, color: Theme.of(context).colorScheme.primary), const SizedBox(width: 10), Text(badge.$2, style: const TextStyle(fontWeight: FontWeight.w900))]),
          );
        },
      ),
    );
  }
}

class CategoryStrip extends StatelessWidget {
  const CategoryStrip({required this.categories, super.key});
  final List<CategoryItem> categories;

  @override
  Widget build(BuildContext context) {
    return SectionBlock(
      title: 'أقسام الصيدلية',
      child: SizedBox(
        height: 150,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          scrollDirection: Axis.horizontal,
          itemCount: categories.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) {
            final category = categories[index];
            return SizedBox(
              width: 126,
              child: InkWell(
                borderRadius: BorderRadius.circular(24),
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductsScreen(api: ApiClient(), category: category))),
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        Expanded(child: AppImage(url: category.image, fit: BoxFit.contain)),
                        const SizedBox(height: 8),
                        Text(category.name, maxLines: 2, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w900, height: 1.15)),
                        Text('${category.count} منتج', style: Theme.of(context).textTheme.labelSmall),
                      ],
                    ),
                  ),
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
        height: 290,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          scrollDirection: Axis.horizontal,
          itemCount: products.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) => ProductCard(product: products[index], width: 185),
        ),
      ),
    );
  }
}

class BrandCarousel extends StatelessWidget {
  const BrandCarousel({super.key});

  static const brands = ['Now', 'Sebamed', 'Accu-Chek', 'Mustela', 'Centrum', 'Vichy', 'La Roche-Posay', 'Bioderma'];

  @override
  Widget build(BuildContext context) {
    return SectionBlock(
      title: 'أشهر الماركات الطبية',
      child: SizedBox(
        height: 112,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          scrollDirection: Axis.horizontal,
          itemCount: brands.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) => Container(
            width: 150,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: .18)),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .04), blurRadius: 16, offset: const Offset(0, 8))],
            ),
            child: Text(brands[index], textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w900, color: Colors.grey, fontSize: 16)),
          ),
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
        ? const [
            {'title': 'المناعة', 'subtitle': 'دعم يومي لصحة أقوى'},
            {'title': 'السكري', 'subtitle': 'قياس ومتابعة ومنتجات أساسية'},
            {'title': 'ضغط الدم', 'subtitle': 'أجهزة ومنتجات متابعة منزلية'},
            {'title': 'العناية بالبشرة', 'subtitle': 'حلول طبية لبشرة صحية'},
          ]
        : concerns;

    return SectionBlock(
      title: 'تسوق حسب الاحتياج',
      child: GridView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisExtent: 142, crossAxisSpacing: 12, mainAxisSpacing: 12),
        itemCount: items.take(6).length,
        itemBuilder: (context, index) {
          final item = items[index];
          final colors = [
            const Color(0xffecfdf5),
            const Color(0xffeff6ff),
            const Color(0xfffff1f2),
            const Color(0xfffff7ed),
            const Color(0xfff5f3ff),
            const Color(0xfff0fdf4),
          ];
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(color: colors[index % colors.length], borderRadius: BorderRadius.circular(18)),
                    child: Icon(Icons.medication_liquid_outlined, color: Theme.of(context).colorScheme.primary),
                  ),
                  const Spacer(),
                  Text(item['title'] as String? ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 17)),
                  const SizedBox(height: 4),
                  Text(item['subtitle'] as String? ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.labelMedium?.copyWith(color: Colors.grey.shade600, fontWeight: FontWeight.w700)),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class TestimonialsStrip extends StatelessWidget {
  const TestimonialsStrip({super.key});

  static const reviews = [
    ('أحمد', 'تجربة شراء ممتازة والتوصيل كان سريع جدا.'),
    ('منى', 'المنتجات وصلت مغلفة ونظيفة والأسعار واضحة.'),
    ('كريم', 'واجهة سهلة والعروض واضحة. طلبت في دقائق.'),
  ];

  @override
  Widget build(BuildContext context) {
    return SectionBlock(
      title: 'ثقة يومية من عملائنا',
      child: SizedBox(
        height: 162,
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          scrollDirection: Axis.horizontal,
          itemCount: reviews.length,
          separatorBuilder: (_, __) => const SizedBox(width: 12),
          itemBuilder: (context, index) {
            final review = reviews[index];
            return Container(
              width: 280,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(24), border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: .16))),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(backgroundColor: const Color(0xffbbf7d0), child: Text(review.$1.substring(0, 1), style: const TextStyle(fontWeight: FontWeight.w900, color: Color(0xff065f46)))),
                      const SizedBox(width: 10),
                      Expanded(child: Text(review.$1, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16))),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(color: const Color(0xffe0f2fe), borderRadius: BorderRadius.circular(99)),
                        child: const Text('شراء موثق', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 10, color: Color(0xff0369a1))),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text('★★★★★', style: TextStyle(color: Color(0xffffb020), letterSpacing: 1.5)),
                  const SizedBox(height: 8),
                  Text(review.$2, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.grey.shade700, fontWeight: FontWeight.w700, height: 1.5)),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class AppDownloadBanner extends StatelessWidget {
  const AppDownloadBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 24, 16, 0),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(30),
        gradient: const LinearGradient(colors: [Color(0xff063f36), Color(0xff0f766e)]),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('تطبيق العملاء', style: TextStyle(color: Color(0xffa7f3d0), fontWeight: FontWeight.w900)),
                const SizedBox(height: 6),
                const Text('تسوق من الموبايل بسهولة', maxLines: 2, style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 24, height: 1.15)),
                const SizedBox(height: 10),
                Text('منتجات، عروض، سلة، وتتبع طلبات في تجربة واحدة.', style: TextStyle(color: Colors.white.withValues(alpha: .78), fontWeight: FontWeight.w700, height: 1.5)),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            width: 112,
            height: 186,
            padding: const EdgeInsets.all(5),
            decoration: BoxDecoration(
              color: const Color(0xff020617),
              borderRadius: BorderRadius.circular(30),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .22), blurRadius: 24, offset: const Offset(0, 14))],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(25),
              child: Container(
                color: const Color(0xfff4f8fb),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.fromLTRB(7, 9, 7, 7),
                      decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff065f46), Color(0xff10b981)])),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Container(width: 18, height: 18, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .18), borderRadius: BorderRadius.circular(8)), child: const Icon(Icons.medical_services, color: Colors.white, size: 11)),
                              const SizedBox(width: 5),
                              const Expanded(child: Text('صيدلية د. محمد رمضان', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white, fontSize: 6.5, fontWeight: FontWeight.w900))),
                            ],
                          ),
                          const SizedBox(height: 7),
                          Container(height: 17, alignment: Alignment.centerRight, padding: const EdgeInsets.symmetric(horizontal: 7), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)), child: const Text('ابحث عن منتج صحي', style: TextStyle(color: Colors.grey, fontSize: 5.5, fontWeight: FontWeight.w800))),
                        ],
                      ),
                    ),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.all(6),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Container(
                              height: 58,
                              padding: const EdgeInsets.all(7),
                              decoration: BoxDecoration(borderRadius: BorderRadius.circular(16), gradient: const LinearGradient(colors: [Color(0xff065f46), Color(0xff34d399)])),
                              child: const Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('منتجات أصلية 100%', style: TextStyle(color: Colors.white, fontSize: 5.5, fontWeight: FontWeight.w900)),
                                  SizedBox(height: 3),
                                  Text('عروض الصيدلية', maxLines: 1, style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w900, height: 1)),
                                  Spacer(),
                                  Icon(Icons.medication_liquid, color: Colors.white, size: 18),
                                ],
                              ),
                            ),
                            const SizedBox(height: 6),
                            const Row(children: [
                              Expanded(child: _MiniAppPill(label: 'أصلية', icon: Icons.verified_user_outlined)),
                              SizedBox(width: 5),
                              Expanded(child: _MiniAppPill(label: 'توصيل', icon: Icons.local_shipping_outlined)),
                            ]),
                            const SizedBox(height: 6),
                            const Align(alignment: Alignment.centerRight, child: Text('أقسام الصيدلية', style: TextStyle(fontSize: 7, fontWeight: FontWeight.w900))),
                            const SizedBox(height: 5),
                            const Row(children: [
                              Expanded(child: _MiniCategory(label: 'أدوية')),
                              SizedBox(width: 5),
                              Expanded(child: _MiniCategory(label: 'بشرة')),
                              SizedBox(width: 5),
                              Expanded(child: _MiniCategory(label: 'طفل')),
                            ]),
                            const SizedBox(height: 6),
                            const Expanded(
                              child: Row(children: [
                                Expanded(child: _MiniProduct()),
                                SizedBox(width: 5),
                                Expanded(child: _MiniProduct(price: '69 ج.م')),
                              ]),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniAppPill extends StatelessWidget {
  const _MiniAppPill({required this.label, required this.icon});
  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 24,
      padding: const EdgeInsets.symmetric(horizontal: 5),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 9, color: const Color(0xff047857)),
          const SizedBox(width: 3),
          Flexible(child: Text(label, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 5.5, fontWeight: FontWeight.w900))),
        ],
      ),
    );
  }
}

class _MiniCategory extends StatelessWidget {
  const _MiniCategory({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 34,
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(11)),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.medical_services, color: Color(0xff047857), size: 13),
          const SizedBox(height: 2),
          Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 5.5, fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }
}

class _MiniProduct extends StatelessWidget {
  const _MiniProduct({this.price = '48 ج.م'});
  final String price;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(5),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Container(
              width: double.infinity,
              decoration: BoxDecoration(color: const Color(0xffecfdf5), borderRadius: BorderRadius.circular(9)),
              child: const Icon(Icons.medication_liquid, color: Color(0xff047857), size: 15),
            ),
          ),
          const SizedBox(height: 3),
          const Text('منتج صيدلي', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 5.5, fontWeight: FontWeight.w900)),
          Text(price, maxLines: 1, style: const TextStyle(fontSize: 6, fontWeight: FontWeight.w900, color: Color(0xff04799b))),
        ],
      ),
    );
  }
}

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({required this.api, this.category, super.key});
  final ApiClient api;
  final CategoryItem? category;

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  late Future<List<Product>> future;
  final searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<List<Product>> load() async {
    final query = <String, String>{'per_page': '60'};
    if (widget.category != null) query['category_id'] = '${widget.category!.id}';
    if (searchController.text.trim().isNotEmpty) query['q'] = searchController.text.trim();
    final json = await widget.api.getJson('/products', query);
    return (json['data'] as List).map((e) => Product.fromJson(e as Map<String, dynamic>)).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.category?.name ?? 'كل المنتجات')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: searchController,
              onSubmitted: (_) => setState(() => future = load()),
              decoration: InputDecoration(
                hintText: 'ابحث بالاسم أو SKU أو الباركود',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(icon: const Icon(Icons.tune), onPressed: () {}),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(18)),
              ),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<Product>>(
              future: future,
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
                if (snapshot.hasError) return ErrorState(message: snapshot.error.toString(), onRetry: () => setState(() => future = load()));
                final products = snapshot.data ?? [];
                if (products.isEmpty) return const EmptyState(title: 'لا توجد منتجات', subtitle: 'جرّب البحث بكلمة أخرى.');
                return GridView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, mainAxisExtent: 292, crossAxisSpacing: 12, mainAxisSpacing: 12),
                  itemCount: products.length,
                  itemBuilder: (context, index) => ProductCard(product: products[index]),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class ProductCard extends StatelessWidget {
  const ProductCard({required this.product, this.width, super.key});
  final Product product;
  final double? width;

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return SizedBox(
      width: width,
      child: Card(
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProductDetailsScreen(product: product, api: ApiClient()))),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Stack(
                    children: [
                      Positioned.fill(child: AppImage(url: product.image, fit: BoxFit.contain)),
                      if (product.discountPercent > 0)
                        Positioned(
                          top: 0,
                          right: 0,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                            decoration: BoxDecoration(color: Colors.redAccent, borderRadius: BorderRadius.circular(99)),
                            child: Text('خصم ${product.discountPercent}%', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11)),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                Text(product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, height: 1.25)),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Text(money(product.price), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900, fontSize: 16)),
                    if (product.comparePrice != null) ...[
                      const SizedBox(width: 6),
                      Text(money(product.comparePrice!), style: const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey, fontSize: 11)),
                    ],
                  ],
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: product.availableQty > 0 ? () => cart.add(product) : null,
                    icon: const Icon(Icons.add_shopping_cart, size: 18),
                    label: Text(product.availableQty > 0 ? 'أضف للسلة' : 'غير متاح'),
                  ),
                ),
              ],
            ),
          ),
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
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: SizedBox(height: 320, child: Padding(padding: const EdgeInsets.all(20), child: AppImage(url: product.image, fit: BoxFit.contain))),
              ),
              const SizedBox(height: 18),
              Text(product.name, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text(product.categoryName, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w800)),
              const SizedBox(height: 14),
              Text(money(product.price), style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, color: Theme.of(context).colorScheme.primary)),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  Chip(label: Text(product.availableQty > 0 ? 'متوفر ${product.availableQty}' : 'غير متاح')),
                  if (product.discountPercent > 0) Chip(label: Text('خصم ${product.discountPercent}%')),
                  const Chip(label: Text('منتج أصلي')),
                ],
              ),
              const SizedBox(height: 18),
              Text(product.description.isEmpty ? 'منتج صيدلي موثوق متاح للطلب من التطبيق.' : product.description, style: const TextStyle(height: 1.8, fontWeight: FontWeight.w600)),
            ],
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(16),
        child: FilledButton.icon(
          onPressed: widget.product.availableQty > 0 ? () => cart.add(widget.product) : null,
          icon: const Icon(Icons.add_shopping_cart),
          label: const Text('إضافة للسلة'),
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
        appBar: AppBar(title: const Text('سلة المشتريات')),
        body: cart.items.isEmpty
            ? const EmptyState(title: 'السلة فارغة', subtitle: 'أضف منتجاتك المفضلة ثم أكمل الطلب.')
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: cart.items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  final item = cart.items[index];
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Row(
                        children: [
                          SizedBox(width: 72, height: 72, child: AppImage(url: item.product.image)),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Text(item.product.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900)),
                              Text(money(item.product.price), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900)),
                            ]),
                          ),
                          QuantityStepper(value: item.qty, onChanged: (qty) => cart.setQty(item.product, qty)),
                        ],
                      ),
                    ),
                  );
                },
              ),
        bottomNavigationBar: cart.items.isEmpty
            ? null
            : SafeArea(
                minimum: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Expanded(child: Text('الإجمالي\n${money(cart.subtotal)}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18))),
                    Expanded(
                      child: FilledButton(
                        onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CheckoutScreen(api: api))),
                        child: const Text('إتمام الطلب'),
                      ),
                    ),
                  ],
                ),
              ),
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
            _field(name, 'اسم العميل', Icons.person_outline),
            _field(phone, 'رقم الجوال', Icons.phone_outlined, keyboard: TextInputType.phone),
            _field(city, 'المدينة', Icons.location_city_outlined),
            _field(address, 'العنوان التفصيلي', Icons.location_on_outlined, maxLines: 3),
            Card(
              child: ListTile(
                title: const Text('إجمالي الطلب', style: TextStyle(fontWeight: FontWeight.w900)),
                subtitle: Text('${cart.count} منتج'),
                trailing: Text(money(cart.subtotal), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900, fontSize: 18)),
              ),
            ),
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

  Widget _field(TextEditingController controller, String label, IconData icon, {TextInputType? keyboard, int maxLines = 1}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboard,
        maxLines: maxLines,
        validator: (value) => (value == null || value.trim().isEmpty) && (label.contains('اسم') || label.contains('جوال')) ? 'مطلوب' : null,
        decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon), border: OutlineInputBorder(borderRadius: BorderRadius.circular(18))),
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
        'items': cart.items.map((item) => {'product_id': item.product.id, 'qty': item.qty}).toList(),
      });
      cart.clear();
      if (!mounted) return;
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => OrderSuccessScreen(order: response['data'] as Map<String, dynamic>)),
        (route) => route.isFirst,
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error is ApiException ? error.message : error.toString())));
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }
}

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({required this.api, super.key});
  final ApiClient api;

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  final phone = TextEditingController();
  Future<List<dynamic>>? future;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('طلباتي')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: phone,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: 'رقم الجوال',
                prefixIcon: const Icon(Icons.phone),
                suffixIcon: IconButton(icon: const Icon(Icons.search), onPressed: load),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(18)),
              ),
              onSubmitted: (_) => load(),
            ),
          ),
          Expanded(
            child: future == null
                ? const EmptyState(title: 'تتبع الطلبات', subtitle: 'اكتب رقم الجوال لعرض طلباتك.')
                : FutureBuilder<List<dynamic>>(
                    future: future,
                    builder: (context, snapshot) {
                      if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
                      if (snapshot.hasError) return ErrorState(message: snapshot.error.toString(), onRetry: load);
                      final orders = snapshot.data ?? [];
                      if (orders.isEmpty) return const EmptyState(title: 'لا توجد طلبات', subtitle: 'لا يوجد طلبات مسجلة لهذا الرقم.');
                      return ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: orders.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final order = orders[index] as Map<String, dynamic>;
                          return Card(
                            child: ListTile(
                              title: Text('طلب #${order['id']}', style: const TextStyle(fontWeight: FontWeight.w900)),
                              subtitle: Text('${order['status']} - ${order['created_at'] ?? ''}'),
                              trailing: Text(money((order['total'] as num?)?.toDouble() ?? 0), style: const TextStyle(fontWeight: FontWeight.w900)),
                            ),
                          );
                        },
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  void load() {
    final value = phone.text.trim();
    if (value.isEmpty) return;
    setState(() {
      future = widget.api.getJson('/orders', {'phone': value}).then((json) => json['data'] as List<dynamic>);
    });
  }
}

class OrderSuccessScreen extends StatelessWidget {
  const OrderSuccessScreen({required this.order, super.key});
  final Map<String, dynamic> order;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.check_circle, size: 96, color: Theme.of(context).colorScheme.primary),
              const SizedBox(height: 16),
              const Text('تم إنشاء الطلب بنجاح', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text('رقم الطلب #${order['id']} - الإجمالي ${money((order['total'] as num?)?.toDouble() ?? 0)}', textAlign: TextAlign.center),
              const SizedBox(height: 24),
              FilledButton(onPressed: () => Navigator.of(context).pop(), child: const Text('العودة للرئيسية')),
            ],
          ),
        ),
      ),
    );
  }
}

class SectionBlock extends StatelessWidget {
  const SectionBlock({required this.title, required this.child, this.urgent = false, super.key});
  final String title;
  final Widget child;
  final bool urgent;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(title, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, color: urgent ? Colors.redAccent : null)),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class AppImage extends StatelessWidget {
  const AppImage({required this.url, this.fit = BoxFit.cover, super.key});
  final String url;
  final BoxFit fit;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) {
      return Container(
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .08), borderRadius: BorderRadius.circular(20)),
        child: Icon(Icons.medication_liquid, color: Theme.of(context).colorScheme.primary, size: 54),
      );
    }

    return Image.network(
      url,
      fit: fit,
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return Container(color: Theme.of(context).colorScheme.primary.withValues(alpha: .06));
      },
      errorBuilder: (_, __, ___) => Icon(Icons.medication_liquid, color: Theme.of(context).colorScheme.primary, size: 48),
    );
  }
}

class QuantityStepper extends StatelessWidget {
  const QuantityStepper({required this.value, required this.onChanged, super.key});
  final int value;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        IconButton.filledTonal(onPressed: () => onChanged(value + 1), icon: const Icon(Icons.add)),
        Text('$value', style: const TextStyle(fontWeight: FontWeight.w900)),
        IconButton.filledTonal(onPressed: () => onChanged(value - 1), icon: const Icon(Icons.remove)),
      ],
    );
  }
}

class LoadingHome extends StatelessWidget {
  const LoadingHome({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        SizedBox(height: MediaQuery.paddingOf(context).top),
        Container(height: 120, decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(28))),
        const SizedBox(height: 16),
        Container(height: 220, decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(28))),
        const SizedBox(height: 16),
        ...List.generate(4, (_) => Padding(padding: const EdgeInsets.only(bottom: 12), child: Container(height: 90, decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(24))))),
      ],
    );
  }
}

class ErrorState extends StatelessWidget {
  const ErrorState({required this.message, required this.onRetry, super.key});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded, size: 56),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(onPressed: onRetry, child: const Text('إعادة المحاولة')),
          ],
        ),
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({required this.title, required this.subtitle, super.key});
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.inventory_2_outlined, size: 64, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 12),
            Text(title, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
            const SizedBox(height: 6),
            Text(subtitle, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}

String money(double value) => '${NumberFormat('#,##0.00', 'ar_EG').format(value)} ج.م';
