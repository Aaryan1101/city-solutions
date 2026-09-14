import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../../../core/adaptive_colors.dart';
import '../data/ecommerce_api_client.dart';
import '../data/ecommerce_session_store.dart';
import '../domain/ecommerce_models.dart';
import 'ecommerce_checkout_screen.dart';
import 'ecommerce_remote_image.dart';

class EcommerceCartScreen extends StatefulWidget {
  const EcommerceCartScreen(
      {super.key, required this.guestId, this.apiBaseUrl});

  final String guestId;
  final String? apiBaseUrl;

  @override
  State<EcommerceCartScreen> createState() => _EcommerceCartScreenState();
}

class _EcommerceCartScreenState extends State<EcommerceCartScreen> {
  late final EcommerceApiClient _api;
  final _couponController = TextEditingController();
  late Future<EcommerceCartData> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchCart(guestId: widget.guestId);
  }

  @override
  void dispose() {
    _couponController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Cart')),
      body: FutureBuilder<EcommerceCartData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _CartMessage(
              icon: Icons.cloud_off_rounded,
              title: 'Cart could not load',
              message: AppErrorState.userMessage(snapshot.error),
              action: FilledButton(
                onPressed: _reload,
                child: const Text('Retry'),
              ),
            );
          }

          final cart = snapshot.data!;
          if (cart.items.isEmpty) {
            return const _CartMessage(
              icon: Icons.shopping_cart_outlined,
              title: 'Your cart is empty',
              message: 'Add products from Ecommerce to continue checkout.',
            );
          }

          return Column(
            children: [
              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                  itemCount: cart.items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final item = cart.items[index];
                    return _CartItemTile(
                      item: item,
                      busy: _busy,
                      onQuantityChanged: (quantity) => _update(item, quantity),
                      onRemove: () => _remove(item),
                    );
                  },
                ),
              ),
              _CartSummary(
                cart: cart,
                couponController: _couponController,
                busy: _busy,
                onApplyCoupon: _applyCoupon,
                onRemoveCoupon: _removeCoupon,
                onCheckout: _busy || cart.minimumOrderRemaining > 0
                    ? null
                    : () async {
                        final placed = await Navigator.of(context).push<bool>(
                          MaterialPageRoute(
                            builder: (_) => EcommerceCheckoutScreen(
                              guestId: widget.guestId,
                              cart: cart,
                              sessionFuture: EcommerceSessionStore().load(),
                              apiBaseUrl: widget.apiBaseUrl,
                            ),
                          ),
                        );
                        if (placed == true) _reload();
                      },
              ),
            ],
          );
        },
      ),
    );
  }

  void _reload() {
    setState(() => _future = _api.fetchCart(guestId: widget.guestId));
  }

  Future<void> _update(EcommerceCartItem item, int quantity) async {
    setState(() => _busy = true);
    try {
      final cart = await _api.updateCart(
        guestId: widget.guestId,
        cartId: item.id,
        quantity: quantity,
      );
      if (!mounted) return;
      setState(() => _future = Future.value(cart));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _remove(EcommerceCartItem item) async {
    setState(() => _busy = true);
    try {
      final cart = await _api.removeCartItem(
        guestId: widget.guestId,
        cartId: item.id,
      );
      if (!mounted) return;
      setState(() => _future = Future.value(cart));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _applyCoupon() async {
    final code = _couponController.text.trim();
    if (code.isEmpty) return;
    setState(() => _busy = true);
    try {
      final cart = await _api.applyCoupon(
        guestId: widget.guestId,
        code: code,
      );
      if (!mounted) return;
      _couponController.text = cart.couponCode;
      setState(() => _future = Future.value(cart));
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${cart.couponCode} applied')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _removeCoupon() async {
    setState(() => _busy = true);
    try {
      final cart = await _api.removeCoupon(guestId: widget.guestId);
      if (!mounted) return;
      _couponController.clear();
      setState(() => _future = Future.value(cart));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _CartItemTile extends StatelessWidget {
  const _CartItemTile({
    required this.item,
    required this.busy,
    required this.onQuantityChanged,
    required this.onRemove,
  });

  final EcommerceCartItem item;
  final bool busy;
  final ValueChanged<int> onQuantityChanged;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 66,
            height: 66,
            decoration: BoxDecoration(
              color: AppTheme.primary.withValues(alpha: .08),
              borderRadius: BorderRadius.circular(17),
            ),
            child: EcommerceRemoteImage(
              url: item.thumbnailUrl,
              icon: Icons.shopping_basket_outlined,
              iconSize: 32,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface,
                        fontWeight: FontWeight.w900)),
                const SizedBox(height: 4),
                Text(item.unit,
                    style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 12,
                        fontWeight: FontWeight.w700)),
                const SizedBox(height: 8),
                Text('₹${item.total.toStringAsFixed(0)}',
                    style: const TextStyle(
                        color: Color(0xFF6C5CE7), fontWeight: FontWeight.w900)),
              ],
            ),
          ),
          Column(
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton.filledTonal(
                    onPressed: busy || item.quantity <= 1
                        ? null
                        : () => onQuantityChanged(item.quantity - 1),
                    icon: const Icon(Icons.remove_circle_outline),
                  ),
                  SizedBox(
                    width: 28,
                    child: Text('${item.quantity}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontWeight: FontWeight.w900)),
                  ),
                  IconButton.filledTonal(
                    onPressed: busy
                        ? null
                        : () => onQuantityChanged(item.quantity + 1),
                    icon: const Icon(Icons.add_circle_outline),
                  ),
                ],
              ),
              TextButton.icon(
                onPressed: busy ? null : onRemove,
                icon: const Icon(Icons.delete_outline, size: 18),
                label: const Text('Remove'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _CartSummary extends StatelessWidget {
  const _CartSummary({
    required this.cart,
    required this.couponController,
    required this.busy,
    required this.onApplyCoupon,
    required this.onRemoveCoupon,
    required this.onCheckout,
  });

  final EcommerceCartData cart;
  final TextEditingController couponController;
  final bool busy;
  final VoidCallback onApplyCoupon;
  final VoidCallback onRemoveCoupon;
  final VoidCallback? onCheckout;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .1),
            blurRadius: 28,
            offset: const Offset(0, -10),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (cart.couponCode.isEmpty)
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: couponController,
                      textCapitalization: TextCapitalization.characters,
                      decoration: InputDecoration(
                        hintText: 'Coupon code',
                        prefixIcon: const Icon(Icons.local_offer_outlined),
                        filled: true,
                        fillColor: Theme.of(context).scaffoldBackgroundColor,
                        border: OutlineInputBorder(
                          borderSide: BorderSide.none,
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  SizedBox(
                    height: 48,
                    child: FilledButton(
                      onPressed: busy ? null : onApplyCoupon,
                      child: const Text('Apply'),
                    ),
                  ),
                ],
              )
            else
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: adaptiveColor(
                    context,
                    light: const Color(0xFFF0EEFF),
                    dark: const Color(0xFF28243D),
                  ),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.local_offer_outlined,
                        color: Color(0xFF6C5CE7)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        '${cart.couponCode} applied',
                        style: TextStyle(
                            color: Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w900),
                      ),
                    ),
                    TextButton(
                      onPressed: busy ? null : onRemoveCoupon,
                      child: const Text('Remove'),
                    ),
                  ],
                ),
              ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Theme.of(context).scaffoldBackgroundColor,
                borderRadius: BorderRadius.circular(18),
              ),
              child: Column(
                children: [
                  _CartTotalLine(
                    label: '${cart.itemsCount} items',
                    value: '₹${cart.subtotal.toStringAsFixed(0)}',
                    mutedLabel: 'Subtotal',
                  ),
                  if (cart.couponDiscount > 0) ...[
                    const SizedBox(height: 8),
                    _CartTotalLine(
                      label: cart.couponTitle.isEmpty
                          ? 'Coupon'
                          : cart.couponTitle,
                      value: '-₹${cart.couponDiscount.toStringAsFixed(0)}',
                      valueColor: const Color(0xFF6C5CE7),
                    ),
                  ],
                  if (cart.deliveryCharge > 0) ...[
                    const SizedBox(height: 8),
                    _CartTotalLine(
                      label: 'Delivery',
                      value: '₹${cart.deliveryCharge.toStringAsFixed(0)}',
                    ),
                  ],
                  Divider(height: 22, color: Theme.of(context).dividerColor),
                  Row(
                    children: [
                      Text('Total',
                          style: TextStyle(
                              color: Theme.of(context).colorScheme.onSurface,
                              fontWeight: FontWeight.w900)),
                      const Spacer(),
                      Text('₹${cart.total.toStringAsFixed(0)}',
                          style: TextStyle(
                              color: Theme.of(context).colorScheme.onSurface,
                              fontSize: 22,
                              fontWeight: FontWeight.w900)),
                    ],
                  ),
                ],
              ),
            ),
            if (cart.minimumOrderRemaining > 0) ...[
              const SizedBox(height: 10),
              Text(
                'Add ₹${cart.minimumOrderRemaining.toStringAsFixed(0)} more to checkout.',
                style: const TextStyle(
                    color: Colors.red, fontWeight: FontWeight.w800),
              ),
            ],
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: FilledButton(
                onPressed: onCheckout,
                style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF6C5CE7)),
                child: const Text('Checkout'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CartTotalLine extends StatelessWidget {
  const _CartTotalLine({
    required this.label,
    required this.value,
    this.mutedLabel,
    this.valueColor,
  });

  final String label;
  final String value;
  final String? mutedLabel;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w900)),
              if (mutedLabel != null) ...[
                const SizedBox(height: 2),
                Text(mutedLabel!,
                    style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 12)),
              ],
            ],
          ),
        ),
        Text(value,
            style: TextStyle(
                color: valueColor ?? Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w900)),
      ],
    );
  }
}

class _CartMessage extends StatelessWidget {
  const _CartMessage({
    required this.icon,
    required this.title,
    required this.message,
    this.action,
  });

  final IconData icon;
  final String title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final safeMessage =
        action == null ? message : AppErrorState.userMessage(message);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: Theme.of(context).dividerColor),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primary.withValues(alpha: .06),
                blurRadius: 24,
                offset: const Offset(0, 14),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 76,
                height: 76,
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: .08),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: Icon(icon, color: AppTheme.primary, size: 40),
              ),
              const SizedBox(height: 16),
              Text(title,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontSize: 20,
                      fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text(safeMessage,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                      height: 1.35)),
              if (action != null) ...[const SizedBox(height: 16), action!],
            ],
          ),
        ),
      ),
    );
  }
}
