import 'package:flutter/material.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/app_theme.dart';
import '../../home/home_search_screen.dart';
import '../../ecommerce/data/ecommerce_api_client.dart';
import '../../ecommerce/data/ecommerce_session_store.dart';
import '../../ecommerce/domain/ecommerce_models.dart';
import '../../ecommerce/presentation/ecommerce_cart_screen.dart';
import '../../ecommerce/presentation/ecommerce_home_screen.dart';
import '../../ecommerce/presentation/ecommerce_notifications_screen.dart';
import '../../ecommerce/presentation/ecommerce_order_history_screen.dart';
import '../../ecommerce/presentation/ecommerce_product_details_screen.dart';
import '../../ecommerce/presentation/ecommerce_remote_image.dart';
import '../../ecommerce/presentation/ecommerce_vendor_screen.dart';

const _medicalApiBaseUrl =
    'https://snow-grouse-381496.hostingersite.com/api/v1/medical';
const _medicalAppVersion =
    String.fromEnvironment('MEDICAL_APP_VERSION', defaultValue: 'dev');
const _medicalBg = Color(0xFF070807);
const _medicalPanel = Color(0xFF111211);
const _medicalCard = Color(0xFF181A18);
const _medicalLine = Color(0xFF242824);
const _medicalBlue = Color(0xFF2F7DFF);
const _medicalCyan = Color(0xFF27C7FF);
const _medicalText = Color(0xFFF7FAF3);
const _medicalMuted = Color(0xFF8D978C);

class MedicalHomeScreen extends StatefulWidget {
  const MedicalHomeScreen({super.key});

  @override
  State<MedicalHomeScreen> createState() => _MedicalHomeScreenState();
}

class _MedicalHomeScreenState extends State<MedicalHomeScreen> {
  late final EcommerceApiClient _api;
  final _sessionStore = EcommerceSessionStore();
  final _searchController = TextEditingController();
  late Future<EcommerceHomeData> _future;
  List<EcommerceProduct>? _searchResults;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: _medicalApiBaseUrl);
    _future = _api.fetchHome();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _medicalBg),
      appBar: AppBar(
        backgroundColor: adaptiveModuleHeaderTint(
          context,
          accent: _medicalBlue,
          darkBase: _medicalBg,
        ),
        foregroundColor: adaptiveText(context, _medicalText),
        surfaceTintColor: Colors.transparent,
        title: const Text('Medical'),
        titleTextStyle: TextStyle(
          color: adaptiveText(context, _medicalText),
          fontSize: 20,
          fontWeight: FontWeight.w900,
        ),
        iconTheme: IconThemeData(color: adaptiveText(context, _medicalText)),
        actionsIconTheme:
            IconThemeData(color: adaptiveText(context, _medicalText)),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            onPressed: _openNotifications,
            icon: const Icon(Icons.notifications_none_rounded),
          ),
          IconButton(
            tooltip: 'Menu',
            onPressed: _openOrders,
            icon: const Icon(Icons.menu_rounded),
          ),
        ],
      ),
      body: FutureBuilder<EcommerceHomeData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage(layout: AppSkeletonLayout.storefront);
          }
          if (snapshot.hasError) {
            return _MedicalMessage(
              icon: Icons.cloud_off_rounded,
              title: 'Medical backend is not connected.',
              detail: AppErrorState.userMessage(snapshot.error),
              actionLabel: 'Retry',
              onTap: _reload,
            );
          }

          final data = snapshot.data!;
          final forceUpdateRequired =
              data.config.forceUpdateVersion.isNotEmpty &&
                  data.config.forceUpdateVersion != _medicalAppVersion;
          if (data.config.maintenanceMode || forceUpdateRequired) {
            return _MedicalMessage(
              icon: Icons.info_outline_rounded,
              title: forceUpdateRequired ? 'Update required' : 'Maintenance',
              detail: forceUpdateRequired
                  ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                  : (data.config.maintenanceMessage.trim().isEmpty
                      ? 'Medical is temporarily unavailable.'
                      : data.config.maintenanceMessage),
            );
          }

          final products = _searchResults ?? data.latestProducts;
          final labTests = _labProducts(data);

          return RefreshIndicator(
            color: _medicalBlue,
            backgroundColor: adaptiveSurface(context, _medicalPanel),
            onRefresh: () async {
              _searchController.clear();
              setState(() {
                _searchResults = null;
                _future = _api.fetchHome();
              });
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
              children: [
                AdaptiveModuleHeaderBand(
                  accent: _medicalBlue,
                  darkBase: _medicalBg,
                  margin: const EdgeInsets.fromLTRB(-16, -8, -16, 0),
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                  child: Column(
                    children: [
                      _MedicalStatusBar(onCartTap: _openCart),
                      const SizedBox(height: 12),
                      _MedicalSearchField(
                        controller: _searchController,
                        onTap: _openSearch,
                      ),
                      const SizedBox(height: 14),
                      _MedicalActionRow(
                        onMedicines: () => _openProductList(
                          title: 'Medicines',
                          products: data.latestProducts,
                        ),
                        onTests: () => _openSearch(initialQuery: 'test'),
                        onPrescription: _openCart,
                        onHealthLog: _openOrders,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _PrescriptionBanner(onTap: _openCart),
                const SizedBox(height: 16),
                if (data.categories.isNotEmpty) ...[
                  const _MedicalSectionHeader(title: 'Shop by category'),
                  const SizedBox(height: 10),
                  _MedicalCategoryStrip(
                    categories: data.categories,
                    onTap: _openCategory,
                  ),
                  const SizedBox(height: 18),
                ],
                if (data.vendors.isNotEmpty) ...[
                  const _MedicalSectionHeader(
                    title: 'Pharmacies near you',
                    trailing: 'See all',
                  ),
                  const SizedBox(height: 10),
                  _PharmacyStrip(
                    vendors: data.vendors,
                    onTap: _openVendor,
                  ),
                  const SizedBox(height: 18),
                ],
                _MedicalSectionHeader(
                  title: _searchResults == null
                      ? 'Popular Lab Tests'
                      : 'Search Results',
                  trailing: _searchResults == null ? 'See all' : null,
                ),
                const SizedBox(height: 10),
                if (_searchResults == null)
                  _MedicalProductStrip(
                    products: labTests.isNotEmpty
                        ? labTests
                        : data.featuredProducts.take(6).toList(),
                    currency: data.config.currencySymbol,
                    onTap: _openProduct,
                  )
                else if (products.isEmpty)
                  const _MedicalMessage(
                    icon: Icons.medication_liquid_outlined,
                    title: 'No medicines found',
                    detail: 'Try searching with another medicine or test name.',
                  )
                else
                  _MedicalProductGrid(
                    products: products,
                    currency: data.config.currencySymbol,
                    onTap: _openProduct,
                  ),
                if (_searchResults == null) ...[
                  const SizedBox(height: 18),
                  const _MedicalSectionHeader(
                    title: 'Medicines',
                    trailing: 'See all',
                  ),
                  const SizedBox(height: 10),
                  _MedicalProductGrid(
                    products: data.latestProducts,
                    currency: data.config.currencySymbol,
                    onTap: _openProduct,
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  List<EcommerceProduct> _labProducts(EcommerceHomeData data) {
    final products = [
      ...data.featuredProducts,
      ...data.latestProducts,
      ...data.topRatedProducts,
    ];
    final seen = <int>{};
    return products.where((product) {
      if (!seen.add(product.id)) return false;
      final value =
          '${product.name} ${product.description} ${product.categoryId}'
              .toLowerCase();
      return value.contains('test') ||
          value.contains('panel') ||
          value.contains('checkup') ||
          value.contains('lab');
    }).toList();
  }

  void _reload() {
    setState(() {
      _searchResults = null;
      _future = _api.fetchHome();
    });
  }

  void _openSearch({String initialQuery = ''}) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => HomeSearchScreen(
          initialQuery: initialQuery,
          scope: HomeSearchScope.medical,
          apiBaseUrl: _medicalApiBaseUrl,
        ),
      ),
    );
  }

  void _openProduct(EcommerceProduct product) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductDetailsScreen(
          product: product,
          apiBaseUrl: _medicalApiBaseUrl,
        ),
      ),
    );
  }

  Future<void> _openCategory(EcommerceCategory category) async {
    if (category.subcategories.isNotEmpty) {
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (sheetContext) => _MedicalSubcategorySheet(
          category: category,
          onAllTap: () {
            Navigator.of(sheetContext).pop();
            _openCategoryProducts(category);
          },
          onTap: (subcategory) {
            Navigator.of(sheetContext).pop();
            _openSubcategory(subcategory);
          },
        ),
      );
      return;
    }
    await _openCategoryProducts(category);
  }

  Future<void> _openCategoryProducts(EcommerceCategory category) async {
    final products = await _api.fetchCategoryProducts(category.id);
    if (!mounted) return;
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => EcommerceProductListScreen(
        title: category.name,
        products: products,
        currency: '₹',
        onFetch: (query) =>
            _api.fetchCategoryProducts(category.id, query: query),
        onProductTap: _openProduct,
      ),
    ));
  }

  Future<void> _openSubcategory(EcommerceSubcategory subcategory) async {
    final products = await _api.fetchSubcategoryProducts(subcategory.id);
    if (!mounted) return;
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => EcommerceProductListScreen(
        title: subcategory.name,
        products: products,
        currency: '₹',
        onFetch: (query) =>
            _api.fetchSubcategoryProducts(subcategory.id, query: query),
        onProductTap: _openProduct,
      ),
    ));
  }

  void _openProductList({
    required String title,
    required List<EcommerceProduct> products,
  }) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductListScreen(
          title: title,
          products: products,
          currency: '₹',
          onProductTap: _openProduct,
        ),
      ),
    );
  }

  void _openVendor(EcommerceVendor vendor) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceVendorScreen(
          vendorId: vendor.id,
          vendorName: vendor.shopName,
          apiBaseUrl: _medicalApiBaseUrl,
        ),
      ),
    );
  }

  Future<void> _openCart() async {
    final session = await _sessionStore.load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceCartScreen(
          guestId: session.guestId,
          apiBaseUrl: _medicalApiBaseUrl,
        ),
      ),
    );
  }

  Future<void> _openOrders() async {
    final session = await _sessionStore.load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceOrderHistoryScreen(
          guestId: session.guestId,
          apiBaseUrl: _medicalApiBaseUrl,
          title: 'Medical Orders',
          emptyTitle: 'No medical orders yet.',
        ),
      ),
    );
  }

  Future<void> _openNotifications() async {
    final session = await _sessionStore.load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceNotificationsScreen(
          guestId: session.guestId,
          apiBaseUrl: _medicalApiBaseUrl,
        ),
      ),
    );
  }
}

class _MedicalCategoryStrip extends StatelessWidget {
  const _MedicalCategoryStrip({
    required this.categories,
    required this.onTap,
  });

  final List<EcommerceCategory> categories;
  final ValueChanged<EcommerceCategory> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return SizedBox(
      height: 92,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: categories.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final category = categories[index];
          return InkWell(
            onTap: () => onTap(category),
            borderRadius: BorderRadius.circular(16),
            child: Container(
              width: 82,
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: colors.line),
              ),
              child: Column(
                children: [
                  SizedBox(
                    width: 46,
                    height: 46,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: EcommerceRemoteImage(
                        url: category.imageUrl,
                        icon: Icons.medication_outlined,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    category.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MedicalSubcategorySheet extends StatelessWidget {
  const _MedicalSubcategorySheet({
    required this.category,
    required this.onAllTap,
    required this.onTap,
  });

  final EcommerceCategory category;
  final VoidCallback onAllTap;
  final ValueChanged<EcommerceSubcategory> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    final items = category.subcategories;
    return SafeArea(
      top: false,
      child: Container(
        constraints:
            BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * .68),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        decoration: BoxDecoration(
          color: colors.bg,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
          border: Border(top: BorderSide(color: colors.line)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(
                  color: colors.line,
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(category.name,
                style: TextStyle(
                    color: colors.text,
                    fontSize: 20,
                    fontWeight: FontWeight.w900)),
            const SizedBox(height: 4),
            Text('Choose a medicine section',
                style: TextStyle(
                    color: colors.muted, fontWeight: FontWeight.w700)),
            const SizedBox(height: 16),
            Flexible(
              child: GridView.builder(
                shrinkWrap: true,
                itemCount: items.length + 1,
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: .9,
                ),
                itemBuilder: (context, index) {
                  final isAll = index == 0;
                  final item = isAll ? null : items[index - 1];
                  return InkWell(
                    onTap: isAll ? onAllTap : () => onTap(item!),
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: colors.card,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                            color: isAll ? _medicalBlue : colors.line),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            width: 48,
                            height: 48,
                            child: isAll
                                ? const DecoratedBox(
                                    decoration: BoxDecoration(
                                      color: _medicalBlue,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(Icons.grid_view_rounded,
                                        color: Colors.white),
                                  )
                                : ClipRRect(
                                    borderRadius: BorderRadius.circular(14),
                                    child: EcommerceRemoteImage(
                                      url: item!.imageUrl,
                                      icon: Icons.medication_outlined,
                                      fit: BoxFit.cover,
                                    ),
                                  ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            isAll ? 'All ${category.name}' : item!.name,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: colors.text,
                              fontSize: 11,
                              height: 1.1,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MedicalStatusBar extends StatelessWidget {
  const _MedicalStatusBar({required this.onCartTap});

  final VoidCallback onCartTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          const Icon(Icons.circle, color: _medicalBlue, size: 8),
          const SizedBox(width: 9),
          Expanded(
            child: Text(
              '24/7 medicine delivery available',
              style: TextStyle(
                color: colors.text,
                fontWeight: FontWeight.w900,
                fontSize: 12,
              ),
            ),
          ),
          IconButton(
            tooltip: 'Medical cart',
            onPressed: onCartTap,
            icon: const Icon(Icons.shopping_bag_outlined),
            color: colors.text,
            visualDensity: VisualDensity.compact,
          ),
        ],
      ),
    );
  }
}

class _MedicalSearchField extends StatelessWidget {
  const _MedicalSearchField({
    required this.controller,
    required this.onTap,
  });

  final TextEditingController controller;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return TextField(
      controller: controller,
      readOnly: true,
      onTap: onTap,
      style: TextStyle(color: colors.text),
      decoration: InputDecoration(
        hintText: 'Search medicines, lab tests...',
        hintStyle: TextStyle(color: colors.muted),
        prefixIcon: Icon(Icons.search_rounded, color: colors.muted),
        suffixIcon: Icon(Icons.arrow_forward_rounded, color: colors.muted),
        filled: true,
        fillColor: colors.surface,
        enabledBorder: OutlineInputBorder(
          borderSide: BorderSide(color: colors.line),
          borderRadius: BorderRadius.circular(18),
        ),
        focusedBorder: OutlineInputBorder(
          borderSide: const BorderSide(color: _medicalBlue, width: 1.4),
          borderRadius: BorderRadius.circular(18),
        ),
      ),
    );
  }
}

class _MedicalActionRow extends StatelessWidget {
  const _MedicalActionRow({
    required this.onMedicines,
    required this.onTests,
    required this.onPrescription,
    required this.onHealthLog,
  });

  final VoidCallback onMedicines;
  final VoidCallback onTests;
  final VoidCallback onPrescription;
  final VoidCallback onHealthLog;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _MedicalAction(
          icon: Icons.medication_liquid_outlined,
          label: 'Medicines',
          onTap: onMedicines,
        ),
        _MedicalAction(
          icon: Icons.science_outlined,
          label: 'Lab Tests',
          onTap: onTests,
        ),
        _MedicalAction(
          icon: Icons.upload_file_outlined,
          label: 'Prescription',
          onTap: onPrescription,
        ),
        _MedicalAction(
          icon: Icons.favorite_border_rounded,
          label: 'Health Log',
          onTap: onHealthLog,
        ),
      ],
    );
  }
}

class _MedicalAction extends StatelessWidget {
  const _MedicalAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Column(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: colors.surface,
                shape: BoxShape.circle,
                border: Border.all(color: colors.line),
              ),
              child: Icon(icon, color: _medicalBlue, size: 22),
            ),
            const SizedBox(height: 7),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: colors.muted,
                fontSize: 10,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PrescriptionBanner extends StatelessWidget {
  const _PrescriptionBanner({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        height: 104,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: colors.line),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text(
                    'Upload Prescription',
                    style: TextStyle(
                      color: _medicalBlue,
                      fontWeight: FontWeight.w900,
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Get medicines delivered after pharmacist review',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.text,
                      fontWeight: FontWeight.w900,
                      fontSize: 15,
                      height: 1.15,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Prescription upload is available during checkout',
                    style: TextStyle(color: colors.muted, fontSize: 11),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: _medicalBlue.withValues(alpha: .14),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Icon(
                Icons.medical_information_outlined,
                color: _medicalCyan,
                size: 36,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PharmacyStrip extends StatelessWidget {
  const _PharmacyStrip({required this.vendors, required this.onTap});

  final List<EcommerceVendor> vendors;
  final ValueChanged<EcommerceVendor> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return SizedBox(
      height: 76,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: vendors.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final vendor = vendors[index];
          return InkWell(
            onTap: () => onTap(vendor),
            borderRadius: BorderRadius.circular(18),
            child: Container(
              width: 176,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: colors.line),
              ),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: _medicalBlue.withValues(alpha: .12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.local_pharmacy_outlined,
                        color: _medicalBlue),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          vendor.shopName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.text,
                            fontWeight: FontWeight.w900,
                            fontSize: 12,
                          ),
                        ),
                        Text(
                          vendor.city.isEmpty
                              ? 'Verified pharmacy'
                              : vendor.city,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.muted,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MedicalSectionHeader extends StatelessWidget {
  const _MedicalSectionHeader({required this.title, this.trailing});

  final String title;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _medicalText);
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: textColor,
              fontWeight: FontWeight.w900,
              fontSize: 15,
            ),
          ),
        ),
        if (trailing != null)
          Text(
            trailing!,
            style: const TextStyle(
              color: _medicalBlue,
              fontWeight: FontWeight.w900,
              fontSize: 11,
            ),
          ),
      ],
    );
  }
}

class _MedicalProductStrip extends StatelessWidget {
  const _MedicalProductStrip({
    required this.products,
    required this.currency,
    required this.onTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onTap;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) {
      return const _MedicalMessage(
        icon: Icons.science_outlined,
        title: 'No lab tests yet',
        detail: 'Lab test packages added from admin will appear here.',
      );
    }
    return SizedBox(
      height: 148,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: products.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final product = products[index];
          return SizedBox(
            width: 136,
            child: _MedicalProductCard(
              product: product,
              currency: currency,
              onTap: () => onTap(product),
              compact: true,
            ),
          );
        },
      ),
    );
  }
}

class _MedicalProductGrid extends StatelessWidget {
  const _MedicalProductGrid({
    required this.products,
    required this.currency,
    required this.onTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onTap;

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      itemCount: products.length,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: .78,
      ),
      itemBuilder: (context, index) {
        final product = products[index];
        return _MedicalProductCard(
          product: product,
          currency: currency,
          onTap: () => onTap(product),
        );
      },
    );
  }
}

class _MedicalProductCard extends StatelessWidget {
  const _MedicalProductCard({
    required this.product,
    required this.currency,
    required this.onTap,
    this.compact = false,
  });

  final EcommerceProduct product;
  final String currency;
  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: colors.surface,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Stack(
                  children: [
                    Positioned.fill(
                      child: EcommerceRemoteImage(
                        url: product.thumbnailUrl,
                        icon: product.requiresPrescription
                            ? Icons.assignment_outlined
                            : Icons.medication_liquid_outlined,
                        iconSize: compact ? 28 : 42,
                      ),
                    ),
                    if (product.requiresPrescription)
                      Positioned(
                        left: 6,
                        top: 6,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 7, vertical: 4),
                          decoration: BoxDecoration(
                            color: _medicalBlue,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: const Text(
                            'Rx',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              product.name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: colors.text,
                fontSize: 12,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              product.requiresPrescription
                  ? 'Prescription review'
                  : product.unit,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: colors.muted,
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              '$currency${product.sellingPrice.toStringAsFixed(0)}',
              style: const TextStyle(
                color: _medicalBlue,
                fontSize: 13,
                fontWeight: FontWeight.w900,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MedicalMessage extends StatelessWidget {
  const _MedicalMessage({
    required this.icon,
    required this.title,
    required this.detail,
    this.actionLabel,
    this.onTap,
  });

  final IconData icon;
  final String title;
  final String detail;
  final String? actionLabel;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _medicalBg,
      darkSurface: _medicalPanel,
      darkCard: _medicalCard,
      darkLine: _medicalLine,
      darkText: _medicalText,
      darkMuted: _medicalMuted,
    );
    return Center(
      child: Container(
        margin: const EdgeInsets.all(24),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: colors.line),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: colors.muted, size: 44),
            const SizedBox(height: 12),
            Text(
              title,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: colors.text,
                fontSize: 17,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              detail,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: colors.muted,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
            if (actionLabel != null && onTap != null) ...[
              const SizedBox(height: 14),
              FilledButton(onPressed: onTap, child: Text(actionLabel!)),
            ],
          ],
        ),
      ),
    );
  }
}
