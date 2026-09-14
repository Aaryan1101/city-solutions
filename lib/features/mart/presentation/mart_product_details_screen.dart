import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/mart_api_client.dart';
import '../data/mart_session_store.dart';
import '../domain/mart_models.dart';
import 'mart_cart_screen.dart';
import 'mart_remote_image.dart';
import 'mart_vendor_screen.dart';

class MartProductDetailsScreen extends StatefulWidget {
  const MartProductDetailsScreen({
    super.key,
    required this.product,
    this.apiBaseUrl,
  });

  final MartProduct product;
  final String? apiBaseUrl;

  @override
  State<MartProductDetailsScreen> createState() =>
      _MartProductDetailsScreenState();
}

class _MartProductDetailsScreenState extends State<MartProductDetailsScreen> {
  int _quantity = 1;
  int _imageIndex = 0;
  MartProductVariant? _variant;
  bool _adding = false;
  bool _wishlistLoading = false;
  bool _wishlisted = false;
  late Future<MartProductReviewsData> _reviewsFuture;
  late final MartApiClient _api;

  @override
  void initState() {
    super.initState();
    _api = MartApiClient(baseUrl: widget.apiBaseUrl);
    _reviewsFuture = _api.fetchProductReviews(productId: widget.product.id);
    _loadWishlist();
  }

  @override
  Widget build(BuildContext context) {
    final product = widget.product;
    final imageUrls = product.imageUrls.isEmpty
        ? [if (product.thumbnailUrl != null) product.thumbnailUrl!]
        : product.imageUrls;
    final price = _variant?.sellingPrice ?? product.sellingPrice;
    final originalPrice = _variant?.price ?? product.price;
    final hasDiscount = _variant?.hasDiscount ?? product.hasDiscount;
    final unit = _variant?.unit ?? product.unit;
    final stock = _variant?.stock ?? product.stock;
    final savedAmount =
        (originalPrice - price).clamp(0, double.infinity).toDouble();
    const accent = Color(0xFF20A66A);
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(14, 8, 14, 14),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.12),
                blurRadius: 24,
                offset: const Offset(0, -8),
              ),
            ],
          ),
          child: Row(
            children: [
              _QtyStepper(
                quantity: _quantity,
                maxQuantity: stock,
                onChanged: (value) => setState(() => _quantity = value),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: SizedBox(
                  height: 54,
                  child: FilledButton.icon(
                    onPressed: _adding || stock <= 0 ? null : _addToCart,
                    icon: _adding
                        ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                        : const Icon(Icons.add_shopping_cart_rounded),
                    label: Text(_adding ? 'Adding...' : 'Add to cart'),
                    style: FilledButton.styleFrom(
                      backgroundColor: accent,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      textStyle: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w900,
                      ),
                      shadowColor: accent.withValues(alpha: 0.35),
                      elevation: 8,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      body: Stack(
        children: [
          ListView(
            padding: EdgeInsets.zero,
            children: [
              SizedBox(
                height: 420,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Container(
                      color: const Color(0xFFEAF8F1),
                      child: MartRemoteImage(
                        url: imageUrls.isEmpty
                            ? product.thumbnailUrl
                            : imageUrls[_imageIndex],
                        icon: Icons.eco_outlined,
                        iconSize: 92,
                        fit: BoxFit.cover,
                      ),
                    ),
                    DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.black.withValues(alpha: 0.22),
                            Colors.transparent,
                            const Color(0xFFF4F7FB),
                          ],
                          stops: const [0, 0.48, 1],
                        ),
                      ),
                    ),
                    if (hasDiscount)
                      Positioned(
                        left: 18,
                        bottom: 92,
                        child: _MetaPill(
                          label: originalPrice > 0
                              ? '${((savedAmount / originalPrice) * 100).round()}% OFF'
                              : 'OFFER',
                          color: AppTheme.danger,
                        ),
                      ),
                    Positioned(
                      left: 0,
                      right: 0,
                      bottom: 62,
                      child: _ImageDots(
                        count: imageUrls.length <= 1 ? 3 : imageUrls.length,
                        index: _imageIndex,
                        color: accent,
                      ),
                    ),
                  ],
                ),
              ),
              Transform.translate(
                offset: const Offset(0, -58),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(18, 0, 18, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _ProductSummaryCard(
                        productName: product.name,
                        unit: unit,
                        price: price,
                        originalPrice: originalPrice,
                        hasDiscount: hasDiscount,
                        savedAmount: savedAmount,
                        stock: stock,
                        taxPercent: product.taxPercent,
                        isDigital: product.isDigital,
                        accent: accent,
                      ),
                      const SizedBox(height: 14),
                      const Row(
                        children: [
                          Expanded(
                            child: _TrustCard(
                              icon: Icons.assignment_return_outlined,
                              title: 'Easy Refunds',
                            ),
                          ),
                          SizedBox(width: 12),
                          Expanded(
                            child: _TrustCard(
                              icon: Icons.delivery_dining_rounded,
                              title: 'Fast Delivery',
                            ),
                          ),
                        ],
                      ),
                      if (product.vendorId > 0) ...[
                        const SizedBox(height: 14),
                        _StoreTile(product: product),
                      ],
                      if (product.variants.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        _SectionCard(
                          title: 'Select Unit',
                          child: Wrap(
                            spacing: 10,
                            runSpacing: 10,
                            children: [
                              for (final variant in product.variants)
                                _VariantCard(
                                  name: variant.name,
                                  unit: variant.unit,
                                  price: variant.sellingPrice,
                                  originalPrice: variant.price,
                                  selected: _variant?.id == variant.id,
                                  enabled: variant.stock > 0,
                                  accent: accent,
                                  onTap: () => setState(() {
                                    _variant = variant;
                                    _quantity = 1;
                                  }),
                                ),
                            ],
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      _HighlightsCard(
                        unit: unit,
                        stock: stock,
                        taxPercent: product.taxPercent,
                        attributes: product.attributes,
                        colors: product.colors,
                        isDigital: product.isDigital,
                        freshnessNote: product.freshnessNote,
                        expiryDate: product.expiryDate,
                        shelfLife: product.shelfLife,
                        warrantyNote: product.warrantyNote,
                        returnPolicy: product.returnPolicy,
                        accent: accent,
                      ),
                      const SizedBox(height: 16),
                      _SectionCard(
                        title: 'Description',
                        child: Text(
                          product.description.isEmpty
                              ? 'Product details will appear here from admin.'
                              : product.description,
                          style: const TextStyle(
                            height: 1.5,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      _ReviewsSection(
                        future: _reviewsFuture,
                        onWriteReview: _writeReview,
                      ),
                      const SizedBox(height: 38),
                    ],
                  ),
                ),
              ),
            ],
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
              child: Row(
                children: [
                  _ActionBubble(
                    icon: Icons.arrow_back_ios_new_rounded,
                    onTap: () => Navigator.of(context).pop(),
                  ),
                  const Spacer(),
                  _ActionBubble(
                    icon: _wishlisted
                        ? Icons.favorite_rounded
                        : Icons.favorite_border_rounded,
                    color: _wishlisted ? AppTheme.danger : null,
                    onTap: _wishlistLoading ? null : _toggleWishlist,
                  ),
                  const SizedBox(width: 10),
                  _ActionBubble(
                    icon: Icons.search_rounded,
                    onTap: () => Navigator.of(context).maybePop(),
                  ),
                  const SizedBox(width: 10),
                  _ActionBubble(
                    icon: Icons.shopping_cart_outlined,
                    onTap: () async {
                      final session = await MartSessionStore().load();
                      if (!context.mounted) return;
                      Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => MartCartScreen(
                            guestId: session.guestId,
                            apiBaseUrl: widget.apiBaseUrl,
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
          if (imageUrls.length > 1)
            Positioned(
              left: 18,
              right: 18,
              top: 358,
              child: SizedBox(
                height: 58,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: imageUrls.length,
                  separatorBuilder: (_, __) => const SizedBox(width: 8),
                  itemBuilder: (context, index) => InkWell(
                    onTap: () => setState(() => _imageIndex = index),
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      width: 58,
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: index == _imageIndex
                              ? accent
                              : Theme.of(context).dividerColor,
                          width: 2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.12),
                            blurRadius: 16,
                            offset: const Offset(0, 8),
                          ),
                        ],
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: MartRemoteImage(
                          url: imageUrls[index],
                          icon: Icons.image_outlined,
                          iconSize: 22,
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Future<void> _addToCart() async {
    setState(() => _adding = true);
    try {
      final session = await MartSessionStore().load();
      await _api.addToCart(
        guestId: session.guestId,
        productId: widget.product.id,
        variantId: _variant?.id,
        quantity: _quantity,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          SnackBar(
            duration: const Duration(seconds: 2),
            margin: const EdgeInsets.fromLTRB(16, 0, 16, 92),
            content: const Text('Added to cart'),
            action: SnackBarAction(
              label: 'View',
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => MartCartScreen(
                    guestId: session.guestId,
                    apiBaseUrl: widget.apiBaseUrl,
                  ),
                ),
              ),
            ),
          ),
        );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _adding = false);
    }
  }

  Future<void> _loadWishlist() async {
    try {
      final session = await MartSessionStore().load();
      final wishlisted = await _api.isWishlisted(
        guestId: session.guestId,
        productId: widget.product.id,
      );
      if (!mounted) return;
      setState(() => _wishlisted = wishlisted);
    } catch (_) {
      // Wishlist is optional; product details should still load if backend is older.
    }
  }

  Future<void> _toggleWishlist() async {
    setState(() => _wishlistLoading = true);
    try {
      final session = await MartSessionStore().load();
      final wishlisted = await _api.toggleWishlist(
        guestId: session.guestId,
        productId: widget.product.id,
      );
      if (!mounted) return;
      setState(() => _wishlisted = wishlisted);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            wishlisted ? 'Added to wishlist' : 'Removed from wishlist',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _wishlistLoading = false);
    }
  }

  Future<void> _writeReview() async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _ReviewSheet(productId: widget.product.id),
    );
    if (saved != true || !mounted) return;
    setState(() {
      _reviewsFuture = _api.fetchProductReviews(productId: widget.product.id);
    });
  }
}

class _ActionBubble extends StatelessWidget {
  const _ActionBubble({
    required this.icon,
    required this.onTap,
    this.color,
  });

  final IconData icon;
  final VoidCallback? onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surface.withValues(alpha: 0.94),
      shape: const CircleBorder(),
      elevation: 8,
      shadowColor: AppTheme.ink.withValues(alpha: 0.16),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 46,
          height: 46,
          child: Icon(
            icon,
            color: color ?? Theme.of(context).colorScheme.onSurface,
            size: 22,
          ),
        ),
      ),
    );
  }
}

class _ImageDots extends StatelessWidget {
  const _ImageDots({
    required this.count,
    required this.index,
    required this.color,
  });

  final int count;
  final int index;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        for (var dot = 0; dot < count.clamp(1, 8); dot++)
          AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            width: dot == index ? 16 : 6,
            height: 6,
            margin: const EdgeInsets.symmetric(horizontal: 3),
            decoration: BoxDecoration(
              color: dot == index ? color : Theme.of(context).dividerColor,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
      ],
    );
  }
}

class _ProductSummaryCard extends StatelessWidget {
  const _ProductSummaryCard({
    required this.productName,
    required this.unit,
    required this.price,
    required this.originalPrice,
    required this.hasDiscount,
    required this.savedAmount,
    required this.stock,
    required this.taxPercent,
    required this.isDigital,
    required this.accent,
  });

  final String productName;
  final String unit;
  final double price;
  final double originalPrice;
  final bool hasDiscount;
  final double savedAmount;
  final int stock;
  final double taxPercent;
  final bool isDigital;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 22,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            productName,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontSize: 21,
              height: 1.15,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 7),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              Text(
                unit,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
              if (isDigital) _MetaPill(label: 'Digital', color: accent),
              if (taxPercent > 0)
                _MetaPill(
                  label: 'Incl. ${taxPercent.toStringAsFixed(0)}% tax',
                  color: AppTheme.primary,
                ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                decoration: BoxDecoration(
                  color: accent,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '₹${price.toStringAsFixed(0)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              if (hasDiscount)
                Flexible(
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text(
                      'MRP ₹${originalPrice.toStringAsFixed(0)}',
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        decoration: TextDecoration.lineThrough,
                      ),
                    ),
                  ),
                ),
              if (hasDiscount && savedAmount > 0) ...[
                const SizedBox(width: 8),
                Padding(
                  padding: const EdgeInsets.only(bottom: 3),
                  child: Text(
                    '₹${savedAmount.toStringAsFixed(0)} OFF',
                    style: TextStyle(
                      color: accent,
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Text(
            stock > 0 ? '$stock in stock' : 'Out of stock',
            style: TextStyle(
              color: stock > 0 ? accent : AppTheme.danger,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _TrustCard extends StatelessWidget {
  const _TrustCard({required this.icon, required this.title});

  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 112,
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 34, color: Theme.of(context).colorScheme.onSurface),
          const SizedBox(height: 12),
          Text(
            title,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: 0.65),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 14),
          child,
        ],
      ),
    );
  }
}

class _VariantCard extends StatelessWidget {
  const _VariantCard({
    required this.name,
    required this.unit,
    required this.price,
    required this.originalPrice,
    required this.selected,
    required this.enabled,
    required this.accent,
    required this.onTap,
  });

  final String name;
  final String unit;
  final double price;
  final double originalPrice;
  final bool selected;
  final bool enabled;
  final Color accent;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final hasDiscount = originalPrice > price;
    final discountPercent = originalPrice <= 0
        ? 0
        : (((originalPrice - price) / originalPrice) * 100).round();
    return InkWell(
      onTap: enabled ? onTap : null,
      borderRadius: BorderRadius.circular(16),
      child: Ink(
        width: 150,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected
              ? accent.withValues(alpha: 0.14)
              : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? accent : Theme.of(context).dividerColor,
            width: selected ? 2 : 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w900)),
            const SizedBox(height: 4),
            Text(unit, style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 10),
            Text('₹${price.toStringAsFixed(0)}',
                style:
                    const TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
            if (hasDiscount) ...[
              const SizedBox(height: 3),
              Text(
                '$discountPercent% OFF on MRP',
                style: TextStyle(color: accent, fontWeight: FontWeight.w900),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _HighlightsCard extends StatelessWidget {
  const _HighlightsCard({
    required this.unit,
    required this.stock,
    required this.taxPercent,
    required this.attributes,
    required this.colors,
    required this.isDigital,
    required this.freshnessNote,
    required this.expiryDate,
    required this.shelfLife,
    required this.warrantyNote,
    required this.returnPolicy,
    required this.accent,
  });

  final String unit;
  final int stock;
  final double taxPercent;
  final List<String> attributes;
  final List<String> colors;
  final bool isDigital;
  final String freshnessNote;
  final String expiryDate;
  final String shelfLife;
  final String warrantyNote;
  final String returnPolicy;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    final rows = <MapEntry<String, String>>[
      MapEntry('Net Quantity', unit),
      MapEntry('Availability', stock > 0 ? '$stock in stock' : 'Out of stock'),
      if (taxPercent > 0) MapEntry('Tax', '${taxPercent.toStringAsFixed(0)}%'),
      if (freshnessNote.trim().isNotEmpty) MapEntry('Freshness', freshnessNote),
      if (expiryDate.trim().isNotEmpty) MapEntry('Expiry', expiryDate),
      if (shelfLife.trim().isNotEmpty) MapEntry('Shelf Life', shelfLife),
      if (warrantyNote.trim().isNotEmpty) MapEntry('Warranty', warrantyNote),
      if (returnPolicy.trim().isNotEmpty) MapEntry('Returns', returnPolicy),
      if (isDigital) const MapEntry('Delivery Type', 'Digital download'),
      for (final item in attributes)
        if (item.trim().isNotEmpty) _attributeEntry(item),
    ];
    return _SectionCard(
      title: 'Highlights',
      child: Column(
        children: [
          for (final row in rows)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 120,
                    child: Text(
                      row.key,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      row.value,
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          if (colors.isNotEmpty)
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(
                  width: 120,
                  child: Text(
                    'Colors',
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                Expanded(
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final color in colors)
                        _MetaPill(label: color, color: accent),
                    ],
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }

  static MapEntry<String, String> _attributeEntry(String item) {
    final parts = item.split(':');
    if (parts.length >= 2) {
      return MapEntry(parts.first.trim(), parts.sublist(1).join(':').trim());
    }
    return MapEntry('Detail', item.trim());
  }
}

class _MetaPill extends StatelessWidget {
  const _MetaPill({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontWeight: FontWeight.w900),
      ),
    );
  }
}

class _ReviewsSection extends StatelessWidget {
  const _ReviewsSection({
    required this.future,
    required this.onWriteReview,
  });

  final Future<MartProductReviewsData> future;
  final VoidCallback onWriteReview;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<MartProductReviewsData>(
      future: future,
      builder: (context, snapshot) {
        final data = snapshot.data;
        return Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Theme.of(context).dividerColor),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Expanded(
                    child: Text('Reviews',
                        style: TextStyle(
                            fontSize: 18, fontWeight: FontWeight.w900)),
                  ),
                  TextButton.icon(
                    onPressed: onWriteReview,
                    icon: const Icon(Icons.rate_review_outlined),
                    label: const Text('Write'),
                  ),
                ],
              ),
              if (snapshot.connectionState == ConnectionState.waiting)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: AppSkeletonList(cardCount: 1),
                )
              else if (snapshot.hasError)
                Text(AppErrorState.userMessage(snapshot.error),
                    style: const TextStyle())
              else ...[
                _RatingSummary(summary: data!.summary),
                const SizedBox(height: 12),
                if (data.reviews.isEmpty)
                  const Text('No reviews yet.', style: TextStyle())
                else
                  ...data.reviews.take(5).map(_ReviewTile.new),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _RatingSummary extends StatelessWidget {
  const _RatingSummary({required this.summary});

  final MartReviewSummary summary;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        const Icon(Icons.star_rounded, color: Color(0xFFFFB020)),
        const SizedBox(width: 6),
        Text(summary.averageRating.toStringAsFixed(1),
            style: const TextStyle(fontWeight: FontWeight.w900)),
        const SizedBox(width: 6),
        Text('(${summary.totalReviews} reviews)', style: const TextStyle()),
      ],
    );
  }
}

class _ReviewTile extends StatelessWidget {
  const _ReviewTile(this.review);

  final MartProductReview review;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(review.customerName,
                    style: const TextStyle(fontWeight: FontWeight.w900)),
              ),
              _Stars(value: review.rating),
            ],
          ),
          const SizedBox(height: 5),
          Text(review.comment, style: const TextStyle(height: 1.35)),
          if (review.reply.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Theme.of(context).scaffoldBackgroundColor,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text('Seller: ${review.reply}',
                  style: const TextStyle(height: 1.35)),
            ),
          ],
        ],
      ),
    );
  }
}

class _Stars extends StatelessWidget {
  const _Stars({required this.value});

  final int value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var index = 1; index <= 5; index++)
          Icon(
            index <= value ? Icons.star_rounded : Icons.star_border_rounded,
            size: 17,
            color: const Color(0xFFFFB020),
          ),
      ],
    );
  }
}

class _ReviewSheet extends StatefulWidget {
  const _ReviewSheet({required this.productId});

  final int productId;

  @override
  State<_ReviewSheet> createState() => _ReviewSheetState();
}

class _ReviewSheetState extends State<_ReviewSheet> {
  final _commentController = TextEditingController();
  int _rating = 5;
  bool _saving = false;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          16,
          16,
          16,
          16 + MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Write Review',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
            const SizedBox(height: 14),
            Row(
              children: [
                for (var index = 1; index <= 5; index++)
                  IconButton(
                    onPressed: () => setState(() => _rating = index),
                    icon: Icon(
                      index <= _rating
                          ? Icons.star_rounded
                          : Icons.star_border_rounded,
                      color: const Color(0xFFFFB020),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _commentController,
              minLines: 3,
              maxLines: 5,
              decoration: const InputDecoration(
                labelText: 'Comment',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: FilledButton(
                onPressed: _saving ? null : _save,
                child: Text(_saving ? 'Saving...' : 'Submit Review'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    final comment = _commentController.text.trim();
    if (comment.isEmpty) return;
    setState(() => _saving = true);
    try {
      final session = await MartSessionStore().load();
      await MartApiClient().saveProductReview(
        productId: widget.productId,
        guestId: session.guestId,
        customerName: session.name.isEmpty ? 'Customer' : session.name,
        rating: _rating,
        comment: comment,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _StoreTile extends StatelessWidget {
  const _StoreTile({required this.product});

  final MartProduct product;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => MartVendorScreen(
            vendorId: product.vendorId,
            vendorName: product.vendorName ?? 'Store',
          ),
        ),
      ),
      borderRadius: BorderRadius.circular(8),
      child: Ink(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: Row(
          children: [
            const Icon(Icons.storefront_outlined, color: Color(0xFF20A66A)),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Sold by ${product.vendorName ?? 'Store'}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
            ),
          ],
        ),
      ),
    );
  }
}

class _QtyStepper extends StatelessWidget {
  const _QtyStepper({
    required this.quantity,
    required this.maxQuantity,
    required this.onChanged,
  });

  final int quantity;
  final int maxQuantity;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Theme.of(context).dividerColor)),
      child: Row(
        children: [
          IconButton(
            onPressed: quantity > 1 ? () => onChanged(quantity - 1) : null,
            icon: const Icon(Icons.remove_rounded),
          ),
          SizedBox(
              width: 26,
              child: Text('$quantity',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontWeight: FontWeight.w900))),
          IconButton(
            onPressed:
                quantity < maxQuantity ? () => onChanged(quantity + 1) : null,
            icon: const Icon(Icons.add_rounded),
          ),
        ],
      ),
    );
  }
}
