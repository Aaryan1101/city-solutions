import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/mart_api_client.dart';
import '../domain/mart_models.dart';
import 'mart_product_details_screen.dart';
import 'mart_remote_image.dart';

class MartWishlistScreen extends StatefulWidget {
  const MartWishlistScreen({
    super.key,
    required this.guestId,
    this.apiBaseUrl,
    this.showAppBar = true,
    this.title = 'Wishlist',
    this.emptyText = 'No wishlist products yet.',
  });

  final String guestId;
  final String? apiBaseUrl;
  final bool showAppBar;
  final String title;
  final String emptyText;

  @override
  State<MartWishlistScreen> createState() => _MartWishlistScreenState();
}

class _MartWishlistScreenState extends State<MartWishlistScreen> {
  late final MartApiClient _api;
  late Future<List<MartProduct>> _future;

  @override
  void initState() {
    super.initState();
    _api = MartApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchWishlist(guestId: widget.guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: widget.showAppBar ? AppBar(title: Text(widget.title)) : null,
      body: FutureBuilder<List<MartProduct>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Wishlist could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(
                () => _future = _api.fetchWishlist(guestId: widget.guestId),
              ),
            );
          }
          final products = snapshot.data ?? const <MartProduct>[];
          if (products.isEmpty) {
            return Center(
              child: Text(widget.emptyText,
                  style: const TextStyle(fontWeight: FontWeight.w800)),
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _api.fetchWishlist(guestId: widget.guestId);
              });
              await _future;
            },
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: products.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) => _WishlistTile(
                product: products[index],
                onOpen: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => MartProductDetailsScreen(
                      product: products[index],
                      apiBaseUrl: widget.apiBaseUrl,
                    ),
                  ),
                ),
                onRemove: () async {
                  await _api.toggleWishlist(
                    guestId: widget.guestId,
                    productId: products[index].id,
                  );
                  if (!mounted) return;
                  setState(() {
                    _future = _api.fetchWishlist(guestId: widget.guestId);
                  });
                },
              ),
            ),
          );
        },
      ),
    );
  }
}

class _WishlistTile extends StatelessWidget {
  const _WishlistTile({
    required this.product,
    required this.onOpen,
    required this.onRemove,
  });

  final MartProduct product;
  final VoidCallback onOpen;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onOpen,
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
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: SizedBox(
                width: 64,
                height: 64,
                child: MartRemoteImage(
                  url: product.thumbnailUrl,
                  icon: Icons.shopping_basket_outlined,
                  iconSize: 28,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(product.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 5),
                  Text('₹${product.sellingPrice.toStringAsFixed(0)}',
                      style: const TextStyle(
                          color: Color(0xFF20A66A),
                          fontWeight: FontWeight.w900)),
                ],
              ),
            ),
            IconButton(
              tooltip: 'Remove',
              onPressed: onRemove,
              icon: const Icon(Icons.favorite_rounded, color: Colors.red),
            ),
          ],
        ),
      ),
    );
  }
}
