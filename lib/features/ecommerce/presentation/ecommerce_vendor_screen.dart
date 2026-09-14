import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_models.dart';
import 'ecommerce_home_screen.dart';
import 'ecommerce_product_details_screen.dart';

class EcommerceVendorScreen extends StatefulWidget {
  const EcommerceVendorScreen({
    super.key,
    required this.vendorId,
    required this.vendorName,
    this.apiBaseUrl,
  });

  final int vendorId;
  final String vendorName;
  final String? apiBaseUrl;

  @override
  State<EcommerceVendorScreen> createState() => _EcommerceVendorScreenState();
}

class _EcommerceVendorScreenState extends State<EcommerceVendorScreen> {
  late final EcommerceApiClient _api;
  late Future<_VendorBundle> _future;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _load();
  }

  Future<_VendorBundle> _load() async {
    final results = await Future.wait([
      _api.fetchVendor(widget.vendorId),
      _api.fetchVendorProducts(widget.vendorId),
    ]);
    return _VendorBundle(
      vendor: results[0] as EcommerceVendor,
      products: results[1] as List<EcommerceProduct>,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text(widget.vendorName)),
      body: FutureBuilder<_VendorBundle>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Store could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(() => _future = _load()),
            );
          }

          final bundle = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _StoreHeader(vendor: bundle.vendor),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => EcommerceProductListScreen(
                        title: bundle.vendor.shopName,
                        products: bundle.products,
                        currency: '₹',
                        onFetch: (query) => _api.fetchVendorProducts(
                          widget.vendorId,
                          query: query,
                        ),
                        onProductTap: (product) => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => EcommerceProductDetailsScreen(
                              product: product,
                              apiBaseUrl: widget.apiBaseUrl,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  icon: const Icon(Icons.tune_rounded),
                  label: const Text('Browse With Filters'),
                ),
              ),
              const SizedBox(height: 12),
              EcommerceProductListBody(
                products: bundle.products,
                currency: '₹',
                onProductTap: (product) => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => EcommerceProductDetailsScreen(
                      product: product,
                      apiBaseUrl: widget.apiBaseUrl,
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _StoreHeader extends StatelessWidget {
  const _StoreHeader({required this.vendor});

  final EcommerceVendor vendor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Row(
        children: [
          const CircleAvatar(
            radius: 26,
            backgroundColor: Color(0xFFF0EEFF),
            child: Icon(Icons.storefront_outlined, color: Color(0xFF6C5CE7)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(vendor.shopName,
                    style: const TextStyle(
                        fontSize: 18, fontWeight: FontWeight.w900)),
                if (vendor.city.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(vendor.city, style: const TextStyle()),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VendorBundle {
  const _VendorBundle({required this.vendor, required this.products});

  final EcommerceVendor vendor;
  final List<EcommerceProduct> products;
}
