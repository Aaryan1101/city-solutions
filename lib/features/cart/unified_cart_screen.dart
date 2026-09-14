import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../ecommerce/data/ecommerce_api_client.dart';
import '../ecommerce/data/ecommerce_session_store.dart';
import '../ecommerce/domain/ecommerce_models.dart';
import '../ecommerce/presentation/ecommerce_cart_screen.dart';
import '../mart/data/mart_api_client.dart';
import '../mart/data/mart_session_store.dart';
import '../mart/domain/mart_models.dart';
import '../mart/presentation/mart_cart_screen.dart';

const _medicalApiBaseUrl =
    'https://snow-grouse-381496.hostingersite.com/api/v1/medical';

class UnifiedCartScreen extends StatefulWidget {
  const UnifiedCartScreen({super.key});

  @override
  State<UnifiedCartScreen> createState() => _UnifiedCartScreenState();
}

class _UnifiedCartScreenState extends State<UnifiedCartScreen> {
  late Future<_UnifiedCartData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_UnifiedCartData> _load() async {
    final martSession = await MartSessionStore().load();
    final ecommerceSession = await EcommerceSessionStore().load();
    final martApi = MartApiClient();
    final ecommerceApi = EcommerceApiClient();
    final medicalApi = EcommerceApiClient(baseUrl: _medicalApiBaseUrl);

    final responses = await Future.wait<dynamic>([
      _tryMartCart(martApi, martSession.guestId),
      _tryEcommerceCart(ecommerceApi, ecommerceSession.guestId),
      _tryEcommerceCart(medicalApi, ecommerceSession.guestId),
    ]);

    return _UnifiedCartData(
      martGuestId: martSession.guestId,
      ecommerceGuestId: ecommerceSession.guestId,
      mart: responses[0] as MartCartData?,
      ecommerce: responses[1] as EcommerceCartData?,
      medical: responses[2] as EcommerceCartData?,
    );
  }

  void _refresh() {
    setState(() => _future = _load());
  }

  Future<MartCartData?> _tryMartCart(MartApiClient api, String guestId) async {
    try {
      return await api.fetchCart(guestId: guestId);
    } catch (_) {
      return null;
    }
  }

  Future<EcommerceCartData?> _tryEcommerceCart(
    EcommerceApiClient api,
    String guestId,
  ) async {
    try {
      return await api.fetchCart(guestId: guestId);
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: const Text('Cart'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _refresh,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: FutureBuilder<_UnifiedCartData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError || !snapshot.hasData) {
            return _CartStateMessage(
              icon: Icons.cloud_off_outlined,
              title: 'Cart could not load',
              subtitle: AppErrorState.userMessage(snapshot.error),
              action: _refresh,
            );
          }

          final data = snapshot.data!;
          if (data.totalItems == 0) {
            return _CartStateMessage(
              icon: Icons.shopping_cart_outlined,
              title: 'Your cart is empty',
              subtitle:
                  'Items from Mart, E-Commerce and Medical will appear here.',
              action: _refresh,
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              _refresh();
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.fromLTRB(18, 12, 18, 110),
              children: [
                _UnifiedCartSummary(data: data),
                const SizedBox(height: 14),
                if (data.mart != null)
                  _MartCartSection(
                    title: 'Mart',
                    color: const Color(0xFF20A66A),
                    icon: Icons.storefront_outlined,
                    cart: data.mart!,
                    onOpen: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => MartCartScreen(
                          guestId: data.martGuestId,
                        ),
                      ),
                    ),
                  ),
                if (data.ecommerce != null)
                  _EcommerceCartSection(
                    title: 'E-Commerce',
                    color: AppTheme.primary,
                    icon: Icons.shopping_bag_outlined,
                    cart: data.ecommerce!,
                    onOpen: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => EcommerceCartScreen(
                          guestId: data.ecommerceGuestId,
                        ),
                      ),
                    ),
                  ),
                if (data.medical != null)
                  _EcommerceCartSection(
                    title: 'Medical',
                    color: const Color(0xFFE94C3D),
                    icon: Icons.medication_liquid_outlined,
                    cart: data.medical!,
                    onOpen: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => EcommerceCartScreen(
                          guestId: data.ecommerceGuestId,
                          apiBaseUrl: _medicalApiBaseUrl,
                        ),
                      ),
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

class _UnifiedCartData {
  const _UnifiedCartData({
    required this.martGuestId,
    required this.ecommerceGuestId,
    required this.mart,
    required this.ecommerce,
    required this.medical,
  });

  final String martGuestId;
  final String ecommerceGuestId;
  final MartCartData? mart;
  final EcommerceCartData? ecommerce;
  final EcommerceCartData? medical;

  int get totalItems =>
      (mart?.itemsCount ?? 0) +
      (ecommerce?.itemsCount ?? 0) +
      (medical?.itemsCount ?? 0);

  double get grandTotal =>
      (mart?.total ?? 0) + (ecommerce?.total ?? 0) + (medical?.total ?? 0);
}

class _UnifiedCartSummary extends StatelessWidget {
  const _UnifiedCartSummary({required this.data});

  final _UnifiedCartData data;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppTheme.primaryDark,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Row(
        children: [
          Container(
            height: 48,
            width: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.shopping_cart_checkout_rounded,
                color: Colors.white),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${data.totalItems} item${data.totalItems == 1 ? '' : 's'} across modules',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Combined visible value',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.72),
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          Text(
            '₹${data.grandTotal.toStringAsFixed(0)}',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _MartCartSection extends StatelessWidget {
  const _MartCartSection({
    required this.title,
    required this.color,
    required this.icon,
    required this.cart,
    required this.onOpen,
  });

  final String title;
  final Color color;
  final IconData icon;
  final MartCartData cart;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return _CartSectionShell(
      title: title,
      color: color,
      icon: icon,
      itemCount: cart.itemsCount,
      total: cart.total,
      onOpen: onOpen,
      children: [
        for (final item in cart.items)
          _UnifiedCartItemTile(
            name: item.name,
            subtitle: item.unit,
            imageUrl: item.thumbnailUrl,
            quantity: item.quantity,
            total: item.total,
          ),
      ],
    );
  }
}

class _EcommerceCartSection extends StatelessWidget {
  const _EcommerceCartSection({
    required this.title,
    required this.color,
    required this.icon,
    required this.cart,
    required this.onOpen,
  });

  final String title;
  final Color color;
  final IconData icon;
  final EcommerceCartData cart;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return _CartSectionShell(
      title: title,
      color: color,
      icon: icon,
      itemCount: cart.itemsCount,
      total: cart.total,
      onOpen: onOpen,
      children: [
        for (final item in cart.items)
          _UnifiedCartItemTile(
            name: item.name,
            subtitle: item.unit,
            imageUrl: item.thumbnailUrl,
            quantity: item.quantity,
            total: item.total,
          ),
      ],
    );
  }
}

class _CartSectionShell extends StatelessWidget {
  const _CartSectionShell({
    required this.title,
    required this.color,
    required this.icon,
    required this.itemCount,
    required this.total,
    required this.children,
    required this.onOpen,
  });

  final String title;
  final Color color;
  final IconData icon;
  final int itemCount;
  final double total;
  final List<Widget> children;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    if (itemCount <= 0) return const SizedBox.shrink();
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryDark.withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                height: 42,
                width: 42,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: color),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 16,
                      ),
                    ),
                    Text(
                      '$itemCount item${itemCount == 1 ? '' : 's'}',
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              Text(
                '₹${total.toStringAsFixed(0)}',
                style: const TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 18,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...children,
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: onOpen,
              icon: const Icon(Icons.open_in_new_rounded, size: 18),
              label: Text('Open $title cart'),
              style: FilledButton.styleFrom(backgroundColor: color),
            ),
          ),
        ],
      ),
    );
  }
}

class _UnifiedCartItemTile extends StatelessWidget {
  const _UnifiedCartItemTile({
    required this.name,
    required this.subtitle,
    required this.imageUrl,
    required this.quantity,
    required this.total,
  });

  final String name;
  final String subtitle;
  final String? imageUrl;
  final int quantity;
  final double total;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: Container(
              height: 54,
              width: 54,
              color: Theme.of(context).colorScheme.surfaceContainerHighest,
              child: imageUrl == null || imageUrl!.isEmpty
                  ? const Icon(
                      Icons.inventory_2_outlined,
                    )
                  : Image.network(
                      imageUrl!,
                      fit: BoxFit.cover,
                      loadingBuilder: (context, child, progress) {
                        if (progress == null) return child;
                        return const AppDelayedSkeletonBox(
                            height: 54, radius: 14);
                      },
                      errorBuilder: (_, __, ___) => const Icon(
                        Icons.inventory_2_outlined,
                      ),
                    ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
                if (subtitle.isNotEmpty)
                  Text(
                    subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                'x$quantity',
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                ),
              ),
              Text(
                '₹${total.toStringAsFixed(0)}',
                style: const TextStyle(
                  fontWeight: FontWeight.w900,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _CartStateMessage extends StatelessWidget {
  const _CartStateMessage({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.action,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback action;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 54,
            ),
            const SizedBox(height: 12),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: action,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}
