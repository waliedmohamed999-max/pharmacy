import 'dart:async';
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
  OrderSummary({
    required this.id,
    required this.customerName,
    required this.phone,
    required this.status,
    required this.subtotal,
    required this.discount,
    required this.shipping,
    required this.total,
    required this.createdAt,
    required this.items,
  });
  final int id;
  final String customerName;
  final String phone;
  final String status;
  final double subtotal;
  final double discount;
  final double shipping;
  final double total;
  final String createdAt;
  final List<OrderLine> items;

  factory OrderSummary.fromJson(Map<String, dynamic> json) => OrderSummary(
        id: (json['id'] as num?)?.toInt() ?? 0,
        customerName: json['customer_name'] as String? ?? '',
        phone: json['phone'] as String? ?? '',
        status: json['status'] as String? ?? '',
        subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
        discount: (json['discount'] as num?)?.toDouble() ?? 0,
        shipping: (json['shipping'] as num?)?.toDouble() ?? 0,
        total: (json['total'] as num?)?.toDouble() ?? 0,
        createdAt: json['created_at'] as String? ?? '',
        items: (json['items'] as List? ?? []).map((item) => OrderLine.fromJson(item as Map<String, dynamic>)).toList(),
      );
}

class OrderLine {
  OrderLine({required this.productId, required this.name, required this.price, required this.qty, required this.lineTotal});
  final int productId;
  final String name;
  final double price;
  final int qty;
  final double lineTotal;

  factory OrderLine.fromJson(Map<String, dynamic> json) => OrderLine(
        productId: (json['product_id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        price: (json['price'] as num?)?.toDouble() ?? 0,
        qty: (json['qty'] as num?)?.toInt() ?? 0,
        lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
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
      HomeScreen(api: api, onToggleTheme: widget.onToggleTheme),
      ProductsScreen(api: api),
      CartScreen(api: api),
      OrdersScreen(api: api),
      SettingsScreen(
        api: api,
        onToggleTheme: widget.onToggleTheme,
        onOpenProducts: () => setState(() => index = 1),
        onOpenCart: () => setState(() => index = 2),
        onOpenOrders: () => setState(() => index = 3),
      ),
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
            const NavigationDestination(icon: Icon(Icons.settings_outlined), selectedIcon: Icon(Icons.settings), label: 'الإعدادات'),
          ],
        ),
      ),
    );
  }
}

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({
    required this.api,
    required this.onToggleTheme,
    required this.onOpenProducts,
    required this.onOpenCart,
    required this.onOpenOrders,
    super.key,
  });
  final ApiClient api;
  final VoidCallback onToggleTheme;
  final VoidCallback onOpenProducts;
  final VoidCallback onOpenCart;
  final VoidCallback onOpenOrders;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final name = TextEditingController();
  final phone = TextEditingController();
  final email = TextEditingController();
  final address = TextEditingController();
  final allergy = TextEditingController();
  final emergencyPhone = TextEditingController();
  final insurance = TextEditingController();
  bool saved = false;
  bool orderUpdates = true;
  bool offerAlerts = true;
  bool refillReminder = false;
  bool biometricLock = true;
  bool preciseLocation = false;
  bool dataSync = true;
  final Set<String> healthTags = {};

  @override
  void dispose() {
    name.dispose();
    phone.dispose();
    email.dispose();
    address.dispose();
    allergy.dispose();
    emergencyPhone.dispose();
    insurance.dispose();
    super.dispose();
  }

  int get completion {
    final fields = [name.text, phone.text, email.text, address.text, allergy.text, emergencyPhone.text, insurance.text];
    final filled = fields.where((value) => value.trim().isNotEmpty).length;
    return ((filled / fields.length) * 100).round();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: SettingsHero(completion: completion, saved: saved)),
          SliverToBoxAdapter(child: SettingsMemberCard(name: name.text, phone: phone.text, completion: completion)),
          SliverToBoxAdapter(child: SettingsProfileCard(name: name, phone: phone, email: email, address: address, allergy: allergy, emergencyPhone: emergencyPhone, insurance: insurance, onChanged: () => setState(() => saved = false))),
          SliverToBoxAdapter(
            child: SettingsHealthCard(
              selected: healthTags,
              onToggle: (tag) => setState(() {
                healthTags.contains(tag) ? healthTags.remove(tag) : healthTags.add(tag);
                saved = false;
              }),
            ),
          ),
          SliverToBoxAdapter(
            child: SettingsNotificationsCard(
              orderUpdates: orderUpdates,
              offerAlerts: offerAlerts,
              refillReminder: refillReminder,
              onOrderUpdates: (value) => setState(() => orderUpdates = value),
              onOfferAlerts: (value) => setState(() => offerAlerts = value),
              onRefillReminder: (value) => setState(() => refillReminder = value),
            ),
          ),
          SliverToBoxAdapter(
            child: SettingsSecurityCard(
              biometricLock: biometricLock,
              preciseLocation: preciseLocation,
              dataSync: dataSync,
              onBiometric: (value) => setState(() => biometricLock = value),
              onLocation: (value) => setState(() => preciseLocation = value),
              onDataSync: (value) => setState(() => dataSync = value),
            ),
          ),
          SliverToBoxAdapter(
            child: SettingsToolsGrid(
              onOpenProducts: widget.onOpenProducts,
              onOpenCart: widget.onOpenCart,
              onOpenOrders: widget.onOpenOrders,
              onToggleTheme: widget.onToggleTheme,
            ),
          ),
          const SliverToBoxAdapter(child: SettingsSupportCard()),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 110),
              child: FilledButton.icon(
                onPressed: () {
                  setState(() => saved = true);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم حفظ ملف العميل داخل التطبيق')));
                },
                icon: const Icon(Icons.verified_user_outlined),
                label: const Text('حفظ ملف العميل'),
                style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(56)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class SettingsHero extends StatelessWidget {
  const SettingsHero({required this.completion, required this.saved, super.key});
  final int completion;
  final bool saved;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(16, MediaQuery.paddingOf(context).top + 14, 16, 20),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(width: 58, height: 58, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(22)), child: const Icon(Icons.manage_accounts_outlined, color: Colors.white, size: 30)),
                const SizedBox(width: 12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('الإعدادات', style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w900)), Text('ملف العميل وأدوات إدارة تجربة الشراء.', style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w800))])),
                if (saved) Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99)), child: const Text('محفوظ', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900))),
              ],
            ),
            const SizedBox(height: 18),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: .13), borderRadius: BorderRadius.circular(24), border: Border.all(color: Colors.white.withValues(alpha: .16))),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [const Expanded(child: Text('اكتمال الملف', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900))), Text('$completion%', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900))]),
                  const SizedBox(height: 10),
                  ClipRRect(borderRadius: BorderRadius.circular(99), child: LinearProgressIndicator(value: completion / 100, minHeight: 8, color: Colors.white, backgroundColor: Colors.white.withValues(alpha: .20))),
                ],
              ),
            ),
          ],
        ),
      );
}

class SettingsMemberCard extends StatelessWidget {
  const SettingsMemberCard({required this.name, required this.phone, required this.completion, super.key});
  final String name;
  final String phone;
  final int completion;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(28),
            gradient: const LinearGradient(colors: [Color(0xffecfdf5), Color(0xffffffff)]),
            border: Border.all(color: const Color(0xffbbf7d0)),
          ),
          child: Row(
            children: [
              Container(
                width: 66,
                height: 66,
                decoration: const BoxDecoration(shape: BoxShape.circle, gradient: LinearGradient(colors: [Color(0xff059669), Color(0xff14b8a6)])),
                child: Center(child: Text(memberInitial(name), style: const TextStyle(color: Colors.white, fontSize: 25, fontWeight: FontWeight.w900))),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name.trim().isEmpty ? 'عميل الصيدلية' : name.trim(), maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 19)),
                    const SizedBox(height: 4),
                    Text(phone.trim().isEmpty ? 'أضف رقم الجوال لتسريع الطلب' : phone.trim(), style: mutedStyle(context, 12)),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        SettingsMiniBadge(icon: Icons.verified_user_outlined, label: completion >= 70 ? 'ملف موثق' : 'ملف قيد الإكمال'),
                        const SettingsMiniBadge(icon: Icons.local_shipping_outlined, label: 'شراء أسرع'),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
}

class SettingsMiniBadge extends StatelessWidget {
  const SettingsMiniBadge({required this.icon, required this.label, super.key});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .08), borderRadius: BorderRadius.circular(99)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [Icon(icon, size: 14, color: Theme.of(context).colorScheme.primary), const SizedBox(width: 5), Text(label, style: TextStyle(color: Theme.of(context).colorScheme.primary, fontSize: 11, fontWeight: FontWeight.w900))]),
      );
}

String memberInitial(String value) {
  final trimmed = value.trim();
  return trimmed.isEmpty ? 'ع' : trimmed.substring(0, 1);
}

class SettingsProfileCard extends StatelessWidget {
  const SettingsProfileCard({required this.name, required this.phone, required this.email, required this.address, required this.allergy, required this.emergencyPhone, required this.insurance, required this.onChanged, super.key});
  final TextEditingController name;
  final TextEditingController phone;
  final TextEditingController email;
  final TextEditingController address;
  final TextEditingController allergy;
  final TextEditingController emergencyPhone;
  final TextEditingController insurance;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('ملف العميل', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              const SizedBox(height: 4),
              Text('احفظ البيانات الأساسية لتسريع الطلبات ومتابعتها.', style: mutedStyle(context, 13)),
              const SizedBox(height: 14),
              SettingsField(controller: name, label: 'الاسم الكامل', icon: Icons.person_outline_rounded, onChanged: onChanged),
              SettingsField(controller: phone, label: 'رقم الجوال', icon: Icons.phone_iphone_rounded, keyboard: TextInputType.phone, onChanged: onChanged),
              SettingsField(controller: email, label: 'البريد الإلكتروني', icon: Icons.alternate_email_rounded, keyboard: TextInputType.emailAddress, onChanged: onChanged),
              SettingsField(controller: address, label: 'العنوان المختصر', icon: Icons.location_on_outlined, onChanged: onChanged),
              SettingsField(controller: allergy, label: 'حساسية أو ملاحظات صحية', icon: Icons.health_and_safety_outlined, onChanged: onChanged),
              SettingsField(controller: emergencyPhone, label: 'جوال الطوارئ', icon: Icons.emergency_outlined, keyboard: TextInputType.phone, onChanged: onChanged),
              SettingsField(controller: insurance, label: 'شركة التأمين أو رقم الوثيقة', icon: Icons.policy_outlined, onChanged: onChanged),
            ],
          ),
        ),
      );
}

class SettingsField extends StatelessWidget {
  const SettingsField({required this.controller, required this.label, required this.icon, required this.onChanged, this.keyboard, super.key});
  final TextEditingController controller;
  final String label;
  final IconData icon;
  final VoidCallback onChanged;
  final TextInputType? keyboard;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: TextField(
          controller: controller,
          keyboardType: keyboard,
          onChanged: (_) => onChanged(),
          decoration: InputDecoration(prefixIcon: Icon(icon), labelText: label),
        ),
      );
}

class SettingsHealthCard extends StatelessWidget {
  const SettingsHealthCard({required this.selected, required this.onToggle, super.key});
  final Set<String> selected;
  final ValueChanged<String> onToggle;

  @override
  Widget build(BuildContext context) {
    const tags = ['سكري', 'ضغط', 'حساسية', 'أطفال', 'جلدية', 'فيتامينات', 'قلب', 'نوم'];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                CircleIcon(icon: Icons.monitor_heart_outlined),
                SizedBox(width: 10),
                Expanded(child: Text('الملف الصحي', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20))),
              ],
            ),
            const SizedBox(height: 6),
            Text('اختيارات تساعد التطبيق يعرض منتجات وتنبيهات أقرب لاحتياجك.', style: mutedStyle(context, 13)),
            const SizedBox(height: 14),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final tag in tags)
                  FilterChip(
                    selected: selected.contains(tag),
                    label: Text(tag),
                    avatar: Icon(selected.contains(tag) ? Icons.check_rounded : Icons.add_rounded, size: 16),
                    onSelected: (_) => onToggle(tag),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class SettingsNotificationsCard extends StatelessWidget {
  const SettingsNotificationsCard({
    required this.orderUpdates,
    required this.offerAlerts,
    required this.refillReminder,
    required this.onOrderUpdates,
    required this.onOfferAlerts,
    required this.onRefillReminder,
    super.key,
  });
  final bool orderUpdates;
  final bool offerAlerts;
  final bool refillReminder;
  final ValueChanged<bool> onOrderUpdates;
  final ValueChanged<bool> onOfferAlerts;
  final ValueChanged<bool> onRefillReminder;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('تفضيلات ذكية', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              const SizedBox(height: 10),
              SettingsSwitch(icon: Icons.local_shipping_outlined, title: 'تحديثات الطلب والتوصيل', subtitle: 'إشعارات حالة الطلب لحظة بلحظة', value: orderUpdates, onChanged: onOrderUpdates),
              SettingsSwitch(icon: Icons.local_offer_outlined, title: 'عروض الصيدلية', subtitle: 'تنبيه بالعروض والخصومات المهمة', value: offerAlerts, onChanged: onOfferAlerts),
              SettingsSwitch(icon: Icons.event_repeat_outlined, title: 'تذكير إعادة الطلب', subtitle: 'مفيد للأدوية والمستلزمات المتكررة', value: refillReminder, onChanged: onRefillReminder),
            ],
          ),
        ),
      );
}

class SettingsSwitch extends StatelessWidget {
  const SettingsSwitch({required this.icon, required this.title, required this.subtitle, required this.value, required this.onChanged, super.key});
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) => SwitchListTile(
        contentPadding: EdgeInsets.zero,
        secondary: CircleIcon(icon: icon),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w900)),
        subtitle: Text(subtitle, style: mutedStyle(context, 12)),
        value: value,
        onChanged: onChanged,
      );
}

class SettingsSecurityCard extends StatelessWidget {
  const SettingsSecurityCard({
    required this.biometricLock,
    required this.preciseLocation,
    required this.dataSync,
    required this.onBiometric,
    required this.onLocation,
    required this.onDataSync,
    super.key,
  });
  final bool biometricLock;
  final bool preciseLocation;
  final bool dataSync;
  final ValueChanged<bool> onBiometric;
  final ValueChanged<bool> onLocation;
  final ValueChanged<bool> onDataSync;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('الأمان والخصوصية', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              const SizedBox(height: 10),
              SettingsSwitch(icon: Icons.fingerprint_rounded, title: 'قفل التطبيق بالبصمة', subtitle: 'جاهزية لحماية بيانات العميل', value: biometricLock, onChanged: onBiometric),
              SettingsSwitch(icon: Icons.location_searching_rounded, title: 'مشاركة الموقع للتوصيل', subtitle: 'يساعد في دقة عنوان التسليم', value: preciseLocation, onChanged: onLocation),
              SettingsSwitch(icon: Icons.cloud_sync_outlined, title: 'مزامنة آمنة مع الصيدلية', subtitle: 'ربط التفضيلات بالطلبات والداش بورد لاحقا', value: dataSync, onChanged: onDataSync),
            ],
          ),
        ),
      );
}

class SettingsToolsGrid extends StatelessWidget {
  const SettingsToolsGrid({required this.onOpenProducts, required this.onOpenCart, required this.onOpenOrders, required this.onToggleTheme, super.key});
  final VoidCallback onOpenProducts;
  final VoidCallback onOpenCart;
  final VoidCallback onOpenOrders;
  final VoidCallback onToggleTheme;

  @override
  Widget build(BuildContext context) {
    final tools = [
      SettingsTool('كل المنتجات', 'تصفح وفلترة', Icons.medication_liquid_outlined, onOpenProducts),
      SettingsTool('السلة', 'مراجعة الطلب', Icons.shopping_cart_outlined, onOpenCart),
      SettingsTool('طلباتي', 'تتبع بالجوال', Icons.receipt_long_outlined, onOpenOrders),
      SettingsTool('الوضع', 'فاتح / داكن', Icons.dark_mode_outlined, onToggleTheme),
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('أدوات سريعة', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
            const SizedBox(height: 12),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: tools.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, crossAxisSpacing: 10, mainAxisSpacing: 10, childAspectRatio: 1.35),
              itemBuilder: (context, index) => SettingsToolCard(tool: tools[index]),
            ),
          ],
        ),
      ),
    );
  }
}

class SettingsTool {
  const SettingsTool(this.title, this.subtitle, this.icon, this.onTap);
  final String title;
  final String subtitle;
  final IconData icon;
  final VoidCallback onTap;
}

class SettingsToolCard extends StatelessWidget {
  const SettingsToolCard({required this.tool, super.key});
  final SettingsTool tool;

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: tool.onTap,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .055), borderRadius: BorderRadius.circular(20)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleIcon(icon: tool.icon),
              const Spacer(),
              Text(tool.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900)),
              Text(tool.subtitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: mutedStyle(context, 11)),
            ],
          ),
        ),
      );
}

class SettingsSupportCard extends StatelessWidget {
  const SettingsSupportCard({super.key});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('مركز المساعدة', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              SizedBox(height: 12),
              SettingsActionRow(icon: Icons.support_agent_outlined, title: 'تواصل مع الصيدلية', subtitle: 'استفسار عن دواء أو طلب'),
              SettingsActionRow(icon: Icons.receipt_long_outlined, title: 'سياسة الطلب والاسترجاع', subtitle: 'تعليمات الشراء والتبديل'),
              SettingsActionRow(icon: Icons.privacy_tip_outlined, title: 'خصوصية البيانات الطبية', subtitle: 'حماية معلومات العميل الصحية'),
            ],
          ),
        ),
      );
}

class SettingsActionRow extends StatelessWidget {
  const SettingsActionRow({required this.icon, required this.title, required this.subtitle, super.key});
  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 9),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .045), borderRadius: BorderRadius.circular(18)),
        child: Row(
          children: [
            CircleIcon(icon: icon),
            const SizedBox(width: 10),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: const TextStyle(fontWeight: FontWeight.w900)), Text(subtitle, style: mutedStyle(context, 12))])),
            Icon(Icons.arrow_back_ios_new_rounded, size: 16, color: Theme.of(context).colorScheme.primary),
          ],
        ),
      );
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({required this.api, required this.onToggleTheme, super.key});
  final ApiClient api;
  final VoidCallback onToggleTheme;

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
          final cart = CartScope.of(context);

          return CustomScrollView(
            slivers: [
              SliverToBoxAdapter(child: StoreHeader(store: store, api: widget.api, onToggleTheme: widget.onToggleTheme)),
              SliverToBoxAdapter(child: HomeQuickActions(api: widget.api, cart: cart)),
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
  const StoreHeader({required this.store, required this.api, required this.onToggleTheme, super.key});
  final Map<String, dynamic> store;
  final ApiClient api;
  final VoidCallback onToggleTheme;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 10, 14, 14),
      decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                height: 50,
                width: 50,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)),
                child: const Icon(Icons.medication_liquid_rounded, color: Colors.white, size: 27),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(store['name'] as String? ?? 'صيدلية د. محمد رمضان', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 19)),
                    Text(store['tagline'] as String? ?? 'رعاية موثوقة وتسوق أسرع', maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .80), fontWeight: FontWeight.w700)),
                  ],
                ),
              ),
              IconButton.filledTonal(
                onPressed: onToggleTheme,
                icon: const Icon(Icons.dark_mode_outlined),
                style: IconButton.styleFrom(backgroundColor: Colors.white.withValues(alpha: .14), foregroundColor: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SearchBox(readOnly: true, onTap: () => openProducts(context, api)),
        ],
      ),
    );
  }
}

class HomeQuickActions extends StatelessWidget {
  const HomeQuickActions({required this.api, required this.cart, super.key});
  final ApiClient api;
  final CartStore cart;

  @override
  Widget build(BuildContext context) {
    final items = [
      _HomeAction('المنتجات', Icons.medication_liquid_outlined, () => openProducts(context, api)),
      _HomeAction('باركود', Icons.qr_code_scanner_rounded, () => openProducts(context, api)),
      _HomeAction('السلة ${cart.count}', Icons.shopping_cart_checkout_rounded, () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => CartScreen(api: api)))),
      _HomeAction('طلباتي', Icons.receipt_long_outlined, () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => OrdersScreen(api: api)))),
    ];

    return Container(
      color: const Color(0xff059669),
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
      child: Row(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            Expanded(child: _HomeActionChip(item: items[i])),
            if (i != items.length - 1) const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class _HomeAction {
  const _HomeAction(this.label, this.icon, this.onTap);
  final String label;
  final IconData icon;
  final VoidCallback onTap;
}

class _HomeActionChip extends StatelessWidget {
  const _HomeActionChip({required this.item});
  final _HomeAction item;

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: item.onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 11, horizontal: 6),
          decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(18), border: Border.all(color: Colors.white.withValues(alpha: .12))),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(item.icon, color: Colors.white, size: 21),
              const SizedBox(height: 5),
              Text(item.label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11)),
            ],
          ),
        ),
      );
}

class SearchBox extends StatelessWidget {
  const SearchBox({this.controller, this.onChanged, this.onTap, this.onClear, this.readOnly = false, super.key});
  final TextEditingController? controller;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onTap;
  final VoidCallback? onClear;
  final bool readOnly;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      onChanged: onChanged,
      onTap: onTap,
      readOnly: readOnly,
      decoration: InputDecoration(
        hintText: 'ابحث عن دواء، فيتامين، باركود أو منتج صحي',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _SearchTrailing(controller: controller, onClear: onClear, readOnly: readOnly),
      ),
    );
  }
}

class _SearchTrailing extends StatelessWidget {
  const _SearchTrailing({required this.controller, required this.onClear, required this.readOnly});
  final TextEditingController? controller;
  final VoidCallback? onClear;
  final bool readOnly;

  @override
  Widget build(BuildContext context) {
    if (readOnly || controller == null || onClear == null) {
      return const Icon(Icons.qr_code_scanner);
    }
    return ValueListenableBuilder<TextEditingValue>(
      valueListenable: controller!,
      builder: (context, value, _) {
        if (value.text.trim().isEmpty) return const Icon(Icons.qr_code_scanner);
        return IconButton(onPressed: onClear, icon: const Icon(Icons.close_rounded));
      },
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
      height: 260,
      child: PageView.builder(
        controller: PageController(viewportFraction: .93),
        itemCount: items.length,
        itemBuilder: (context, index) {
          final banner = items[index];
          return Container(
            margin: const EdgeInsets.fromLTRB(6, 14, 6, 8),
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
                  padding: const EdgeInsets.all(18),
                  child: Row(
                    children: [
                      Expanded(
                        flex: 7,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const PillLabel(label: 'منتجات أصلية 100%'),
                            const SizedBox(height: 10),
                            Text(banner['title'] as String? ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 27, fontWeight: FontWeight.w900, height: 1.08)),
                            const SizedBox(height: 8),
                            Text(banner['subtitle'] as String? ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.white.withValues(alpha: .88), fontWeight: FontWeight.w800, fontSize: 12, height: 1.45)),
                            const SizedBox(height: 12),
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
      height: 132,
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
      height: 88,
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
      width: 168,
      padding: const EdgeInsets.all(12),
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
        height: 148,
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
                width: 124,
                padding: const EdgeInsets.all(12),
                decoration: softCard(context),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(width: 54, height: 54, child: AppImage(url: category.image, fit: BoxFit.contain)),
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
  Timer? searchDebounce;
  String sort = 'newest';
  bool inStockOnly = false;
  bool grid = true;
  CategoryItem? selectedCategory;
  late Future<List<CategoryItem>> categoriesFuture;
  late Future<List<Product>> productsFuture;

  @override
  void dispose() {
    searchDebounce?.cancel();
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

  void scheduleSearch(String _) {
    searchDebounce?.cancel();
    searchDebounce = Timer(const Duration(milliseconds: 450), reload);
  }

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
          SliverToBoxAdapter(child: ProductsHeader(search: search, onSearch: scheduleSearch, grid: grid, onToggleGrid: () => setState(() => grid = !grid), selectedCategory: selectedCategory, onClear: () => setState(() { search.clear(); productsFuture = loadProducts(); }))),
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
  const ProductsHeader({required this.search, required this.onSearch, required this.grid, required this.onToggleGrid, required this.selectedCategory, required this.onClear, super.key});
  final TextEditingController search;
  final ValueChanged<String> onSearch;
  final bool grid;
  final VoidCallback onToggleGrid;
  final CategoryItem? selectedCategory;
  final VoidCallback onClear;

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
            SearchBox(controller: search, onChanged: onSearch, onClear: onClear),
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
  int selectedImage = 0;
  int qty = 1;

  @override
  void initState() {
    super.initState();
    future = widget.api.getJson('/products/${widget.product.slug}').then((json) => Product.fromJson(json['data'] as Map<String, dynamic>));
  }

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return Scaffold(
      body: FutureBuilder<Product>(
        future: future,
        builder: (context, snapshot) {
          final product = snapshot.data ?? widget.product;
          final images = product.images.isEmpty ? [product.image] : product.images;
          final activeImage = images[selectedImage.clamp(0, images.length - 1)];
          final maxQty = product.availableQty < 1 ? 1 : product.availableQty;
          if (qty > maxQty) qty = maxQty;
          return CustomScrollView(
            slivers: [
              SliverToBoxAdapter(child: ProductDetailsHero(product: product, image: activeImage, onBack: () => Navigator.of(context).pop())),
              SliverToBoxAdapter(
                child: ProductImageThumbs(
                  images: images,
                  selected: selectedImage,
                  onSelect: (index) => setState(() => selectedImage = index),
                ),
              ),
              SliverToBoxAdapter(child: ProductDetailsInfo(product: product)),
              SliverToBoxAdapter(
                child: ProductPurchasePanel(
                  product: product,
                  qty: qty,
                  onDecrease: () => setState(() => qty = (qty - 1).clamp(1, maxQty)),
                  onIncrease: () => setState(() => qty = (qty + 1).clamp(1, maxQty)),
                ),
              ),
              const SliverToBoxAdapter(child: ProductGuarantees()),
              const SliverToBoxAdapter(child: ProductPharmacyTools()),
              SliverToBoxAdapter(child: ProductDescriptionCard(product: product)),
              const SliverToBoxAdapter(child: SizedBox(height: 106)),
            ],
          );
        },
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(16),
        child: FutureBuilder<Product>(
          future: future,
          builder: (context, snapshot) {
            final product = snapshot.data ?? widget.product;
            return Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  decoration: softCard(context),
                  child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [Text('الإجمالي', style: mutedStyle(context, 11)), Text(money(product.price * qty), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900, fontSize: 17))]),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: product.inStock
                        ? () {
                            for (var i = 0; i < qty; i++) {
                              cart.add(product);
                            }
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('تمت إضافة $qty للسلة')));
                          }
                        : null,
                    icon: const Icon(Icons.add_shopping_cart),
                    label: Text(product.inStock ? 'إضافة للسلة' : 'غير متاح حاليا'),
                    style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(56)),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class ProductDetailsHero extends StatelessWidget {
  const ProductDetailsHero({required this.product, required this.image, required this.onBack, super.key});
  final Product product;
  final String image;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 10, 14, 18),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
        child: Column(
          children: [
            Row(
              children: [
                IconButton.filledTonal(
                  onPressed: onBack,
                  icon: const Icon(Icons.arrow_back_rounded),
                  style: IconButton.styleFrom(backgroundColor: Colors.white.withValues(alpha: .16), foregroundColor: Colors.white),
                ),
                const SizedBox(width: 8),
                Expanded(child: Text(product.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18))),
                Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7), decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(99)), child: const Text('Rx', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900))),
              ],
            ),
            const SizedBox(height: 14),
            Container(
              width: double.infinity,
              height: 260,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(30), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .14), blurRadius: 28, offset: const Offset(0, 14))]),
              child: Stack(
                children: [
                  Center(child: AppImage(url: image, fit: BoxFit.contain)),
                  if (product.discountPercent > 0)
                    Positioned(top: 0, right: 0, child: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7), decoration: BoxDecoration(color: Colors.redAccent, borderRadius: BorderRadius.circular(99)), child: Text('خصم ${product.discountPercent}%', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 11)))),
                ],
              ),
            ),
          ],
        ),
      );
}

class ProductImageThumbs extends StatelessWidget {
  const ProductImageThumbs({required this.images, required this.selected, required this.onSelect, super.key});
  final List<String> images;
  final int selected;
  final ValueChanged<int> onSelect;

  @override
  Widget build(BuildContext context) {
    if (images.length <= 1) return const SizedBox(height: 12);
    return SizedBox(
      height: 88,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        scrollDirection: Axis.horizontal,
        itemCount: images.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final active = selected == index;
          return InkWell(
            onTap: () => onSelect(index),
            borderRadius: BorderRadius.circular(18),
            child: Container(
              width: 74,
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: active ? Theme.of(context).colorScheme.primary.withValues(alpha: .10) : Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: active ? Theme.of(context).colorScheme.primary : const Color(0xffdbe7ef))),
              child: AppImage(url: images[index], fit: BoxFit.contain),
            ),
          );
        },
      ),
    );
  }
}

class ProductDetailsInfo extends StatelessWidget {
  const ProductDetailsInfo({required this.product, super.key});
  final Product product;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(product.name, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, height: 1.25)),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  Chip(label: Text(product.categoryName.isEmpty ? 'منتج صيدلي' : product.categoryName), avatar: const Icon(Icons.category_outlined, size: 17)),
                  Chip(label: Text(product.inStock ? 'متوفر ${product.availableQty}' : 'غير متاح'), avatar: const Icon(Icons.inventory_2_outlined, size: 17)),
                  if (product.discountPercent > 0) Chip(label: Text('خصم ${product.discountPercent}%'), avatar: const Icon(Icons.local_offer_outlined, size: 17)),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(money(product.price), style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, color: Theme.of(context).colorScheme.primary)),
                  const SizedBox(width: 10),
                  if (product.comparePrice != null && product.comparePrice! > product.price) Text(money(product.comparePrice!), style: const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey, fontWeight: FontWeight.w800)),
                ],
              ),
            ],
          ),
        ),
      );
}

class ProductPurchasePanel extends StatelessWidget {
  const ProductPurchasePanel({required this.product, required this.qty, required this.onDecrease, required this.onIncrease, super.key});
  final Product product;
  final int qty;
  final VoidCallback onDecrease;
  final VoidCallback onIncrease;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(width: 44, height: 44, decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .10), borderRadius: BorderRadius.circular(16)), child: Icon(Icons.shopping_basket_outlined, color: Theme.of(context).colorScheme.primary)),
                  const SizedBox(width: 10),
                  const Expanded(child: Text('تجهيز الشراء', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900))),
                  Text(product.inStock ? 'متاح الآن' : 'نفد المخزون', style: TextStyle(color: product.inStock ? Theme.of(context).colorScheme.primary : Colors.red, fontWeight: FontWeight.w900)),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(child: Text('الكمية المطلوبة', style: mutedStyle(context, 13))),
                  QuantityStepper(value: qty, onChanged: (value) => value > qty ? onIncrease() : onDecrease()),
                ],
              ),
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: const Color(0xffecfdf5), borderRadius: BorderRadius.circular(18)),
                child: Row(
                  children: [
                    const Icon(Icons.health_and_safety_outlined, color: Color(0xff059669)),
                    const SizedBox(width: 8),
                    Expanded(child: Text('يراجع الصيدلي الطلب قبل التحضير للتأكد من ملاءمة المنتج وتوفره.', style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.w800, height: 1.5))),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
}

class ProductGuarantees extends StatelessWidget {
  const ProductGuarantees({super.key});

  @override
  Widget build(BuildContext context) {
    const items = [
      [Icons.verified_user_outlined, 'أصلي'],
      [Icons.local_shipping_outlined, 'توصيل'],
      [Icons.support_agent_outlined, 'صيدلي'],
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: Row(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 8),
                decoration: softCard(context),
                child: Column(children: [Icon(items[i][0] as IconData, color: Theme.of(context).colorScheme.primary), const SizedBox(height: 5), Text(items[i][1] as String, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 12))]),
              ),
            ),
            if (i != items.length - 1) const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class ProductPharmacyTools extends StatelessWidget {
  const ProductPharmacyTools({super.key});

  @override
  Widget build(BuildContext context) {
    const tools = [
      (Icons.chat_bubble_outline_rounded, 'اسأل الصيدلي', 'استشارة قبل الطلب'),
      (Icons.qr_code_scanner_rounded, 'مسح بديل', 'ابحث بالباركود'),
      (Icons.notifications_active_outlined, 'تنبيه توفر', 'للمنتجات الناقصة'),
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('أدوات الصيدلية', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
            const SizedBox(height: 12),
            for (final tool in tools)
              Container(
                margin: const EdgeInsets.only(bottom: 8),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .045), borderRadius: BorderRadius.circular(18)),
                child: Row(
                  children: [
                    Container(width: 38, height: 38, decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .10), borderRadius: BorderRadius.circular(14)), child: Icon(tool.$1, color: Theme.of(context).colorScheme.primary, size: 20)),
                    const SizedBox(width: 10),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(tool.$2, style: const TextStyle(fontWeight: FontWeight.w900)), Text(tool.$3, style: mutedStyle(context, 12))])),
                    Icon(Icons.arrow_back_ios_new_rounded, size: 16, color: Theme.of(context).colorScheme.primary),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class ProductDescriptionCard extends StatelessWidget {
  const ProductDescriptionCard({required this.product, super.key});
  final Product product;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('وصف المنتج', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
            const SizedBox(height: 8),
            Text(product.description.isEmpty ? 'منتج صيدلي موثوق متاح للطلب من التطبيق، ويتم ربط الطلب مباشرة بلوحة التحكم والمخزون.' : product.description, style: const TextStyle(height: 1.8, fontWeight: FontWeight.w600)),
          ]),
        ),
      );
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
  void dispose() {
    name.dispose();
    phone.dispose();
    city.dispose();
    address.dispose();
    notes.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cart = CartScope.of(context);
    return Scaffold(
      body: Form(
        key: formKey,
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(child: CheckoutHero(total: cart.total, count: cart.count)),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: softCard(context),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('بيانات الاستلام', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 19)),
                      const SizedBox(height: 14),
                      field(name, 'اسم العميل', Icons.person_outline, required: true),
                      field(phone, 'رقم الجوال', Icons.phone_outlined, keyboard: TextInputType.phone, required: true),
                      field(city, 'المدينة', Icons.location_city_outlined),
                      field(address, 'العنوان التفصيلي', Icons.location_on_outlined, maxLines: 3),
                      field(notes, 'ملاحظات للصيدلية', Icons.note_alt_outlined, maxLines: 3),
                    ],
                  ),
                ),
              ),
            ),
            const SliverToBoxAdapter(child: CheckoutSafety()),
            const SliverToBoxAdapter(child: SizedBox(height: 96)),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.all(16),
        child: FilledButton.icon(
          onPressed: loading ? null : () => submit(cart),
          icon: loading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.check_circle_outline),
          label: Text('تأكيد الطلب - ${money(cart.total)}'),
          style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(56)),
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

class CheckoutHero extends StatelessWidget {
  const CheckoutHero({required this.total, required this.count, super.key});
  final double total;
  final int count;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(14, MediaQuery.paddingOf(context).top + 12, 14, 18),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
        child: Row(
          children: [
            Container(width: 54, height: 54, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.verified_outlined, color: Colors.white)),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('إتمام الطلب', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 27)), Text('$count قطعة - ${money(total)}', style: TextStyle(color: Colors.white.withValues(alpha: .84), fontWeight: FontWeight.w800))])),
          ],
        ),
      );
}

class CheckoutSafety extends StatelessWidget {
  const CheckoutSafety({super.key});

  @override
  Widget build(BuildContext context) {
    const items = [
      [Icons.lock_outline_rounded, 'بياناتك محمية'],
      [Icons.local_pharmacy_outlined, 'مراجعة صيدلي'],
      [Icons.local_shipping_outlined, 'توصيل موثوق'],
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Row(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            Expanded(
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 8),
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

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({required this.api, super.key});
  final ApiClient api;

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  final phone = TextEditingController();
  Future<List<OrderSummary>>? future;
  bool loading = false;

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
                      itemBuilder: (context, index) => OrderCard(order: orders[index], onOpen: () => openOrder(orders[index])),
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
    if (loading) return;
    final nextFuture = widget.api.getJson('/orders', {'phone': value}).then((json) => (json['data'] as List).map((e) => OrderSummary.fromJson(e as Map<String, dynamic>)).toList());
    setState(() {
      loading = true;
      future = nextFuture;
    });
    try {
      await nextFuture;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  void openOrder(OrderSummary order) {
    Navigator.of(context).push(MaterialPageRoute(
      builder: (_) => OrderDetailsScreen(api: widget.api, initialOrder: order, phone: phone.text.trim()),
    ));
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
  const OrderCard({required this.order, required this.onOpen, super.key});
  final OrderSummary order;
  final VoidCallback onOpen;

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
            const SizedBox(height: 10),
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton.icon(
                onPressed: onOpen,
                icon: const Icon(Icons.arrow_back_rounded),
                label: const Text('عرض تفاصيل الطلب'),
              ),
            ),
          ],
        ),
      );
}

class OrderDetailsScreen extends StatefulWidget {
  const OrderDetailsScreen({required this.api, required this.initialOrder, required this.phone, super.key});
  final ApiClient api;
  final OrderSummary initialOrder;
  final String phone;

  @override
  State<OrderDetailsScreen> createState() => _OrderDetailsScreenState();
}

class _OrderDetailsScreenState extends State<OrderDetailsScreen> {
  late Future<OrderSummary> future;

  @override
  void initState() {
    super.initState();
    future = load();
  }

  Future<OrderSummary> load() async {
    final json = await widget.api.getJson('/orders/${widget.initialOrder.id}', {'phone': widget.phone});
    return OrderSummary.fromJson(json['data'] as Map<String, dynamic>);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<OrderSummary>(
        future: future,
        initialData: widget.initialOrder,
        builder: (context, snapshot) {
          final order = snapshot.data ?? widget.initialOrder;
          if (snapshot.hasError && snapshot.connectionState == ConnectionState.done) {
            return ErrorState(
              message: snapshot.error.toString(),
              onRetry: () => setState(() => future = load()),
            );
          }
          return RefreshIndicator(
            onRefresh: () async {
              final next = load();
              setState(() => future = next);
              await next;
            },
            child: CustomScrollView(
              slivers: [
                SliverToBoxAdapter(child: OrderDetailsHero(order: order, loading: snapshot.connectionState != ConnectionState.done)),
                SliverToBoxAdapter(child: OrderDetailsTimeline(status: order.status)),
                SliverToBoxAdapter(child: OrderCustomerCard(order: order)),
                SliverToBoxAdapter(child: OrderItemsCard(items: order.items)),
                SliverToBoxAdapter(child: OrderPaymentCard(order: order)),
                const SliverToBoxAdapter(child: SizedBox(height: 110)),
              ],
            ),
          );
        },
      ),
    );
  }
}

class OrderDetailsHero extends StatelessWidget {
  const OrderDetailsHero({required this.order, required this.loading, super.key});
  final OrderSummary order;
  final bool loading;

  @override
  Widget build(BuildContext context) => Container(
        padding: EdgeInsets.fromLTRB(16, MediaQuery.paddingOf(context).top + 12, 16, 22),
        decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff052e2b), Color(0xff059669)])),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                IconButton.filledTonal(onPressed: () => Navigator.of(context).pop(), icon: const Icon(Icons.arrow_forward_rounded)),
                const Spacer(),
                StatusChip(status: order.status),
              ],
            ),
            const SizedBox(height: 18),
            Text('تفاصيل طلب #${order.id}', style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text('متابعة دقيقة لحالة الطلب وبنوده ومراجعة الدفع.', style: TextStyle(color: Colors.white.withValues(alpha: .82), fontWeight: FontWeight.w800)),
            const SizedBox(height: 18),
            Row(
              children: [
                Expanded(child: HeroMetric(icon: Icons.inventory_2_outlined, label: 'البنود', value: '${order.items.length}')),
                const SizedBox(width: 10),
                Expanded(child: HeroMetric(icon: Icons.payments_outlined, label: 'الإجمالي', value: money(order.total))),
              ],
            ),
            if (loading) ...[
              const SizedBox(height: 14),
              ClipRRect(
                borderRadius: BorderRadius.circular(99),
                child: LinearProgressIndicator(minHeight: 6, backgroundColor: Colors.white.withValues(alpha: .16), color: Colors.white),
              ),
            ],
          ],
        ),
      );
}

class HeroMetric extends StatelessWidget {
  const HeroMetric({required this.icon, required this.label, required this.value, super.key});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .13), borderRadius: BorderRadius.circular(22), border: Border.all(color: Colors.white.withValues(alpha: .15))),
        child: Row(
          children: [
            Container(width: 38, height: 38, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .14), borderRadius: BorderRadius.circular(14)), child: Icon(icon, color: Colors.white, size: 20)),
            const SizedBox(width: 10),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label, style: TextStyle(color: Colors.white.withValues(alpha: .75), fontWeight: FontWeight.w800, fontSize: 11)), Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 16))])),
          ],
        ),
      );
}

class OrderDetailsTimeline extends StatelessWidget {
  const OrderDetailsTimeline({required this.status, super.key});
  final String status;

  @override
  Widget build(BuildContext context) {
    final active = orderStepIndex(status);
    const steps = [
      (Icons.receipt_long_outlined, 'استلام الطلب'),
      (Icons.medication_outlined, 'تجهيز الدواء'),
      (Icons.local_shipping_outlined, 'التوصيل'),
      (Icons.check_circle_outline, 'اكتمال'),
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('مسار الطلب', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
            const SizedBox(height: 16),
            for (var i = 0; i < steps.length; i++)
              TimelineStep(
                icon: steps[i].$1,
                label: steps[i].$2,
                done: status == 'cancelled' ? false : i <= active,
                last: i == steps.length - 1,
              ),
            if (status == 'cancelled')
              Container(
                margin: const EdgeInsets.only(top: 10),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: Colors.red.withValues(alpha: .09), borderRadius: BorderRadius.circular(18)),
                child: const Row(children: [Icon(Icons.cancel_outlined, color: Colors.red), SizedBox(width: 8), Expanded(child: Text('تم إلغاء الطلب. تواصل مع الصيدلية إذا احتجت مساعدة.', style: TextStyle(fontWeight: FontWeight.w800, color: Colors.red)))]),
              ),
          ],
        ),
      ),
    );
  }
}

class TimelineStep extends StatelessWidget {
  const TimelineStep({required this.icon, required this.label, required this.done, required this.last, super.key});
  final IconData icon;
  final String label;
  final bool done;
  final bool last;

  @override
  Widget build(BuildContext context) {
    final color = done ? Theme.of(context).colorScheme.primary : Colors.grey;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(width: 40, height: 40, decoration: BoxDecoration(color: color.withValues(alpha: .12), shape: BoxShape.circle), child: Icon(done ? Icons.check_rounded : icon, color: color, size: 20)),
            if (!last) Container(width: 2, height: 28, color: color.withValues(alpha: .20)),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(label, style: TextStyle(fontWeight: FontWeight.w900, color: done ? null : Colors.grey.shade600)),
          ),
        ),
      ],
    );
  }
}

class OrderCustomerCard extends StatelessWidget {
  const OrderCustomerCard({required this.order, super.key});
  final OrderSummary order;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('بيانات العميل', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 12),
              SuccessInfoRow(icon: Icons.person_outline_rounded, label: 'الاسم', value: order.customerName.isEmpty ? 'عميل الصيدلية' : order.customerName),
              const Divider(height: 22),
              SuccessInfoRow(icon: Icons.phone_iphone_rounded, label: 'الجوال', value: order.phone.isEmpty ? '-' : order.phone),
              const Divider(height: 22),
              SuccessInfoRow(icon: Icons.schedule_outlined, label: 'تاريخ الطلب', value: order.createdAt.isEmpty ? '-' : order.createdAt),
            ],
          ),
        ),
      );
}

class OrderItemsCard extends StatelessWidget {
  const OrderItemsCard({required this.items, super.key});
  final List<OrderLine> items;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('بنود الطلب', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 12),
              if (items.isEmpty)
                Text('لا توجد بنود مسجلة لهذا الطلب.', style: mutedStyle(context, 13))
              else
                for (final item in items) OrderLineTile(item: item),
            ],
          ),
        ),
      );
}

class OrderLineTile extends StatelessWidget {
  const OrderLineTile({required this.item, super.key});
  final OrderLine item;

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .045), borderRadius: BorderRadius.circular(18)),
        child: Row(
          children: [
            Container(width: 42, height: 42, decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .10), borderRadius: BorderRadius.circular(15)), child: Icon(Icons.medication_liquid_outlined, color: Theme.of(context).colorScheme.primary)),
            const SizedBox(width: 10),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(item.name.isEmpty ? 'منتج صيدلي' : item.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w900)), const SizedBox(height: 4), Text('${item.qty} × ${money(item.price)}', style: mutedStyle(context, 12))])),
            const SizedBox(width: 8),
            Text(money(item.lineTotal), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class PriceLine extends StatelessWidget {
  const PriceLine({required this.label, required this.value, super.key});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          children: [
            Expanded(child: Text(label, style: mutedStyle(context, 13))),
            Text(value, style: const TextStyle(fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class OrderPaymentCard extends StatelessWidget {
  const OrderPaymentCard({required this.order, super.key});
  final OrderSummary order;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: softCard(context),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('ملخص الدفع', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 12),
              PriceLine(label: 'الإجمالي الفرعي', value: money(order.subtotal)),
              PriceLine(label: 'الخصم', value: money(order.discount)),
              PriceLine(label: 'التوصيل', value: order.shipping == 0 ? 'مجاني' : money(order.shipping)),
              const Divider(height: 24),
              Row(children: [const Expanded(child: Text('الإجمالي النهائي', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18))), Text(money(order.total), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w900, fontSize: 22))]),
            ],
          ),
        ),
      );
}

int orderStepIndex(String status) => switch (status) {
      'new' => 0,
      'preparing' || 'processing' => 1,
      'shipped' => 2,
      'completed' || 'delivered' => 3,
      _ => 0,
    };

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
  Widget build(BuildContext context) {
    final total = (order['total'] as num?)?.toDouble() ?? 0;
    final id = order['id']?.toString() ?? '-';
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: Container(
              padding: EdgeInsets.fromLTRB(18, MediaQuery.paddingOf(context).top + 28, 18, 28),
              decoration: const BoxDecoration(gradient: LinearGradient(colors: [Color(0xff064e3b), Color(0xff059669)])),
              child: Column(
                children: [
                  Container(width: 104, height: 104, decoration: BoxDecoration(color: Colors.white.withValues(alpha: .16), shape: BoxShape.circle), child: const Icon(Icons.check_circle_rounded, color: Colors.white, size: 70)),
                  const SizedBox(height: 18),
                  const Text('تم إنشاء الطلب بنجاح', textAlign: TextAlign.center, style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w900)),
                  const SizedBox(height: 8),
                  Text('تم إرسال طلبك للصيدلية وسيتم التواصل معك للتأكيد.', textAlign: TextAlign.center, style: TextStyle(color: Colors.white.withValues(alpha: .84), fontWeight: FontWeight.w700, height: 1.6)),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: softCard(context),
                child: Column(
                  children: [
                    SuccessInfoRow(icon: Icons.confirmation_number_outlined, label: 'رقم الطلب', value: '#$id'),
                    const Divider(height: 24),
                    SuccessInfoRow(icon: Icons.payments_outlined, label: 'الإجمالي', value: money(total)),
                    const Divider(height: 24),
                    const SuccessInfoRow(icon: Icons.schedule_outlined, label: 'الحالة', value: 'قيد المراجعة'),
                  ],
                ),
              ),
            ),
          ),
          const SliverToBoxAdapter(child: OrderSuccessSteps()),
          SliverFillRemaining(
            hasScrollBody: false,
            child: SafeArea(
              minimum: const EdgeInsets.all(16),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  FilledButton.icon(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.home_rounded),
                    label: const Text('العودة للرئيسية'),
                    style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(54)),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class SuccessInfoRow extends StatelessWidget {
  const SuccessInfoRow({required this.icon, required this.label, required this.value, super.key});
  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Row(
        children: [
          CircleIcon(icon: icon),
          const SizedBox(width: 12),
          Expanded(child: Text(label, style: mutedStyle(context, 13))),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
        ],
      );
}

class OrderSuccessSteps extends StatelessWidget {
  const OrderSuccessSteps({super.key});

  @override
  Widget build(BuildContext context) {
    const steps = [
      [Icons.receipt_long_outlined, 'استلام الطلب'],
      [Icons.local_pharmacy_outlined, 'مراجعة صيدلي'],
      [Icons.phone_in_talk_outlined, 'تأكيد هاتفي'],
      [Icons.local_shipping_outlined, 'التوصيل'],
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: softCard(context),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('ماذا يحدث الآن؟', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
            const SizedBox(height: 12),
            for (var i = 0; i < steps.length; i++) ...[
              Row(
                children: [
                  CircleIcon(icon: steps[i][0] as IconData),
                  const SizedBox(width: 12),
                  Expanded(child: Text(steps[i][1] as String, style: const TextStyle(fontWeight: FontWeight.w900))),
                ],
              ),
              if (i != steps.length - 1)
                Padding(
                  padding: const EdgeInsetsDirectional.only(start: 22),
                  child: Container(width: 2, height: 18, color: Theme.of(context).colorScheme.primary.withValues(alpha: .20)),
                ),
            ],
          ],
        ),
      ),
    );
  }
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
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary.withValues(alpha: .08), borderRadius: BorderRadius.circular(18)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(width: 34, height: 34, child: IconButton.filledTonal(onPressed: () => onChanged(value + 1), icon: const Icon(Icons.add, size: 17), padding: EdgeInsets.zero)),
            SizedBox(width: 34, child: Text('$value', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w900))),
            SizedBox(width: 34, height: 34, child: IconButton.filledTonal(onPressed: () => onChanged(value - 1), icon: const Icon(Icons.remove, size: 17), padding: EdgeInsets.zero)),
          ],
        ),
      );
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
  Widget build(BuildContext context) => ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SizedBox(height: MediaQuery.paddingOf(context).top),
          Row(children: [skeleton(context, 56, width: 56), const SizedBox(width: 12), Expanded(child: skeleton(context, 56))]),
          const SizedBox(height: 16),
          skeleton(context, 230),
          const SizedBox(height: 16),
          Row(children: [Expanded(child: skeleton(context, 82)), const SizedBox(width: 10), Expanded(child: skeleton(context, 82))]),
          const SizedBox(height: 16),
          ...List.generate(4, (_) => Padding(padding: const EdgeInsets.only(bottom: 12), child: skeleton(context, 96))),
        ],
      );
  Widget skeleton(BuildContext context, double height, {double? width}) => Container(width: width, height: height, decoration: BoxDecoration(color: Theme.of(context).cardTheme.color, borderRadius: BorderRadius.circular(28), border: Border.all(color: Theme.of(context).brightness == Brightness.dark ? const Color(0xff1f2937) : const Color(0xffdbe7ef))));
}

class ErrorState extends StatelessWidget {
  const ErrorState({required this.message, required this.onRetry, super.key});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Container(
            padding: const EdgeInsets.all(22),
            decoration: softCard(context),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.wifi_off_rounded, size: 64, color: Theme.of(context).colorScheme.primary),
              const SizedBox(height: 12),
              const Text('تعذر الاتصال', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              const SizedBox(height: 8),
              Text(message, textAlign: TextAlign.center, style: mutedStyle(context, 13)),
              const SizedBox(height: 16),
              FilledButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh_rounded), label: const Text('إعادة المحاولة')),
            ]),
          ),
        ),
      );
}

class EmptyState extends StatelessWidget {
  const EmptyState({required this.title, required this.subtitle, super.key});
  final String title;
  final String subtitle;
  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Container(
            padding: const EdgeInsets.all(22),
            decoration: softCard(context),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.inventory_2_outlined, size: 64, color: Theme.of(context).colorScheme.primary),
              const SizedBox(height: 12),
              Text(title, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 20)),
              const SizedBox(height: 6),
              Text(subtitle, textAlign: TextAlign.center, style: mutedStyle(context, 13)),
            ]),
          ),
        ),
      );
}

String money(double value) => '${NumberFormat('#,##0.00', 'ar_EG').format(value)} ج.م';
