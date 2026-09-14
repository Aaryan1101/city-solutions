import 'package:flutter/material.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/app_theme.dart';
import '../../home/home_search_screen.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_i18n.dart';
import '../data/ecommerce_session_store.dart';
import '../domain/ecommerce_models.dart';
import 'ecommerce_cart_screen.dart';
import 'ecommerce_notifications_screen.dart';
import 'ecommerce_order_history_screen.dart';
import 'ecommerce_product_details_screen.dart';
import 'ecommerce_remote_image.dart';
import 'ecommerce_vendor_screen.dart';

const _ecommerceAppVersion =
    String.fromEnvironment('ECOMMERCE_APP_VERSION', defaultValue: 'dev');
const _shopBg = Color(0xFF070807);
const _shopPanel = Color(0xFF111211);
const _shopCard = Color(0xFF181A18);
const _shopLine = Color(0xFF242824);
const _shopOrange = Color(0xFFFF7A1A);
const _shopText = Color(0xFFF7FAF3);
const _shopMuted = Color(0xFF8D978C);

class EcommerceHomeScreen extends StatefulWidget {
  const EcommerceHomeScreen({
    super.key,
    this.title = 'E-Commerce',
    this.apiBaseUrl,
    this.appVersion = _ecommerceAppVersion,
  });

  final String title;
  final String? apiBaseUrl;
  final String appVersion;

  @override
  State<EcommerceHomeScreen> createState() => _EcommerceHomeScreenState();
}

class _EcommerceHomeScreenState extends State<EcommerceHomeScreen> {
  late Future<EcommerceHomeData> _future;
  late final EcommerceApiClient _api;
  final _sessionStore = EcommerceSessionStore();
  final _searchController = TextEditingController();
  List<EcommerceProduct>? _searchResults;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchHome();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: EcommerceLocaleController.locale,
      builder: (context, locale, _) => Scaffold(
        backgroundColor: adaptiveScaffold(context, _shopBg),
        appBar: AppBar(
          backgroundColor: adaptiveModuleHeaderTint(
            context,
            accent: _shopOrange,
            darkBase: _shopBg,
          ),
          foregroundColor: adaptiveText(context, _shopText),
          surfaceTintColor: Colors.transparent,
          titleTextStyle: TextStyle(
            color: adaptiveText(context, _shopText),
            fontSize: 20,
            fontWeight: FontWeight.w900,
          ),
          iconTheme: IconThemeData(color: adaptiveText(context, _shopText)),
          actionsIconTheme:
              IconThemeData(color: adaptiveText(context, _shopText)),
          title: Text(
            widget.title == 'E-Commerce'
                ? EcommerceI18n.text('ecommerce', locale)
                : widget.title,
          ),
          actions: [
            IconButton(
              tooltip: EcommerceI18n.text('notifications', locale),
              onPressed: _openNotifications,
              icon: const Icon(Icons.notifications_none_rounded),
            ),
            IconButton(
              tooltip: EcommerceI18n.text('orders', locale),
              onPressed: _openOrders,
              icon: const Icon(Icons.receipt_long_outlined),
            ),
            IconButton(
              tooltip: EcommerceI18n.text('cart', locale),
              onPressed: _openCart,
              icon: const Icon(Icons.shopping_cart_outlined),
            ),
          ],
        ),
        body: FutureBuilder<EcommerceHomeData>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const AppSkeletonPage(
                layout: AppSkeletonLayout.storefront,
              );
            }

            if (snapshot.hasError) {
              return _EcommerceErrorState(
                message: 'Backend is not connected yet.',
                detail: AppErrorState.userMessage(snapshot.error),
                onRetry: () => setState(() => _future = _api.fetchHome()),
              );
            }

            final data = snapshot.data!;
            final moduleName = widget.title == 'E-Commerce'
                ? EcommerceI18n.text('ecommerce', locale)
                : widget.title;
            final forceUpdateRequired =
                data.config.forceUpdateVersion.isNotEmpty &&
                    data.config.forceUpdateVersion != widget.appVersion;
            if (data.config.maintenanceMode || forceUpdateRequired) {
              return _EcommerceBlockingState(
                title: forceUpdateRequired ? 'Update required' : 'Maintenance',
                detail: forceUpdateRequired
                    ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                    : (data.config.maintenanceMessage.trim().isEmpty
                        ? 'The $moduleName module is temporarily unavailable.'
                        : data.config.maintenanceMessage),
              );
            }
            final products = _searchResults ?? data.latestProducts;

            return RefreshIndicator(
              onRefresh: () async {
                setState(() {
                  _searchResults = null;
                  _searchController.clear();
                  _future = _api.fetchHome();
                });
                await _future;
              },
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
                children: [
                  if (data.config.latestAppVersion.isNotEmpty &&
                      data.config.latestAppVersion != widget.appVersion) ...[
                    _VersionNotice(
                      currentVersion: widget.appVersion,
                      latestVersion: data.config.latestAppVersion,
                    ),
                    const SizedBox(height: 12),
                  ],
                  _EcommerceStorefrontHeader(
                    search: _SearchField(
                      controller: _searchController,
                      hintText: 'Search products, brands...',
                      onTap: _openSearch,
                    ),
                    categories: _CategoryStrip(
                      categories: data.categories,
                      onTap: _openCategory,
                    ),
                  ),
                  const SizedBox(height: 14),
                  _BannerStrip(banners: data.banners),
                  if (_searchResults == null) ...[
                    const SizedBox(height: 18),
                    if (data.vendors.isNotEmpty) ...[
                      _SectionTitle(
                          title: 'Stores near you',
                          trailing: '${data.vendors.length} active'),
                      const SizedBox(height: 10),
                      _VendorStrip(
                        vendors: data.vendors,
                        onTap: _openVendorStore,
                      ),
                      const SizedBox(height: 18),
                    ],
                  ],
                  if (data.featuredProducts.isNotEmpty &&
                      _searchResults == null) ...[
                    const _SectionTitle(
                        title: 'Trending Now', trailing: 'See all'),
                    const SizedBox(height: 10),
                    _HorizontalProducts(
                      products: data.featuredProducts,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                    ),
                    const SizedBox(height: 22),
                  ],
                  if (data.flashDealProducts.isNotEmpty &&
                      _searchResults == null) ...[
                    _FlashSaleDealCard(
                      products: data.flashDealProducts,
                      currency: data.config.currencySymbol,
                      onTap: () => _openProductList(
                        'Flash Sale',
                        data.flashDealProducts,
                        data.config.currencySymbol,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _HorizontalProducts(
                      products: data.flashDealProducts,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                    ),
                    const SizedBox(height: 22),
                  ],
                  if (data.clearanceProducts.isNotEmpty &&
                      _searchResults == null) ...[
                    _SectionTitle(
                        title: EcommerceI18n.text('clearance_sale', locale)),
                    const SizedBox(height: 10),
                    _HorizontalProducts(
                      products: data.clearanceProducts,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                    ),
                    const SizedBox(height: 22),
                  ],
                  if (_searchResults == null &&
                      data.topRatedProducts.isNotEmpty) ...[
                    _SectionTitle(
                        title: EcommerceI18n.text('top_rated', locale),
                        trailing: 'See all'),
                    const SizedBox(height: 10),
                    _HorizontalProducts(
                      products: data.topRatedProducts,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                    ),
                    const SizedBox(height: 22),
                  ],
                  if (_searchResults == null &&
                      data.bestSellingProducts.isNotEmpty) ...[
                    _SectionTitle(
                        title: EcommerceI18n.text('best_selling', locale),
                        trailing: 'See all'),
                    const SizedBox(height: 10),
                    _HorizontalProducts(
                      products: data.bestSellingProducts,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                    ),
                    const SizedBox(height: 22),
                  ],
                  if (_searchResults == null && data.brands.isNotEmpty) ...[
                    _SectionTitle(
                        title: 'Popular brands',
                        trailing: '${data.brands.length} active'),
                    const SizedBox(height: 10),
                    _BrandStrip(
                      brands: data.brands,
                      onTap: _openBrand,
                    ),
                    const SizedBox(height: 22),
                  ],
                  _SectionTitle(
                    title: _searchResults == null
                        ? 'Recently Viewed'
                        : EcommerceI18n.text('search_results', locale),
                    trailing: '${products.length} items',
                  ),
                  const SizedBox(height: 10),
                  if (products.isEmpty)
                    const _EmptyProducts()
                  else
                    _ProductGrid(
                      products: products,
                      currency: data.config.currencySymbol,
                      onTap: _openProduct,
                      onVendorTap: _openVendor,
                    ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  void _openSearch() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => HomeSearchScreen(
          scope: HomeSearchScope.ecommerce,
          apiBaseUrl: widget.apiBaseUrl,
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
        builder: (sheetContext) => _EcommerceSubcategorySheet(
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
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductListScreen(
          title: category.name,
          products: products,
          currency: '₹',
          onFetch: (query) =>
              _api.fetchCategoryProducts(category.id, query: query),
          onProductTap: _openProduct,
          onVendorTap: _openVendor,
        ),
      ),
    );
  }

  Future<void> _openSubcategory(EcommerceSubcategory subcategory) async {
    final products = await _api.fetchSubcategoryProducts(subcategory.id);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductListScreen(
          title: subcategory.name,
          products: products,
          currency: '₹',
          onFetch: (query) =>
              _api.fetchSubcategoryProducts(subcategory.id, query: query),
          onProductTap: _openProduct,
          onVendorTap: _openVendor,
        ),
      ),
    );
  }

  Future<void> _openBrand(EcommerceBrand brand) async {
    final products = await _api.fetchBrandProducts(brand.id);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductListScreen(
          title: brand.name,
          products: products,
          currency: '₹',
          onFetch: (query) => _api.fetchBrandProducts(brand.id, query: query),
          onProductTap: _openProduct,
          onVendorTap: _openVendor,
        ),
      ),
    );
  }

  void _openProductList(
    String title,
    List<EcommerceProduct> products,
    String currency,
  ) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductListScreen(
          title: title,
          products: products,
          currency: currency,
          onProductTap: _openProduct,
          onVendorTap: _openVendor,
        ),
      ),
    );
  }

  void _openProduct(EcommerceProduct product) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceProductDetailsScreen(
          product: product,
          apiBaseUrl: widget.apiBaseUrl,
        ),
      ),
    );
  }

  void _openVendor(EcommerceProduct product) {
    if (product.vendorId <= 0) return;
    _openVendorStore(
      EcommerceVendor(
        id: product.vendorId,
        shopName: product.vendorName ?? 'Store',
        ownerName: '',
        phone: '',
        email: '',
        address: '',
        city: '',
      ),
    );
  }

  void _openVendorStore(EcommerceVendor vendor) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => EcommerceVendorScreen(
          vendorId: vendor.id,
          vendorName: vendor.shopName,
          apiBaseUrl: widget.apiBaseUrl,
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
          apiBaseUrl: widget.apiBaseUrl,
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
          apiBaseUrl: widget.apiBaseUrl,
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
          apiBaseUrl: widget.apiBaseUrl,
        ),
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  const _SearchField({
    required this.controller,
    required this.hintText,
    required this.onTap,
  });

  final TextEditingController controller;
  final String hintText;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return TextField(
      controller: controller,
      readOnly: true,
      onTap: onTap,
      style: TextStyle(color: colors.text, fontWeight: FontWeight.w700),
      decoration: InputDecoration(
        hintText: hintText,
        prefixIcon: const Icon(Icons.search_rounded),
        suffixIcon: const Icon(Icons.arrow_forward_rounded),
        filled: true,
        fillColor: colors.surface,
        hintStyle: TextStyle(color: colors.muted),
        prefixIconColor: colors.muted,
        suffixIconColor: colors.muted,
        enabledBorder: OutlineInputBorder(
          borderSide: BorderSide(color: colors.line),
          borderRadius: BorderRadius.circular(18),
        ),
        focusedBorder: OutlineInputBorder(
          borderSide: const BorderSide(color: _shopOrange, width: 1.4),
          borderRadius: BorderRadius.circular(18),
        ),
      ),
    );
  }
}

class _EcommerceStorefrontHeader extends StatelessWidget {
  const _EcommerceStorefrontHeader({
    required this.search,
    required this.categories,
  });

  final Widget search;
  final Widget categories;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return Container(
      margin: const EdgeInsets.fromLTRB(-16, -12, -16, 0),
      padding: const EdgeInsets.fromLTRB(18, 14, 18, 14),
      decoration: BoxDecoration(
        color: adaptiveModuleHeaderTint(
          context,
          accent: _shopOrange,
          darkBase: _shopBg,
        ),
        borderRadius: const BorderRadius.vertical(
          bottom: Radius.circular(24),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.location_on_rounded,
                  color: _shopOrange, size: 16),
              const SizedBox(width: 6),
              Text(
                'Free delivery on orders above ₹499',
                style: TextStyle(
                  color: colors.muted,
                  fontWeight: FontWeight.w900,
                  fontSize: 11,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          search,
          const SizedBox(height: 10),
          categories,
        ],
      ),
    );
  }
}

class _BannerStrip extends StatelessWidget {
  const _BannerStrip({required this.banners});

  final List<EcommerceBanner> banners;

  @override
  Widget build(BuildContext context) {
    if (banners.isEmpty) {
      return const _FallbackBanner();
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        return SizedBox(
          height: 116,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: banners.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (context, index) => SizedBox(
              width: constraints.maxWidth,
              child: _BannerCard(banner: banners[index]),
            ),
          ),
        );
      },
    );
  }
}

class _FallbackBanner extends StatelessWidget {
  const _FallbackBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 116,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF351301), Color(0xFFFF7A1A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white24),
      ),
      child: Row(
        children: [
          const Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Up to 60% Off',
                  style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w900),
                ),
                SizedBox(height: 4),
                Text(
                  'On fashion & electronics',
                  style: TextStyle(
                      color: Colors.white70,
                      fontSize: 11,
                      fontWeight: FontWeight.w800),
                ),
                SizedBox(height: 8),
                _ShopNowPill(),
              ],
            ),
          ),
          const SizedBox(width: 14),
          Container(
            width: 82,
            height: 82,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: .88),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.shopping_bag_outlined,
                color: _shopOrange, size: 34),
          ),
        ],
      ),
    );
  }
}

class _ShopNowPill extends StatelessWidget {
  const _ShopNowPill();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: .72),
        borderRadius: BorderRadius.circular(999),
      ),
      child: const Text(
        'Shop now',
        style: TextStyle(
          color: Colors.white,
          fontSize: 10,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _BannerCard extends StatelessWidget {
  const _BannerCard({required this.banner});

  final EcommerceBanner banner;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: _shopCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _shopLine),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.26),
            blurRadius: 18,
            offset: const Offset(0, 9),
          ),
        ],
      ),
      child: Stack(
        fit: StackFit.expand,
        children: [
          EcommerceRemoteImage(
            url: banner.imageUrl,
            icon: Icons.storefront_outlined,
            fit: BoxFit.cover,
            iconSize: 48,
          ),
          DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Colors.black.withValues(alpha: 0.5),
                  Colors.transparent
                ],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    banner.title.isEmpty ? 'Ecommerce Offer' : banner.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 19,
                        fontWeight: FontWeight.w900),
                  ),
                  const SizedBox(height: 8),
                  const _ShopNowPill(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title, this.trailing});

  final String title;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _shopText);
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title,
            style: TextStyle(
                color: textColor, fontSize: 18, fontWeight: FontWeight.w900)),
        if (trailing != null)
          Text(trailing!,
              style: const TextStyle(
                  color: _shopOrange,
                  fontSize: 12,
                  fontWeight: FontWeight.w700)),
      ],
    );
  }
}

class _CategoryStrip extends StatelessWidget {
  const _CategoryStrip({required this.categories, required this.onTap});

  final List<EcommerceCategory> categories;
  final ValueChanged<EcommerceCategory> onTap;

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) {
      return Text('Categories added from admin will appear here.',
          style: TextStyle(color: adaptiveMuted(context, _shopMuted)));
    }
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );

    return SizedBox(
      height: 78,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: categories.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final category = categories[index];
          return InkWell(
            onTap: () => onTap(category),
            borderRadius: BorderRadius.circular(14),
            child: SizedBox(
              width: 76,
              child: Column(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    clipBehavior: Clip.antiAlias,
                    decoration: BoxDecoration(
                      color: index == 0
                          ? _shopOrange.withValues(alpha: .18)
                          : colors.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: index == 0
                            ? _shopOrange.withValues(alpha: .42)
                            : colors.line,
                      ),
                    ),
                    child: EcommerceRemoteImage(
                      url: category.imageUrl,
                      icon: index == 0
                          ? Icons.checkroom_outlined
                          : Icons.category_outlined,
                      iconSize: 24,
                      fit: BoxFit.cover,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    category.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 10,
                        height: 1.05,
                        fontWeight: FontWeight.w900,
                        color: colors.text),
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

class _EcommerceSubcategorySheet extends StatelessWidget {
  const _EcommerceSubcategorySheet({
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
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return SafeArea(
      top: false,
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.sizeOf(context).height * .68,
        ),
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
            Text('Choose a section or browse everything',
                style: TextStyle(
                    color: colors.muted, fontWeight: FontWeight.w700)),
            const SizedBox(height: 16),
            Flexible(
              child: GridView.builder(
                shrinkWrap: true,
                itemCount: category.subcategories.length + 1,
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: .9,
                ),
                itemBuilder: (context, index) {
                  final isAll = index == 0;
                  final item = isAll ? null : category.subcategories[index - 1];
                  return InkWell(
                    onTap: isAll ? onAllTap : () => onTap(item!),
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: colors.card,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(
                            color: isAll ? _shopOrange : colors.line),
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
                                      color: _shopOrange,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(Icons.grid_view_rounded,
                                        color: Colors.white),
                                  )
                                : ClipRRect(
                                    borderRadius: BorderRadius.circular(14),
                                    child: EcommerceRemoteImage(
                                      url: item!.imageUrl,
                                      icon: Icons.category_outlined,
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

class _BrandStrip extends StatelessWidget {
  const _BrandStrip({required this.brands, required this.onTap});

  final List<EcommerceBrand> brands;
  final ValueChanged<EcommerceBrand> onTap;

  @override
  Widget build(BuildContext context) {
    if (brands.isEmpty) {
      return Text('Brands added from admin will appear here.',
          style: TextStyle(color: adaptiveMuted(context, _shopMuted)));
    }
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );

    return SizedBox(
      height: 94,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: brands.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final brand = brands[index];
          return InkWell(
            onTap: () => onTap(brand),
            borderRadius: BorderRadius.circular(14),
            child: Container(
              width: 104,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: colors.line),
              ),
              child: Column(
                children: [
                  Expanded(
                    child: EcommerceRemoteImage(
                      url: brand.imageUrl,
                      icon: Icons.workspace_premium_outlined,
                      iconSize: 28,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    brand.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        color: colors.text,
                        fontSize: 12,
                        fontWeight: FontWeight.w800),
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

class _VendorStrip extends StatelessWidget {
  const _VendorStrip({required this.vendors, required this.onTap});

  final List<EcommerceVendor> vendors;
  final ValueChanged<EcommerceVendor> onTap;

  @override
  Widget build(BuildContext context) {
    if (vendors.isEmpty) {
      return Text('Approved vendors will appear here.',
          style: TextStyle(color: adaptiveMuted(context, _shopMuted)));
    }
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );

    return SizedBox(
      height: 100,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: vendors.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final vendor = vendors[index];
          return InkWell(
            onTap: () => onTap(vendor),
            borderRadius: BorderRadius.circular(16),
            child: Container(
              width: 168,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: colors.line),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 38,
                        height: 38,
                        decoration: BoxDecoration(
                          color: _shopOrange.withValues(alpha: .14),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.storefront_outlined,
                            color: _shopOrange, size: 22),
                      ),
                      const Spacer(),
                      Icon(Icons.chevron_right_rounded,
                          color: colors.muted, size: 20),
                    ],
                  ),
                  const SizedBox(height: 9),
                  Text(
                    vendor.shopName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: colors.text, fontWeight: FontWeight.w900),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    vendor.city.isEmpty
                        ? 'City E-Commerce seller'
                        : vendor.city,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: colors.muted, fontSize: 12),
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

class _HorizontalProducts extends StatelessWidget {
  const _HorizontalProducts({
    required this.products,
    required this.currency,
    required this.onTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 190,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: products.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) => SizedBox(
          width: 136,
          child: _ProductCard(
              product: products[index],
              currency: currency,
              badge: index == 0 ? 'New' : (index == 1 ? 'Hot' : null),
              onVendorTap: null,
              onTap: () => onTap(products[index])),
        ),
      ),
    );
  }
}

class _FlashSaleDealCard extends StatelessWidget {
  const _FlashSaleDealCard({
    required this.products,
    required this.currency,
    required this.onTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    final product = products.first;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Ink(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: colors.line),
        ),
        child: Row(
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: _shopOrange.withValues(alpha: .16),
                borderRadius: BorderRadius.circular(12),
              ),
              child:
                  const Icon(Icons.bolt_rounded, color: _shopOrange, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Flash Sale',
                    style: TextStyle(
                      color: _shopOrange,
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${product.name} from $currency${product.sellingPrice.toStringAsFixed(0)}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 12,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${products.length} limited time deals',
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: _shopOrange,
                borderRadius: BorderRadius.circular(999),
              ),
              child: const Text(
                'View Deals',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ProductGrid extends StatelessWidget {
  const _ProductGrid({
    required this.products,
    required this.currency,
    required this.onTap,
    this.onVendorTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onTap;
  final ValueChanged<EcommerceProduct>? onVendorTap;

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
        childAspectRatio: 0.68,
      ),
      itemBuilder: (context, index) => _ProductCard(
        product: products[index],
        currency: currency,
        badge: index < 2 ? 'New' : null,
        onTap: () => onTap(products[index]),
        onVendorTap:
            onVendorTap == null ? null : () => onVendorTap!(products[index]),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({
    required this.product,
    required this.currency,
    required this.onTap,
    required this.onVendorTap,
    this.badge,
  });

  final EcommerceProduct product;
  final String currency;
  final VoidCallback onTap;
  final VoidCallback? onVendorTap;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Ink(
        decoration: BoxDecoration(
            color: colors.card,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: colors.line),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.26),
                blurRadius: 14,
                offset: const Offset(0, 7),
              ),
            ]),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Container(
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    color: colors.surface,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      EcommerceRemoteImage(
                        url: product.thumbnailUrl,
                        icon: Icons.shopping_bag_outlined,
                        fit: BoxFit.cover,
                        iconSize: 54,
                      ),
                      if (badge != null)
                        Positioned(
                          left: 6,
                          top: 6,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 7, vertical: 4),
                            decoration: BoxDecoration(
                              color: badge == 'Hot'
                                  ? _shopOrange
                                  : const Color(0xFFFFEFE3),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              badge!,
                              style: TextStyle(
                                color:
                                    badge == 'Hot' ? Colors.white : _shopOrange,
                                fontSize: 9,
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
              if (product.vendorId > 0)
                GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: onVendorTap,
                  child: Text(
                    product.vendorName ?? 'Store',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: colors.muted,
                        fontSize: 10,
                        fontWeight: FontWeight.w800),
                  ),
                )
              else
                Text(product.unit,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: colors.muted,
                        fontSize: 10,
                        fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Row(
                children: [
                  Text('$currency${product.sellingPrice.toStringAsFixed(0)}',
                      style: TextStyle(
                          color: colors.text,
                          fontWeight: FontWeight.w900,
                          fontSize: 12)),
                  const SizedBox(width: 5),
                  if (product.hasDiscount)
                    Expanded(
                      child: Text(
                        '$currency${product.price.toStringAsFixed(0)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                            color: colors.muted,
                            decoration: TextDecoration.lineThrough,
                            fontSize: 10),
                      ),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyProducts extends StatelessWidget {
  const _EmptyProducts();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: colors.line)),
      child: Text('Products added from admin will appear here.',
          textAlign: TextAlign.center, style: TextStyle(color: colors.muted)),
    );
  }
}

class _EcommerceErrorState extends StatelessWidget {
  const _EcommerceErrorState({
    required this.message,
    required this.detail,
    required this.onRetry,
  });

  final String message;
  final String detail;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.cloud_off_rounded, size: 54, color: colors.muted),
            const SizedBox(height: 14),
            Text(message,
                textAlign: TextAlign.center,
                style: TextStyle(
                    color: colors.text,
                    fontSize: 20,
                    fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            Text(detail,
                textAlign: TextAlign.center,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: colors.muted, fontSize: 12)),
            const SizedBox(height: 18),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: _shopOrange),
              onPressed: onRetry,
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EcommerceBlockingState extends StatelessWidget {
  const _EcommerceBlockingState({
    required this.title,
    required this.detail,
  });

  final String title;
  final String detail;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: colors.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: colors.line),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.info_outline_rounded,
                  color: _shopOrange, size: 44),
              const SizedBox(height: 12),
              Text(
                title,
                style: TextStyle(
                  color: colors.text,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                detail,
                textAlign: TextAlign.center,
                style: TextStyle(color: colors.muted, height: 1.5),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _VersionNotice extends StatelessWidget {
  const _VersionNotice({
    required this.currentVersion,
    required this.latestVersion,
  });

  final String currentVersion;
  final String latestVersion;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF6E8),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFFFD79A)),
      ),
      child: Row(
        children: [
          const Icon(Icons.system_update_alt_rounded, color: Color(0xFFCC8A00)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'App update available. Current: $currentVersion  Latest: $latestVersion',
              style: const TextStyle(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class EcommerceProductListScreen extends StatefulWidget {
  const EcommerceProductListScreen({
    super.key,
    required this.title,
    required this.products,
    required this.currency,
    required this.onProductTap,
    this.onFetch,
    this.onVendorTap,
  });

  final String title;
  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onProductTap;
  final Future<List<EcommerceProduct>> Function(EcommerceProductQuery query)?
      onFetch;
  final ValueChanged<EcommerceProduct>? onVendorTap;

  @override
  State<EcommerceProductListScreen> createState() =>
      _EcommerceProductListScreenState();
}

class _EcommerceProductListScreenState
    extends State<EcommerceProductListScreen> {
  String? _color;
  String? _attribute;
  String _sort = 'latest';
  bool? _digital;
  late List<EcommerceProduct> _products;
  final _minPriceController = TextEditingController();
  final _maxPriceController = TextEditingController();
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _products = widget.products;
  }

  @override
  void dispose() {
    _minPriceController.dispose();
    _maxPriceController.dispose();
    super.dispose();
  }

  List<String> get _colors {
    final values = <String>{};
    for (final product in _products) {
      values.addAll(product.colors.where((item) => item.trim().isNotEmpty));
    }
    return values.toList()..sort();
  }

  List<String> get _attributes {
    final values = <String>{};
    for (final product in _products) {
      values.addAll(product.attributes.where((item) => item.trim().isNotEmpty));
    }
    return values.toList()..sort();
  }

  List<EcommerceProduct> get _filteredProducts {
    return _products.where((product) {
      final colorOk = _color == null || product.colors.contains(_color);
      final attributeOk =
          _attribute == null || product.attributes.contains(_attribute);
      final minPrice = double.tryParse(_minPriceController.text.trim());
      final maxPrice = double.tryParse(_maxPriceController.text.trim());
      final price = product.sellingPrice;
      final digitalOk = _digital == null || product.isDigital == _digital;
      final minOk = minPrice == null || price >= minPrice;
      final maxOk = maxPrice == null || price <= maxPrice;
      return colorOk && attributeOk && digitalOk && minOk && maxOk;
    }).toList()
      ..sort((a, b) {
        return switch (_sort) {
          'price_low' => a.sellingPrice.compareTo(b.sellingPrice),
          'price_high' => b.sellingPrice.compareTo(a.sellingPrice),
          'name' => a.name.compareTo(b.name),
          'stock' => b.stock.compareTo(a.stock),
          _ => b.id.compareTo(a.id),
        };
      });
  }

  @override
  Widget build(BuildContext context) {
    final colors = _colors;
    final attributes = _attributes;
    final filteredProducts = _filteredProducts;
    final hasFilters = _color != null ||
        _attribute != null ||
        _digital != null ||
        _sort != 'latest' ||
        _minPriceController.text.trim().isNotEmpty ||
        _maxPriceController.text.trim().isNotEmpty;
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _ProductFilters(
            colors: colors,
            attributes: attributes,
            selectedColor: _color,
            selectedAttribute: _attribute,
            selectedSort: _sort,
            digital: _digital,
            minPriceController: _minPriceController,
            maxPriceController: _maxPriceController,
            onColorChanged: (value) => setState(() => _color = value),
            onAttributeChanged: (value) => setState(() => _attribute = value),
            onSortChanged: (value) => setState(() => _sort = value ?? 'latest'),
            onDigitalChanged: (value) => setState(() => _digital = value),
            onApply: _applyFilters,
            onClear: hasFilters
                ? () => setState(() {
                      _color = null;
                      _attribute = null;
                      _digital = null;
                      _sort = 'latest';
                      _minPriceController.clear();
                      _maxPriceController.clear();
                    })
                : null,
          ),
          const SizedBox(height: 14),
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: AppSkeletonList(cardCount: 2),
            ),
          if (hasFilters)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Text(
                '${filteredProducts.length} products matched',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
          EcommerceProductListBody(
            products: filteredProducts,
            currency: widget.currency,
            onProductTap: widget.onProductTap,
            onVendorTap: widget.onVendorTap,
          ),
        ],
      ),
    );
  }

  Future<void> _applyFilters() async {
    if (widget.onFetch == null) {
      setState(() {});
      return;
    }
    setState(() => _loading = true);
    try {
      final products = await widget.onFetch!(
        EcommerceProductQuery(
          color: _color,
          attribute: _attribute,
          minPrice: double.tryParse(_minPriceController.text.trim()),
          maxPrice: double.tryParse(_maxPriceController.text.trim()),
          sort: _sort,
          digital: _digital,
        ),
      );
      if (!mounted) return;
      setState(() => _products = products);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _ProductFilters extends StatelessWidget {
  const _ProductFilters({
    required this.colors,
    required this.attributes,
    required this.selectedColor,
    required this.selectedAttribute,
    required this.selectedSort,
    required this.digital,
    required this.minPriceController,
    required this.maxPriceController,
    required this.onColorChanged,
    required this.onAttributeChanged,
    required this.onSortChanged,
    required this.onDigitalChanged,
    required this.onApply,
    required this.onClear,
  });

  final List<String> colors;
  final List<String> attributes;
  final String? selectedColor;
  final String? selectedAttribute;
  final String selectedSort;
  final bool? digital;
  final TextEditingController minPriceController;
  final TextEditingController maxPriceController;
  final ValueChanged<String?> onColorChanged;
  final ValueChanged<String?> onAttributeChanged;
  final ValueChanged<String?> onSortChanged;
  final ValueChanged<bool?> onDigitalChanged;
  final VoidCallback onApply;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    final moduleColors = adaptiveModuleColors(
      context,
      darkBg: _shopBg,
      darkSurface: _shopPanel,
      darkCard: _shopCard,
      darkLine: _shopLine,
      darkText: _shopText,
      darkMuted: _shopMuted,
    );
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: moduleColors.card,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: moduleColors.line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.tune_rounded, color: AppTheme.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('Filters',
                    style: TextStyle(
                        color: moduleColors.text, fontWeight: FontWeight.w900)),
              ),
              TextButton(onPressed: onClear, child: const Text('Clear')),
            ],
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: selectedSort,
            decoration: const InputDecoration(
              labelText: 'Sort',
              prefixIcon: Icon(Icons.sort_rounded),
            ),
            items: const [
              DropdownMenuItem(value: 'latest', child: Text('Latest')),
              DropdownMenuItem(value: 'price_low', child: Text('Price low')),
              DropdownMenuItem(value: 'price_high', child: Text('Price high')),
              DropdownMenuItem(value: 'name', child: Text('Name')),
              DropdownMenuItem(value: 'stock', child: Text('Stock')),
            ],
            onChanged: onSortChanged,
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: minPriceController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Min price'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: TextField(
                  controller: maxPriceController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Max price'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: [
              FilterChip(
                label: const Text('Digital'),
                selected: digital == true,
                onSelected: (selected) =>
                    onDigitalChanged(selected ? true : null),
              ),
              FilterChip(
                label: const Text('Physical'),
                selected: digital == false,
                onSelected: (selected) =>
                    onDigitalChanged(selected ? false : null),
              ),
            ],
          ),
          if (colors.isNotEmpty) ...[
            const SizedBox(height: 8),
            const Text('Colors', style: TextStyle(fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                for (final color in colors)
                  FilterChip(
                    label: Text(color),
                    selected: selectedColor == color,
                    onSelected: (selected) =>
                        onColorChanged(selected ? color : null),
                  ),
              ],
            ),
          ],
          if (attributes.isNotEmpty) ...[
            const SizedBox(height: 12),
            const Text('Attributes',
                style: TextStyle(fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                for (final attribute in attributes)
                  FilterChip(
                    label: Text(attribute),
                    selected: selectedAttribute == attribute,
                    onSelected: (selected) =>
                        onAttributeChanged(selected ? attribute : null),
                  ),
              ],
            ),
          ],
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: onApply,
              icon: const Icon(Icons.filter_alt_rounded),
              label: const Text('Apply Filters'),
            ),
          ),
        ],
      ),
    );
  }
}

class EcommerceProductListBody extends StatelessWidget {
  const EcommerceProductListBody({
    super.key,
    required this.products,
    required this.currency,
    required this.onProductTap,
    this.onVendorTap,
  });

  final List<EcommerceProduct> products;
  final String currency;
  final ValueChanged<EcommerceProduct> onProductTap;
  final ValueChanged<EcommerceProduct>? onVendorTap;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) {
      return const _EmptyProducts();
    }

    return _ProductGrid(
      products: products,
      currency: currency,
      onTap: onProductTap,
      onVendorTap: onVendorTap,
    );
  }
}
