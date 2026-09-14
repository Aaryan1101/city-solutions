import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../ecommerce/data/ecommerce_api_client.dart';
import '../ecommerce/domain/ecommerce_models.dart';
import '../ecommerce/presentation/ecommerce_product_details_screen.dart';
import '../mart/data/mart_api_client.dart';
import '../mart/domain/mart_models.dart';
import '../mart/presentation/mart_product_details_screen.dart';
import '../services/data/services_api_client.dart';
import '../services/presentation/services_home_screen.dart';

enum HomeSearchScope { all, mart, ecommerce, medical }

class HomeSearchScreen extends StatefulWidget {
  const HomeSearchScreen({
    super.key,
    this.initialQuery = '',
    this.scope = HomeSearchScope.all,
    this.apiBaseUrl,
  });

  final String initialQuery;
  final HomeSearchScope scope;
  final String? apiBaseUrl;

  @override
  State<HomeSearchScreen> createState() => _HomeSearchScreenState();
}

class _HomeSearchScreenState extends State<HomeSearchScreen> {
  final _controller = TextEditingController();
  late final MartApiClient _martApi;
  late final EcommerceApiClient _ecommerceApi;
  late final EcommerceApiClient _medicalApi;
  final _servicesApi = ServicesApiClient();
  Timer? _debounce;
  int _requestId = 0;

  bool _loading = false;
  String _query = '';
  String? _error;
  List<MartProduct> _mart = const [];
  List<EcommerceProduct> _ecommerce = const [];
  List<EcommerceProduct> _medical = const [];
  List<CityService> _services = const [];
  ServicesHomeData? _servicesData;

  @override
  void initState() {
    super.initState();
    _martApi = MartApiClient(
      baseUrl: widget.scope == HomeSearchScope.mart ? widget.apiBaseUrl : null,
    );
    _ecommerceApi = EcommerceApiClient(
      baseUrl:
          widget.scope == HomeSearchScope.ecommerce ? widget.apiBaseUrl : null,
    );
    _medicalApi = EcommerceApiClient(
      baseUrl: widget.scope == HomeSearchScope.medical
          ? widget.apiBaseUrl
          : 'https://snow-grouse-381496.hostingersite.com/api/v1/medical',
    );
    _controller.text = widget.initialQuery;
    if (widget.initialQuery.trim().isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _search(widget.initialQuery);
      });
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onQueryChanged(String value) {
    _debounce?.cancel();
    final query = value.trim();
    if (query.isEmpty) {
      _requestId++;
      setState(() {
        _query = '';
        _loading = false;
        _error = null;
        _clearResults();
      });
      return;
    }
    setState(() {
      _query = query;
      _loading = true;
      _error = null;
    });
    _debounce = Timer(const Duration(milliseconds: 350), () => _search(query));
  }

  void _clearResults() {
    _mart = const [];
    _ecommerce = const [];
    _medical = const [];
    _services = const [];
    _servicesData = null;
  }

  Future<void> _search(String value) async {
    final query = value.trim();
    if (query.isEmpty) return;
    final requestId = ++_requestId;
    setState(() {
      _loading = true;
      _query = query;
      _error = null;
    });
    try {
      final includeMart = widget.scope == HomeSearchScope.all ||
          widget.scope == HomeSearchScope.mart;
      final includeEcommerce = widget.scope == HomeSearchScope.all ||
          widget.scope == HomeSearchScope.ecommerce;
      final includeMedical = widget.scope == HomeSearchScope.all ||
          widget.scope == HomeSearchScope.medical;
      final includeServices = widget.scope == HomeSearchScope.all;
      final responses = await Future.wait<dynamic>([
        if (includeMart) _martApi.searchProducts(query),
        if (includeEcommerce) _ecommerceApi.searchProducts(query),
        if (includeMedical) _medicalApi.searchProducts(query),
        if (includeServices) _servicesApi.fetchHome(),
      ]);
      if (!mounted || requestId != _requestId) return;
      var index = 0;
      final mart = includeMart
          ? responses[index++] as List<MartProduct>
          : const <MartProduct>[];
      final ecommerce = includeEcommerce
          ? responses[index++] as List<EcommerceProduct>
          : const <EcommerceProduct>[];
      final medical = includeMedical
          ? responses[index++] as List<EcommerceProduct>
          : const <EcommerceProduct>[];
      final servicesData =
          includeServices ? responses[index++] as ServicesHomeData : null;
      final normalized = query.toLowerCase();
      setState(() {
        _mart = mart;
        _ecommerce = ecommerce;
        _medical = medical;
        _servicesData = servicesData;
        _services = (servicesData?.services ?? const []).where((service) {
          final text =
              '${service.name} ${service.description} ${service.providerName}'
                  .toLowerCase();
          return text.contains(normalized);
        }).toList();
      });
    } catch (error) {
      if (!mounted || requestId != _requestId) return;
      setState(() => _error = AppErrorState.userMessage(error));
    } finally {
      if (mounted && requestId == _requestId) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasSearched = _query.isNotEmpty;
    final total =
        _mart.length + _ecommerce.length + _medical.length + _services.length;
    return Scaffold(
      appBar: AppBar(title: Text(_screenTitle)),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 28),
        children: [
          TextField(
            controller: _controller,
            autofocus: true,
            textInputAction: TextInputAction.search,
            onChanged: _onQueryChanged,
            onSubmitted: _search,
            decoration: InputDecoration(
              hintText: 'Search medicine, groceries, services...',
              prefixIcon: const Icon(Icons.search_rounded),
              suffixIcon: _loading
                  ? const Padding(
                      padding: EdgeInsets.all(14),
                      child: AppSkeletonBox(width: 16, height: 16, radius: 8),
                    )
                  : IconButton(
                      onPressed: () {
                        _controller.clear();
                        _debounce?.cancel();
                        _requestId++;
                        setState(() {
                          _query = '';
                          _loading = false;
                          _error = null;
                          _clearResults();
                        });
                      },
                      icon: const Icon(Icons.close_rounded),
                    ),
            ),
          ),
          const SizedBox(height: 16),
          if (!hasSearched) const _SearchHints(),
          if (hasSearched && _loading) ...[
            const AppSkeletonBox(height: 22, radius: 8),
            const SizedBox(height: 14),
            const AppSkeletonList(cardCount: 4),
          ],
          if (hasSearched && !_loading) ...[
            if (_error != null)
              AppErrorState(
                title: 'Could not search right now',
                detail: _error,
                onRetry: () => _search(_query),
              )
            else ...[
              Text(
                total == 0
                    ? 'No results for "$_query"'
                    : '$total results for "$_query"',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 14),
              _MartResults(products: _mart),
              _EcommerceResults(
                title: 'E-Commerce',
                products: _ecommerce,
                accent: AppTheme.primary,
              ),
              _EcommerceResults(
                title: 'Medical',
                products: _medical,
                accent: const Color(0xFFE94C3D),
                apiBaseUrl:
                    'https://snow-grouse-381496.hostingersite.com/api/v1/medical',
              ),
              _ServiceResults(
                services: _services,
                data: _servicesData,
              ),
            ],
          ],
        ],
      ),
    );
  }

  String get _screenTitle => switch (widget.scope) {
        HomeSearchScope.mart => 'Search Mart',
        HomeSearchScope.ecommerce => 'Search E-Commerce',
        HomeSearchScope.medical => 'Search Medical',
        HomeSearchScope.all => 'Search City Solution',
      };
}

class _SearchHints extends StatelessWidget {
  const _SearchHints();

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final hint in const [
          'Cold drink',
          'Cleaning',
          'Medicine',
          'Hotel',
          'Rice',
        ])
          ActionChip(
            label: Text(hint),
            onPressed: () {
              final state =
                  context.findAncestorStateOfType<_HomeSearchScreenState>();
              state?._controller.text = hint;
              state?._controller.selection = TextSelection.collapsed(
                offset: hint.length,
              );
              state?._search(hint);
            },
          ),
      ],
    );
  }
}

class _MartResults extends StatelessWidget {
  const _MartResults({required this.products});

  final List<MartProduct> products;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();
    return Column(
      children: products.take(12).map((product) {
        return _ResultTile(
          icon: Icons.eco_outlined,
          accent: const Color(0xFF20A66A),
          title: product.name,
          subtitle: _resultSubtitle(
            'Mart',
            product.vendorName ?? product.unit,
          ),
          trailing: '₹${product.sellingPrice.toStringAsFixed(0)}',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => MartProductDetailsScreen(product: product),
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _EcommerceResults extends StatelessWidget {
  const _EcommerceResults({
    required this.title,
    required this.products,
    required this.accent,
    this.apiBaseUrl,
  });

  final String title;
  final List<EcommerceProduct> products;
  final Color accent;
  final String? apiBaseUrl;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) return const SizedBox.shrink();
    return Column(
      children: products.take(12).map((product) {
        return _ResultTile(
          icon: title == 'Medical'
              ? Icons.medication_liquid_outlined
              : Icons.shopping_bag_outlined,
          accent: accent,
          title: product.name,
          subtitle: _resultSubtitle(
            title,
            product.vendorName ?? product.unit,
          ),
          trailing: '₹${product.sellingPrice.toStringAsFixed(0)}',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => EcommerceProductDetailsScreen(
                product: product,
                apiBaseUrl: apiBaseUrl,
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _ServiceResults extends StatelessWidget {
  const _ServiceResults({required this.services, required this.data});

  final List<CityService> services;
  final ServicesHomeData? data;

  @override
  Widget build(BuildContext context) {
    if (services.isEmpty || data == null) return const SizedBox.shrink();
    return Column(
      children: services.take(12).map((service) {
        return _ResultTile(
          icon: Icons.plumbing_outlined,
          accent: const Color(0xFFE8912A),
          title: service.name,
          subtitle: _resultSubtitle(
            'Services',
            service.providerName.isEmpty
                ? '${service.durationMinutes} min'
                : service.providerName,
          ),
          trailing: '₹${service.sellingPrice.toStringAsFixed(0)}',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => ServiceBookingScreen(
                service: service,
                currency: data!.config.currencySymbol,
                paymentMethods: data!.config.paymentMethods,
                policyPages: data!.config.cmsPages,
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

String _resultSubtitle(String module, String detail) {
  final value = detail.trim();
  return value.isEmpty ? module : '$module  •  $value';
}

class _ResultTile extends StatelessWidget {
  const _ResultTile({
    required this.icon,
    required this.accent,
    required this.title,
    required this.subtitle,
    required this.trailing,
    required this.onTap,
  });

  final IconData icon;
  final Color accent;
  final String title;
  final String subtitle;
  final String trailing;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          child: Ink(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: Row(
              children: [
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(icon, color: accent),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  trailing,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(width: 6),
                Icon(
                  Icons.chevron_right_rounded,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
