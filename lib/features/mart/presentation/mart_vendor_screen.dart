import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/mart_api_client.dart';
import '../domain/mart_models.dart';
import 'mart_home_screen.dart';
import 'mart_product_details_screen.dart';

class MartVendorScreen extends StatefulWidget {
  const MartVendorScreen({
    super.key,
    required this.vendorId,
    required this.vendorName,
    this.apiBaseUrl,
  });

  final int vendorId;
  final String vendorName;
  final String? apiBaseUrl;

  @override
  State<MartVendorScreen> createState() => _MartVendorScreenState();
}

class _MartVendorScreenState extends State<MartVendorScreen> {
  late final MartApiClient _api;
  late Future<_VendorBundle> _future;

  @override
  void initState() {
    super.initState();
    _api = MartApiClient(baseUrl: widget.apiBaseUrl);
    _future = _load();
  }

  Future<_VendorBundle> _load() async {
    final results = await Future.wait([
      _api.fetchVendor(widget.vendorId),
      _api.fetchVendorProducts(widget.vendorId),
    ]);
    return _VendorBundle(
      vendor: results[0] as MartVendor,
      products: results[1] as List<MartProduct>,
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
                      builder: (_) => MartProductListScreen(
                        title: bundle.vendor.shopName,
                        products: bundle.products,
                        currency: '₹',
                        onFetch: (query) => _api.fetchVendorProducts(
                          widget.vendorId,
                          query: query,
                        ),
                        onProductTap: (product) => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => MartProductDetailsScreen(
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
              MartProductListBody(
                products: bundle.products,
                currency: '₹',
                onProductTap: (product) => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => MartProductDetailsScreen(
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

  final MartVendor vendor;

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
            backgroundColor: Color(0xFFE8F7EF),
            child: Icon(Icons.storefront_outlined, color: Color(0xFF20A66A)),
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

  final MartVendor vendor;
  final List<MartProduct> products;
}
