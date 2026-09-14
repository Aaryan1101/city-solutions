import 'package:flutter/material.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/activity_empty_state.dart';
import '../../../core/app_greeting.dart';
import '../../../core/app_theme.dart';
import '../../mart/data/mart_session_store.dart';
import '../../zone/zone_store.dart';
import '../data/services_api_client.dart';
import '../domain/services_constants.dart';

const _servicesAppVersion =
    String.fromEnvironment('SERVICES_APP_VERSION', defaultValue: 'dev');
const _servicesDarkBg = Color(0xFF090909);
const _servicesDarkPanel = Color(0xFF151515);
const _servicesDarkCard = Color(0xFF1D1D1D);
const _servicesDarkLine = Color(0xFF2B2B2B);
const _servicesCyan = Color(0xFF00D5FF);
const _servicesLime = Color(0xFFB9FF38);
const _servicesDarkText = Color(0xFFF8F5EE);
const _servicesDarkMuted = Color(0xFF9E978D);

class ServicesHomeScreen extends StatefulWidget {
  const ServicesHomeScreen({super.key});

  @override
  State<ServicesHomeScreen> createState() => _ServicesHomeScreenState();
}

class _ServicesHomeScreenState extends State<ServicesHomeScreen> {
  final _api = ServicesApiClient();
  late Future<ServicesHomeData> _future;
  int? _categoryId;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _future = _loadHome();
  }

  Future<ServicesHomeData> _loadHome() async {
    await ZoneStore.instance.load();
    ServicesZoneSelection.currentZoneId = ZoneStore.instance.selectedZoneId;
    return _api.fetchHome();
  }

  void _retry() {
    setState(() => _future = _loadHome());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _servicesDarkBg),
      body: FutureBuilder<ServicesHomeData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.serviceGrid,
            );
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Services could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: _retry,
            );
          }
          final data = snapshot.data!;
          final forceUpdateRequired =
              data.config.forceUpdateVersion.isNotEmpty &&
                  data.config.forceUpdateVersion != _servicesAppVersion;
          if (data.config.maintenanceMode || forceUpdateRequired) {
            return _ServicesBlockingState(
              title: forceUpdateRequired ? 'Update required' : 'Maintenance',
              detail: forceUpdateRequired
                  ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                  : (data.config.maintenanceMessage.trim().isEmpty
                      ? 'Services are temporarily unavailable.'
                      : data.config.maintenanceMessage),
            );
          }
          final services = _categoryId == null
              ? data.services
              : data.services
                  .where((service) => service.categoryId == _categoryId)
                  .toList();
          final visibleServices = _query.trim().isEmpty
              ? services
              : services
                  .where((service) =>
                      service.name
                          .toLowerCase()
                          .contains(_query.trim().toLowerCase()) ||
                      service.description
                          .toLowerCase()
                          .contains(_query.trim().toLowerCase()) ||
                      service.providerName
                          .toLowerCase()
                          .contains(_query.trim().toLowerCase()))
                  .toList();
          final featured = data.services
              .where((service) => service.discountPrice != null)
              .take(6)
              .toList();
          final spotlight = featured.isNotEmpty
              ? featured.first
              : (data.services.isNotEmpty ? data.services.first : null);
          final popular = visibleServices.take(8).toList();
          final providerCount = data.services
              .map((service) => service.providerName.trim())
              .where((name) => name.isNotEmpty)
              .toSet()
              .length;
          return RefreshIndicator(
            color: _servicesCyan,
            onRefresh: () async => _retry(),
            child: SafeArea(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 92),
                children: [
                  AdaptiveModuleHeaderBand(
                    accent: _servicesCyan,
                    darkBase: _servicesDarkBg,
                    margin: const EdgeInsets.fromLTRB(-14, -10, -14, 0),
                    padding: const EdgeInsets.fromLTRB(14, 10, 14, 16),
                    child: Column(
                      children: [
                        _ServicesDarkTopBar(
                          providerCount: providerCount,
                          onSupport: () => Navigator.of(context).push(
                            MaterialPageRoute<void>(
                              builder: (_) => const ServiceSupportScreen(),
                            ),
                          ),
                          onBookings: () => Navigator.of(context).push(
                            MaterialPageRoute<void>(
                              builder: (_) => const ServiceBookingsScreen(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 10),
                        if (data.config.latestAppVersion.isNotEmpty &&
                            data.config.latestAppVersion !=
                                _servicesAppVersion) ...[
                          _ServicesVersionNotice(
                            currentVersion: _servicesAppVersion,
                            latestVersion: data.config.latestAppVersion,
                          ),
                          const SizedBox(height: 12),
                        ],
                        _ServicesDarkSearch(
                          query: _query,
                          onChanged: (value) => setState(() => _query = value),
                        ),
                        const SizedBox(height: 12),
                        _ServicesCategoryStrip(
                          categories: data.categories,
                          selectedCategoryId: _categoryId,
                          onSelected: (categoryId) =>
                              setState(() => _categoryId = categoryId),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  if (spotlight != null)
                    _ServicesOfferCard(
                      service: spotlight,
                      currency: data.config.currencySymbol,
                      onTap: () => _openBooking(context, spotlight, data),
                    ),
                  const SizedBox(height: 18),
                  const _ServicesDarkSectionHeader(
                    title: 'Top Professionals',
                    action: 'See all',
                  ),
                  const SizedBox(height: 10),
                  if (popular.isEmpty)
                    const _EmptyServices()
                  else
                    _TopProfessionalsRail(
                      services: popular,
                      currency: data.config.currencySymbol,
                      onTap: (service) => _openBooking(context, service, data),
                    ),
                  const SizedBox(height: 14),
                  _ServiceTrackCard(
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => const ServiceBookingsScreen(),
                      ),
                    ),
                  ),
                  const SizedBox(height: 18),
                  _ServicesDarkSectionHeader(
                    title:
                        _categoryId == null ? 'Available Services' : 'Services',
                    action: '${visibleServices.length} found',
                  ),
                  const SizedBox(height: 10),
                  if (visibleServices.isEmpty)
                    const _EmptyServices()
                  else
                    for (final service in visibleServices.take(10))
                      _ServiceTile(
                        service: service,
                        currency: data.config.currencySymbol,
                        onTap: () => _openBooking(context, service, data),
                      ),
                ],
              ),
            ),
          );
        },
      ),
      bottomNavigationBar: _ServicesDarkBottomNav(
        onBookings: () => Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const ServiceBookingsScreen(),
          ),
        ),
        onSupport: () => Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => const ServiceSupportScreen(),
          ),
        ),
      ),
    );
  }

  void _openBooking(
      BuildContext context, CityService service, ServicesHomeData data) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ServiceBookingScreen(
          service: service,
          currency: data.config.currencySymbol,
          paymentMethods: data.config.paymentMethods,
          policyPages: data.config.cmsPages,
        ),
      ),
    );
  }
}

class _ServicesDarkTopBar extends StatelessWidget {
  const _ServicesDarkTopBar({
    required this.providerCount,
    required this.onSupport,
    required this.onBookings,
  });

  final int providerCount;
  final VoidCallback onSupport;
  final VoidCallback onBookings;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return FutureBuilder(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        final session = snapshot.data;
        final name = appDisplayName(session?.name);
        return Column(
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 18,
                  backgroundColor: _servicesCyan.withValues(alpha: .2),
                  child: Text(
                    name.characters.first.toUpperCase(),
                    style: const TextStyle(
                      color: _servicesCyan,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        appGreeting(),
                        style: TextStyle(
                          color: colors.muted,
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      Text(
                        name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: colors.text,
                          fontSize: 13,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                ),
                _ServicesDarkIconButton(
                  tooltip: 'Support',
                  icon: Icons.support_agent_outlined,
                  onTap: onSupport,
                ),
                const SizedBox(width: 8),
                _ServicesDarkIconButton(
                  tooltip: 'Bookings',
                  icon: Icons.menu_rounded,
                  onTap: onBookings,
                ),
              ],
            ),
            const SizedBox(height: 10),
            _ServicesLocationPill(providerCount: providerCount),
          ],
        );
      },
    );
  }
}

class _ServicesLocationPill extends StatelessWidget {
  const _ServicesLocationPill({required this.providerCount});

  final int providerCount;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return ValueListenableBuilder(
      valueListenable: ZoneStore.instance.selectedLocation,
      builder: (context, location, _) {
        final label = location?.shortAddress ??
            ZoneStore.instance.selectedZone.value?.name ??
            'Lucknow, UP';
        return Container(
          height: 34,
          padding: const EdgeInsets.symmetric(horizontal: 10),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: colors.line),
          ),
          child: Row(
            children: [
              const Icon(Icons.location_on_rounded,
                  color: _servicesCyan, size: 14),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              Text(
                providerCount > 0
                    ? '$providerCount professionals'
                    : 'Services near you',
                style: TextStyle(
                  color: colors.muted,
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _ServicesDarkSearch extends StatelessWidget {
  const _ServicesDarkSearch({
    required this.query,
    required this.onChanged,
  });

  final String query;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return TextField(
      onChanged: onChanged,
      style: TextStyle(
        color: colors.text,
        fontWeight: FontWeight.w700,
      ),
      decoration: InputDecoration(
        hintText: 'Search services, professionals...',
        hintStyle: TextStyle(color: colors.muted, fontSize: 12),
        prefixIcon: Icon(Icons.search_rounded, color: colors.muted),
        suffixIcon: Icon(Icons.tune_rounded, color: colors.muted),
        filled: true,
        fillColor: colors.surface,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: _servicesCyan),
        ),
      ),
    );
  }
}

class _ServicesOfferCard extends StatelessWidget {
  const _ServicesOfferCard({
    required this.service,
    required this.currency,
    required this.onTap,
  });

  final CityService service;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _serviceOfferTitle(service, currency),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: _servicesCyan,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  service.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  height: 30,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _servicesCyan,
                      foregroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                    onPressed: onTap,
                    child: const Text(
                      'Book Now',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            width: 86,
            height: 78,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  _servicesCyan.withValues(alpha: .2),
                  Colors.black,
                ],
              ),
            ),
            child: Icon(
              _serviceIcon(service.name),
              color: _servicesCyan,
              size: 42,
            ),
          ),
        ],
      ),
    );
  }
}

class _ServicesDarkSectionHeader extends StatelessWidget {
  const _ServicesDarkSectionHeader({
    required this.title,
    this.action,
  });

  final String title;
  final String? action;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _servicesDarkText);
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: textColor,
              fontSize: 14,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        if (action != null)
          Text(
            action!,
            style: const TextStyle(
              color: _servicesCyan,
              fontSize: 10,
              fontWeight: FontWeight.w900,
            ),
          ),
      ],
    );
  }
}

class _TopProfessionalsRail extends StatelessWidget {
  const _TopProfessionalsRail({
    required this.services,
    required this.currency,
    required this.onTap,
  });

  final List<CityService> services;
  final String currency;
  final ValueChanged<CityService> onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 146,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: services.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final service = services[index];
          return _ProfessionalCard(
            service: service,
            currency: currency,
            onTap: () => onTap(service),
          );
        },
      ),
    );
  }
}

class _ProfessionalCard extends StatelessWidget {
  const _ProfessionalCard({
    required this.service,
    required this.currency,
    required this.onTap,
  });

  final CityService service;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    final provider = service.providerName.trim().isEmpty
        ? 'City Partner'
        : service.providerName.trim();
    return Container(
      width: 118,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: colors.line),
      ),
      child: Column(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: _servicesCyan.withValues(alpha: .15),
            child: Text(
              provider.characters.first.toUpperCase(),
              style: const TextStyle(
                color: _servicesCyan,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            provider,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: colors.text,
              fontSize: 10,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(
            service.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: colors.muted,
              fontSize: 9,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.star_rounded, color: _servicesCyan, size: 12),
              const SizedBox(width: 2),
              Text(
                '4.${(service.id % 4) + 6}',
                style: const TextStyle(
                  color: _servicesCyan,
                  fontSize: 9,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(width: 5),
              Flexible(
                child: Text(
                  '$currency${service.sellingPrice.toStringAsFixed(0)}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.muted,
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const Spacer(),
          SizedBox(
            width: double.infinity,
            height: 26,
            child: OutlinedButton(
              style: OutlinedButton.styleFrom(
                foregroundColor: _servicesCyan,
                side: const BorderSide(color: _servicesCyan),
                padding: EdgeInsets.zero,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              onPressed: onTap,
              child: const Text(
                'Book',
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ServiceTrackCard extends StatelessWidget {
  const _ServiceTrackCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: const BoxDecoration(
              color: _servicesCyan,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.schedule_rounded,
                color: Colors.black, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Track service booking',
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'Live status and provider updates',
                  style: TextStyle(
                    color: colors.muted,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          OutlinedButton(
            style: OutlinedButton.styleFrom(
              foregroundColor: _servicesCyan,
              side: const BorderSide(color: _servicesCyan),
              padding: const EdgeInsets.symmetric(horizontal: 14),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(999)),
            ),
            onPressed: onTap,
            child: const Text(
              'Track',
              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900),
            ),
          ),
        ],
      ),
    );
  }
}

String _serviceOfferTitle(CityService service, String currency) {
  if (service.discountPrice != null && service.discountPrice! < service.price) {
    return '$currency${service.sellingPrice.toStringAsFixed(0)} Inspection';
  }
  return '$currency${service.sellingPrice.toStringAsFixed(0)} Service';
}

IconData _serviceIcon(String value) {
  final lower = value.toLowerCase();
  if (lower.contains('electric')) return Icons.electrical_services_outlined;
  if (lower.contains('plumb')) return Icons.plumbing_outlined;
  if (lower.contains('paint')) return Icons.format_paint_outlined;
  if (lower.contains('clean')) return Icons.cleaning_services_outlined;
  if (lower.contains('ac')) return Icons.ac_unit_outlined;
  if (lower.contains('appliance')) return Icons.tv_outlined;
  if (lower.contains('salon') || lower.contains('beauty')) {
    return Icons.content_cut_rounded;
  }
  return Icons.home_repair_service_outlined;
}

class _ServicesDarkBottomNav extends StatelessWidget {
  const _ServicesDarkBottomNav({
    required this.onBookings,
    required this.onSupport,
  });

  final VoidCallback onBookings;
  final VoidCallback onSupport;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return DecoratedBox(
      decoration: BoxDecoration(
        color: colors.surface,
        border: Border(top: BorderSide(color: colors.line)),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 64,
          child: Row(
            children: [
              _ServicesNavItem(
                icon: Icons.home_rounded,
                label: 'Home',
                selected: true,
                onTap: () {},
              ),
              _ServicesNavItem(
                icon: Icons.calendar_month_outlined,
                label: 'Bookings',
                onTap: onBookings,
              ),
              _ServicesNavItem(
                icon: Icons.search_rounded,
                label: 'Search',
                onTap: () {},
              ),
              _ServicesNavItem(
                icon: Icons.local_offer_outlined,
                label: 'Offers',
                onTap: () {},
              ),
              _ServicesNavItem(
                icon: Icons.person_outline,
                label: 'Support',
                onTap: onSupport,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ServicesNavItem extends StatelessWidget {
  const _ServicesNavItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.selected = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final muted = adaptiveMuted(context, _servicesDarkMuted);
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: selected ? _servicesLime : muted,
              size: 18,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: selected ? _servicesLime : muted,
                fontSize: 9,
                fontWeight: FontWeight.w900,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ServicesDarkIconButton extends StatelessWidget {
  const _ServicesDarkIconButton({
    required this.tooltip,
    required this.icon,
    required this.onTap,
  });

  final String tooltip;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final text = adaptiveText(context, _servicesDarkText);
    return Tooltip(
      message: tooltip,
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: onTap,
        child: SizedBox(
          width: 38,
          height: 38,
          child: Icon(icon, color: text, size: 20),
        ),
      ),
    );
  }
}

class _ServicesBlockingState extends StatelessWidget {
  const _ServicesBlockingState({
    required this.title,
    required this.detail,
  });

  final String title;
  final String detail;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: adaptiveSurface(context, _servicesDarkCard),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: adaptiveLine(context, _servicesDarkLine)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.info_outline_rounded,
                  color: Color(0xFFFF9800), size: 44),
              const SizedBox(height: 12),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                detail,
                textAlign: TextAlign.center,
                style: const TextStyle(height: 1.5),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ServicesVersionNotice extends StatelessWidget {
  const _ServicesVersionNotice({
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
        borderRadius: BorderRadius.circular(18),
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

class _ServicesSectionHeader extends StatelessWidget {
  const _ServicesSectionHeader({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(title,
              style:
                  const TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
        ),
      ],
    );
  }
}

class _ServicesCategoryStrip extends StatelessWidget {
  const _ServicesCategoryStrip({
    required this.categories,
    required this.selectedCategoryId,
    required this.onSelected,
  });

  final List<ServiceCategory> categories;
  final int? selectedCategoryId;
  final ValueChanged<int?> onSelected;

  @override
  Widget build(BuildContext context) {
    final items = <ServiceCategory?>[null, ...categories];
    return SizedBox(
      height: 78,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final category = items[index];
          final selected = category == null
              ? selectedCategoryId == null
              : selectedCategoryId == category.id;
          return InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => onSelected(category?.id),
            child: SizedBox(
              width: 74,
              child: Column(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: selected
                          ? _servicesCyan.withValues(alpha: .18)
                          : _servicesDarkPanel,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: selected ? _servicesCyan : _servicesDarkLine,
                      ),
                    ),
                    child: Icon(
                      _categoryIcon(category?.name ?? 'all'),
                      color: _servicesCyan,
                      size: 20,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    category?.name ?? 'All',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: selected ? _servicesDarkText : _servicesDarkMuted,
                      fontSize: 10,
                      height: 1.05,
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

  IconData _categoryIcon(String value) {
    final lower = value.toLowerCase();
    if (lower.contains('plumb')) return Icons.plumbing_outlined;
    if (lower.contains('paint')) return Icons.format_paint_outlined;
    if (lower.contains('ac')) return Icons.ac_unit_outlined;
    if (lower.contains('appliance')) return Icons.tv_outlined;
    if (lower.contains('clean')) return Icons.cleaning_services_outlined;
    if (lower.contains('beauty') || lower.contains('salon')) {
      return Icons.spa_outlined;
    }
    if (lower.contains('repair') || lower.contains('electric')) {
      return Icons.handyman_outlined;
    }
    return Icons.more_horiz_rounded;
  }
}

class ServiceBookingScreen extends StatefulWidget {
  const ServiceBookingScreen({
    super.key,
    required this.service,
    required this.currency,
    required this.paymentMethods,
    required this.policyPages,
  });

  final CityService service;
  final String currency;
  final List<ServicePaymentMethod> paymentMethods;
  final List<ServiceCmsPage> policyPages;

  @override
  State<ServiceBookingScreen> createState() => _ServiceBookingScreenState();
}

class _ServiceBookingScreenState extends State<ServiceBookingScreen> {
  final _api = ServicesApiClient();
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController(text: 'Customer');
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _address = TextEditingController(
    text: ZoneStore.instance.selectedLocation.value?.address ?? '',
  );
  final _date = TextEditingController();
  final _time = TextEditingController();
  final _note = TextEditingController();
  final _paymentReference = TextEditingController();
  final _paymentNote = TextEditingController();
  Future<List<ServiceSlot>>? _slotsFuture;
  ServiceSlot? _selectedSlot;
  final Set<int> _selectedAddonIds = <int>{};
  bool _placing = false;
  bool _checklistAccepted = false;
  String _paymentMethod = 'cash_on_service';

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _address.dispose();
    _date.dispose();
    _time.dispose();
    _note.dispose();
    _paymentReference.dispose();
    _paymentNote.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.service.name)),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _ServiceSummary(service: widget.service, currency: widget.currency),
            if (widget.service.checklist.isNotEmpty) ...[
              const SizedBox(height: 14),
              _ServiceChecklistCard(
                items: widget.service.checklist,
                accepted: _checklistAccepted,
                onChanged: (value) =>
                    setState(() => _checklistAccepted = value),
              ),
            ],
            if (widget.service.addons.isNotEmpty) ...[
              const SizedBox(height: 14),
              _AddonsCard(
                addons: widget.service.addons,
                currency: widget.currency,
                selectedIds: _selectedAddonIds,
                onChanged: (addon, selected) => setState(() {
                  if (selected) {
                    _selectedAddonIds.add(addon.id);
                  } else {
                    _selectedAddonIds.remove(addon.id);
                  }
                }),
              ),
            ],
            const SizedBox(height: 14),
            _Field(
                controller: _name, label: 'Name', icon: Icons.person_outline),
            const SizedBox(height: 10),
            _Field(
                controller: _phone, label: 'Phone', icon: Icons.phone_outlined),
            const SizedBox(height: 10),
            _Field(
              controller: _email,
              label: 'Email optional',
              icon: Icons.email_outlined,
              required: false,
            ),
            const SizedBox(height: 10),
            _Field(
              controller: _address,
              label: 'Service Address',
              icon: Icons.location_on_outlined,
              maxLines: 3,
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _date,
                    readOnly: true,
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Required'
                        : null,
                    decoration: InputDecoration(
                      labelText: 'Preferred Date',
                      prefixIcon: const Icon(Icons.event_outlined),
                      filled: true,
                      fillColor: Theme.of(context).cardColor,
                    ),
                    onTap: _pickDate,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _time,
                    readOnly: true,
                    decoration: InputDecoration(
                      labelText: 'Preferred Time',
                      prefixIcon: const Icon(Icons.schedule_outlined),
                      filled: true,
                      fillColor: Theme.of(context).cardColor,
                    ),
                    onTap: _pickTime,
                  ),
                ),
              ],
            ),
            if (_slotsFuture != null) ...[
              const SizedBox(height: 10),
              FutureBuilder<List<ServiceSlot>>(
                future: _slotsFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const AppSkeletonList(cardCount: 2);
                  }
                  final slots = snapshot.data ?? const <ServiceSlot>[];
                  if (slots.isEmpty) {
                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF7ED),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFFFE0B2)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.schedule_outlined,
                              color: Color(0xFFF59E0B)),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'No configured slots for this date. Pick a preferred time and admin will confirm availability.',
                              style: TextStyle(
                                  height: 1.35, fontWeight: FontWeight.w700),
                            ),
                          ),
                        ],
                      ),
                    );
                  }
                  return Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final slot in slots)
                        ChoiceChip(
                          label: Text(
                            slot.providerName.isEmpty
                                ? slot.label
                                : '${slot.label} • ${slot.providerName}',
                          ),
                          selected: _selectedSlot?.id == slot.id,
                          onSelected: slot.isAvailable
                              ? (_) => setState(() {
                                    _selectedSlot = slot;
                                    _time.text = slot.label;
                                  })
                              : null,
                        ),
                    ],
                  );
                },
              ),
            ],
            const SizedBox(height: 10),
            _Field(
              controller: _note,
              label: 'Note optional',
              icon: Icons.notes_outlined,
              required: false,
              maxLines: 2,
            ),
            const SizedBox(height: 14),
            _ServicePaymentSection(
              methods: widget.paymentMethods.isEmpty
                  ? const [
                      ServicePaymentMethod(
                        id: 'cash_on_service',
                        title: 'Cash On Service',
                        description: 'Pay after service completion.',
                      ),
                      ServicePaymentMethod(
                        id: 'online_payment',
                        title: 'Online Payment',
                        description: 'Submit payment for admin verification.',
                      ),
                    ]
                  : widget.paymentMethods,
              selectedId: _paymentMethod,
              onSelected: (id) => setState(() => _paymentMethod = id),
            ),
            if (_paymentMethod != 'cash_on_service') ...[
              const SizedBox(height: 10),
              _Field(
                controller: _paymentReference,
                label: 'Payment Reference',
                icon: Icons.receipt_long_outlined,
              ),
              const SizedBox(height: 10),
              _Field(
                controller: _paymentNote,
                label: 'Payment Note optional',
                icon: Icons.notes_outlined,
                required: false,
                maxLines: 2,
              ),
            ],
            const SizedBox(height: 14),
            _ServicePolicyCard(
              pages: widget.policyPages,
              warrantyDays: widget.service.warrantyDays,
            ),
            const SizedBox(height: 18),
            SizedBox(
              height: 52,
              child: FilledButton.icon(
                onPressed: _placing ? null : _book,
                icon: _placing
                    ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                    : const Icon(Icons.check_circle_outline),
                label: Text(_placing ? 'Booking...' : 'Book Service'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _book() async {
    if (!_formKey.currentState!.validate()) return;
    if (widget.service.checklist.isNotEmpty && !_checklistAccepted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please accept the service preparation checklist'),
        ),
      );
      return;
    }
    setState(() => _placing = true);
    try {
      final response = await _api.placeBooking(
        serviceId: widget.service.id,
        guestId: servicesGuestId,
        customerName: _name.text.trim(),
        customerPhone: _phone.text.trim(),
        customerEmail: _email.text.trim(),
        address: _address.text.trim(),
        preferredDate: _date.text.trim(),
        preferredTime: _time.text.trim(),
        slotId: _selectedSlot?.id,
        addonIds: _selectedAddonIds.toList(),
        checklistAccepted: _checklistAccepted,
        paymentMethod: _paymentMethod,
        paymentReference: _paymentReference.text.trim(),
        paymentNote: _paymentNote.text.trim(),
        note: _note.text.trim(),
      );
      if (!mounted) return;
      final data = response['data'] is Map
          ? Map<String, dynamic>.from(response['data'] as Map)
          : <String, dynamic>{};
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => ServiceBookingSuccessScreen(
            bookingNumber: data['booking_number']?.toString() ?? '',
            amount: (data['amount'] is num)
                ? (data['amount'] as num).toDouble()
                : double.tryParse(data['amount']?.toString() ?? '') ??
                    widget.service.sellingPrice,
            currency: widget.currency,
          ),
        ),
      );
    } finally {
      if (mounted) setState(() => _placing = false);
    }
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 45)),
    );
    if (picked == null) return;
    final date =
        '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    setState(() {
      _date.text = date;
      _time.clear();
      _selectedSlot = null;
      _slotsFuture = _api.fetchSlots(serviceId: widget.service.id, date: date);
    });
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked == null) return;
    setState(() {
      _selectedSlot = null;
      _time.text =
          '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
    });
  }
}

class _ServicePaymentSection extends StatelessWidget {
  const _ServicePaymentSection({
    required this.methods,
    required this.selectedId,
    required this.onSelected,
  });

  final List<ServicePaymentMethod> methods;
  final String selectedId;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _servicesDarkCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _servicesDarkLine)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .05),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Payment Method',
              style: TextStyle(fontWeight: FontWeight.w900)),
          const SizedBox(height: 10),
          for (final method in methods.where((method) => method.id.isNotEmpty))
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () => onSelected(method.id),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: selectedId == method.id
                        ? AppTheme.primary.withValues(alpha: .06)
                        : adaptiveSurface(context, _servicesDarkCard),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: selectedId == method.id
                          ? AppTheme.primary
                          : Theme.of(context).dividerColor,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        selectedId == method.id
                            ? Icons.radio_button_checked
                            : Icons.radio_button_off,
                        color: selectedId == method.id
                            ? AppTheme.primary
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(method.title,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w900)),
                            if (method.description.isNotEmpty) ...[
                              const SizedBox(height: 3),
                              Text(method.description,
                                  style: const TextStyle(fontSize: 12)),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ServicePolicyCard extends StatelessWidget {
  const _ServicePolicyCard({
    required this.pages,
    required this.warrantyDays,
  });

  final List<ServiceCmsPage> pages;
  final int warrantyDays;

  @override
  Widget build(BuildContext context) {
    final visible = pages
        .where((page) =>
            {
              'terms-conditions',
              'cancellation-policy',
              'refund-policy',
            }.contains(page.slug) &&
            page.content.trim().isNotEmpty)
        .toList();
    final fallback = warrantyDays > 0
        ? 'Cancellation requests are reviewed by admin. This service includes $warrantyDays day warranty after completion where applicable.'
        : 'Cancellation requests are reviewed by admin. Refunds and rescheduling depend on provider availability, booking status and admin approval.';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _servicesDarkCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _servicesDarkLine)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFF59E0B).withValues(alpha: .1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.policy_outlined, color: Color(0xFFF59E0B)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Before you book',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                if (visible.isEmpty)
                  Text(
                    fallback,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      height: 1.35,
                    ),
                  )
                else
                  for (final page in visible.take(3))
                    Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: Text(
                        '${page.title}: ${_policyPreview(page.content)}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          height: 1.35,
                        ),
                      ),
                    ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static String _policyPreview(String value) {
    return value.replaceAll(RegExp(r'\s+'), ' ').trim();
  }
}

class ServiceBookingSuccessScreen extends StatelessWidget {
  const ServiceBookingSuccessScreen({
    super.key,
    required this.bookingNumber,
    required this.amount,
    required this.currency,
  });

  final String bookingNumber;
  final double amount;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: adaptiveSurface(context, _servicesDarkCard),
                borderRadius: BorderRadius.circular(28),
                border:
                    Border.all(color: adaptiveLine(context, _servicesDarkLine)),
                boxShadow: [
                  BoxShadow(
                    color: AppTheme.primary.withValues(alpha: .08),
                    blurRadius: 28,
                    offset: const Offset(0, 18),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 92,
                    height: 92,
                    decoration: const BoxDecoration(
                      color: Color(0xFFF59E0B),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check_rounded,
                        color: Colors.white, size: 54),
                  ),
                  const SizedBox(height: 22),
                  const Text('Booking Placed Successfully',
                      textAlign: TextAlign.center,
                      style:
                          TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
                  const SizedBox(height: 10),
                  Text(
                    bookingNumber.isEmpty
                        ? 'Your booking request has been sent to admin.'
                        : 'Booking ID: $bookingNumber',
                    textAlign: TextAlign.center,
                    style: const TextStyle(),
                  ),
                  const SizedBox(height: 8),
                  Text('$currency${amount.toStringAsFixed(2)}',
                      style: const TextStyle(
                          color: Color(0xFFF59E0B),
                          fontSize: 22,
                          fontWeight: FontWeight.w900)),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: FilledButton(
                      style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFFF59E0B)),
                      onPressed: () => Navigator.of(context).pushReplacement(
                        MaterialPageRoute<void>(
                          builder: (_) => const ServiceBookingsScreen(),
                        ),
                      ),
                      child: const Text('View Bookings'),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextButton(
                    onPressed: () => Navigator.of(context)
                        .popUntil((route) => route.isFirst),
                    child: const Text('Back To Home'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class ServiceBookingsScreen extends StatefulWidget {
  const ServiceBookingsScreen({super.key, this.showAppBar = true});

  final bool showAppBar;

  @override
  State<ServiceBookingsScreen> createState() => _ServiceBookingsScreenState();
}

class ServiceSupportScreen extends StatefulWidget {
  const ServiceSupportScreen({super.key});

  @override
  State<ServiceSupportScreen> createState() => _ServiceSupportScreenState();
}

class _ServiceSupportScreenState extends State<ServiceSupportScreen> {
  final _api = ServicesApiClient();
  final _subject = TextEditingController();
  final _message = TextEditingController();
  late Future<List<ServiceSupportThread>> _future;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchSupportThreads(guestId: servicesGuestId);
  }

  @override
  void dispose() {
    _subject.dispose();
    _message.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Service Support')),
      body: RefreshIndicator(
        onRefresh: () async {
          setState(() {
            _future = _api.fetchSupportThreads(guestId: servicesGuestId);
          });
          await _future;
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _ServiceDetailCard(
              title: 'New Request',
              children: [
                TextField(
                  controller: _subject,
                  decoration: const InputDecoration(
                    labelText: 'Subject',
                    prefixIcon: Icon(Icons.subject_outlined),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _message,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Message',
                    prefixIcon: Icon(Icons.chat_bubble_outline),
                  ),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _sending ? null : _send,
                    icon: const Icon(Icons.send_outlined),
                    label: Text(_sending ? 'Sending...' : 'Send Request'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            const _ServicesSectionHeader(title: 'Requests'),
            const SizedBox(height: 10),
            FutureBuilder<List<ServiceSupportThread>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const AppSkeletonList(cardCount: 3);
                }
                final threads = snapshot.data ?? const <ServiceSupportThread>[];
                if (threads.isEmpty) {
                  return const _EmptyServices(
                    message: 'No service support requests yet.',
                  );
                }
                return Column(
                  children: [
                    for (final thread in threads)
                      Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        child: Material(
                          color: adaptiveSurface(context, _servicesDarkCard),
                          borderRadius: BorderRadius.circular(8),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(8),
                            onTap: () async {
                              await Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (_) => ServiceSupportThreadScreen(
                                      thread: thread),
                                ),
                              );
                              if (!mounted) return;
                              setState(() {
                                _future = _api.fetchSupportThreads(
                                  guestId: servicesGuestId,
                                );
                              });
                            },
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: Theme.of(context).dividerColor),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(thread.subject,
                                            style: const TextStyle(
                                                fontWeight: FontWeight.w900)),
                                        const SizedBox(height: 4),
                                        Text(
                                          [
                                            thread.status,
                                            if (thread.bookingId > 0)
                                              'Booking #${thread.bookingId}',
                                            thread.createdAt,
                                          ].join(' • '),
                                          style: const TextStyle(
                                            fontSize: 12,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (thread.unreadCount > 0)
                                    _StatusPill(
                                        label: '${thread.unreadCount} new'),
                                  const Icon(
                                    Icons.chevron_right_rounded,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _send() async {
    final message = _message.text.trim();
    if (message.isEmpty) return;
    setState(() => _sending = true);
    try {
      await _api.createSupportThread(
        guestId: servicesGuestId,
        subject: _subject.text.trim().isEmpty
            ? 'Service support request'
            : _subject.text.trim(),
        message: message,
      );
      _subject.clear();
      _message.clear();
      if (!mounted) return;
      setState(() {
        _future = _api.fetchSupportThreads(guestId: servicesGuestId);
      });
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}

class ServiceSupportThreadScreen extends StatefulWidget {
  const ServiceSupportThreadScreen({super.key, required this.thread});

  final ServiceSupportThread thread;

  @override
  State<ServiceSupportThreadScreen> createState() =>
      _ServiceSupportThreadScreenState();
}

class _ServiceSupportThreadScreenState
    extends State<ServiceSupportThreadScreen> {
  final _api = ServicesApiClient();
  final _reply = TextEditingController();
  late Future<ServiceSupportThreadDetails> _future;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchSupportThread(
      threadId: widget.thread.id,
      guestId: servicesGuestId,
    );
  }

  @override
  void dispose() {
    _reply.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text(widget.thread.subject)),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<ServiceSupportThreadDetails>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const AppSkeletonPage();
                }
                if (snapshot.hasError) {
                  return AppErrorState(
                    title: 'Could not load support',
                    detail: AppErrorState.userMessage(snapshot.error),
                    onRetry: () => setState(
                      () => _future = _api.fetchSupportThread(
                        threadId: widget.thread.id,
                        guestId: servicesGuestId,
                      ),
                    ),
                  );
                }
                final messages =
                    snapshot.data?.messages ?? const <ServiceSupportMessage>[];
                if (messages.isEmpty) {
                  return const _EmptyServices(message: 'No messages yet.');
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final message = messages[index];
                    final mine = message.senderType == 'customer';
                    return Align(
                      alignment:
                          mine ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        constraints: const BoxConstraints(maxWidth: 320),
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: mine
                              ? AppTheme.primary
                              : adaptiveSurface(context, _servicesDarkCard),
                          borderRadius: BorderRadius.circular(8),
                          border:
                              mine ? null : Border.all(color: Theme.of(context).dividerColor),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              message.senderName.isEmpty
                                  ? message.senderType
                                  : message.senderName,
                              style: TextStyle(
                                color: mine
                                    ? Colors.white70
                                    : Theme.of(context)
                                        .colorScheme
                                        .onSurfaceVariant,
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              message.message,
                              style: TextStyle(
                                color: mine
                                    ? Colors.white
                                    : Theme.of(context).colorScheme.onSurface,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
          SafeArea(
            child: Container(
              padding: const EdgeInsets.all(10),
              color: adaptiveSurface(context, _servicesDarkPanel),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _reply,
                      minLines: 1,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        hintText: 'Type reply',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _sending ? null : _send,
                    icon: const Icon(Icons.send_outlined),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _send() async {
    final message = _reply.text.trim();
    if (message.isEmpty) return;
    setState(() => _sending = true);
    try {
      await _api.replySupportThread(
        threadId: widget.thread.id,
        guestId: servicesGuestId,
        message: message,
      );
      _reply.clear();
      if (!mounted) return;
      setState(() {
        _future = _api.fetchSupportThread(
          threadId: widget.thread.id,
          guestId: servicesGuestId,
        );
      });
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}

class _ServiceBookingsScreenState extends State<ServiceBookingsScreen> {
  final _api = ServicesApiClient();
  late Future<List<ServiceBooking>> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = _loadBookings();
  }

  Future<List<ServiceBooking>> _loadBookings() async {
    final session = await MartSessionStore().load();
    if (!session.isLoggedIn) return const <ServiceBooking>[];
    return _api.fetchBookings(guestId: servicesGuestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: widget.showAppBar ? AppBar(title: const Text('Bookings')) : null,
      body: FutureBuilder<List<ServiceBooking>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          final bookings = snapshot.data ?? const <ServiceBooking>[];
          if (bookings.isEmpty) {
            return const _EmptyServices(message: 'No service bookings yet.');
          }
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemBuilder: (context, index) {
              final booking = bookings[index];
              return Material(
                color: adaptiveSurface(context, _servicesDarkCard),
                borderRadius: BorderRadius.circular(18),
                child: InkWell(
                  borderRadius: BorderRadius.circular(18),
                  onTap: () async {
                    await Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) =>
                            ServiceBookingDetailsScreen(booking: booking),
                      ),
                    );
                    if (!mounted) return;
                    setState(() => _future = _loadBookings());
                  },
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: Theme.of(context).dividerColor),
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primary.withValues(alpha: .04),
                          blurRadius: 18,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 46,
                              height: 46,
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFF3E0),
                                borderRadius: BorderRadius.circular(15),
                              ),
                              child: const Icon(Icons.home_repair_service,
                                  color: Color(0xFFF59E0B)),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(booking.bookingNumber,
                                      style: const TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.w900)),
                                  const SizedBox(height: 3),
                                  Text(booking.serviceName,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w700)),
                                ],
                              ),
                            ),
                            const Icon(
                              Icons.chevron_right_rounded,
                            ),
                          ],
                        ),
                        if (booking.providerName.isNotEmpty) ...[
                          const SizedBox(height: 10),
                          Text('Provider: ${booking.providerName}',
                              style: const TextStyle()),
                        ],
                        if (booking.preferredDate.isNotEmpty ||
                            booking.preferredTime.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            '${booking.preferredDate} ${booking.preferredTime}'
                                .trim(),
                            style: const TextStyle(),
                          ),
                        ],
                        const SizedBox(height: 6),
                        Text(
                            '${_bookingStatusLabel(booking.status)} • ₹${booking.amount.toStringAsFixed(2)}',
                            style: const TextStyle(
                                color: AppTheme.primary,
                                fontWeight: FontWeight.w900)),
                        if (booking.addonTotal > 0) ...[
                          const SizedBox(height: 4),
                          Text(
                              'Add-ons: ₹${booking.addonTotal.toStringAsFixed(2)}',
                              style: const TextStyle()),
                        ],
                        if (![
                          'completed',
                          'cancelled',
                          'cancellation_requested'
                        ].contains(booking.status)) ...[
                          const SizedBox(height: 10),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              if (['pending', 'accepted']
                                      .contains(booking.status) &&
                                  booking.rescheduleCount < 2)
                                OutlinedButton.icon(
                                  onPressed:
                                      _busy ? null : () => _reschedule(booking),
                                  icon: const Icon(Icons.event_repeat_outlined),
                                  label: const Text('Reschedule'),
                                ),
                              const SizedBox(width: 8),
                              OutlinedButton.icon(
                                onPressed:
                                    _busy ? null : () => _cancel(booking),
                                icon: const Icon(Icons.cancel_outlined),
                                label: const Text('Request Cancel'),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              );
            },
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemCount: bookings.length,
          );
        },
      ),
    );
  }

  Future<void> _cancel(ServiceBooking booking) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Request cancellation?'),
        content: const Text(
          'This will send a cancellation request to admin. The booking will not be cancelled until admin approves it.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('No'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Send Request'),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    setState(() => _busy = true);
    try {
      await _api.cancelBooking(bookingId: booking.id, guestId: servicesGuestId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cancellation request sent to admin')),
      );
      setState(() => _future = _loadBookings());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _reschedule(ServiceBooking booking) async {
    final result = await showModalBottomSheet<_RescheduleResult>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _RescheduleSheet(api: _api, booking: booking),
    );
    if (result == null) return;
    setState(() => _busy = true);
    try {
      await _api.rescheduleBooking(
        bookingId: booking.id,
        guestId: servicesGuestId,
        preferredDate: result.date,
        preferredTime: result.time,
        slotId: result.slotId,
      );
      if (!mounted) return;
      setState(() => _future = _loadBookings());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String _bookingStatusLabel(String status) {
    return switch (status) {
      'cancellation_requested' => 'Cancellation requested',
      'pending' => 'Pending',
      'accepted' => 'Accepted',
      'ongoing' => 'Ongoing',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
      _ => status,
    };
  }
}

class ServiceBookingDetailsScreen extends StatefulWidget {
  const ServiceBookingDetailsScreen({super.key, required this.booking});

  final ServiceBooking booking;

  @override
  State<ServiceBookingDetailsScreen> createState() =>
      _ServiceBookingDetailsScreenState();
}

class _ServiceBookingDetailsScreenState
    extends State<ServiceBookingDetailsScreen> {
  final _api = ServicesApiClient();
  late Future<ServiceBooking> _future;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchBooking(
      bookingId: widget.booking.id,
      guestId: servicesGuestId,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Booking Details')),
      body: FutureBuilder<ServiceBooking>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          final booking = snapshot.data ?? widget.booking;
          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _api.fetchBooking(
                  bookingId: booking.id,
                  guestId: servicesGuestId,
                );
              });
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _ServiceDetailCard(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            booking.bookingNumber,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                        _StatusPill(label: _bookingStatusLabel(booking.status)),
                      ],
                    ),
                    const SizedBox(height: 14),
                    _DetailRow('Service', booking.serviceName),
                    if (booking.providerName.isNotEmpty)
                      _DetailRow('Provider', booking.providerName),
                    _DetailRow(
                        'Amount', '₹${booking.amount.toStringAsFixed(2)}'),
                    _DetailRow('Payment',
                        '${_paymentLabel(booking.paymentMethod)} / ${booking.paymentStatus}'),
                    if (booking.preferredDate.isNotEmpty ||
                        booking.preferredTime.isNotEmpty)
                      _DetailRow(
                        'Schedule',
                        '${booking.preferredDate} ${booking.preferredTime}'
                            .trim(),
                      ),
                    if (booking.warrantyDays > 0)
                      _DetailRow(
                        'Warranty',
                        booking.warrantyUntil.isNotEmpty
                            ? 'Covered until ${booking.warrantyUntil}'
                            : '${booking.warrantyDays} days after completion',
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                _ServiceDetailCard(
                  title: 'Customer',
                  children: [
                    _DetailRow('Name', booking.customerName),
                    _DetailRow('Phone', booking.customerPhone),
                    if (booking.customerEmail.isNotEmpty)
                      _DetailRow('Email', booking.customerEmail),
                    _DetailRow('Address', booking.address),
                  ],
                ),
                if (booking.addons.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _ServiceDetailCard(
                    title: 'Add-ons',
                    children: [
                      for (final addon in booking.addons)
                        _DetailRow(
                          addon.name,
                          '₹${addon.price.toStringAsFixed(2)}',
                        ),
                    ],
                  ),
                ],
                if (booking.note.isNotEmpty ||
                    booking.adminNote.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _ServiceDetailCard(
                    title: 'Notes',
                    children: [
                      if (booking.note.isNotEmpty)
                        _DetailRow('Customer note', booking.note),
                      if (booking.adminNote.isNotEmpty)
                        _DetailRow('Admin note', booking.adminNote),
                    ],
                  ),
                ],
                if (_hasWarrantyCoverage(booking)) ...[
                  const SizedBox(height: 12),
                  _ServiceDetailCard(
                    title: 'Warranty Support',
                    children: [
                      Text(
                        booking.warrantyUntil.isNotEmpty
                            ? 'This booking is covered until ${booking.warrantyUntil}.'
                            : 'This booking has ${booking.warrantyDays} days of warranty after completion.',
                        style: const TextStyle(
                          height: 1.35,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _reportWarrantyIssue(booking),
                          icon: const Icon(Icons.report_problem_outlined),
                          label: const Text('Report Warranty Issue'),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 18),
                if (!['completed', 'cancelled', 'cancellation_requested']
                    .contains(booking.status))
                  Row(
                    children: [
                      if (['pending', 'accepted'].contains(booking.status) &&
                          booking.rescheduleCount < 2) ...[
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed:
                                _busy ? null : () => _reschedule(booking),
                            icon: const Icon(Icons.event_repeat_outlined),
                            label: const Text('Reschedule'),
                          ),
                        ),
                        const SizedBox(width: 10),
                      ],
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _busy ? null : () => _cancel(booking),
                          icon: const Icon(Icons.cancel_outlined),
                          label: const Text('Request Cancel'),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  bool _hasWarrantyCoverage(ServiceBooking booking) {
    if (booking.status != 'completed' || booking.warrantyDays <= 0) {
      return false;
    }
    if (booking.warrantyUntil.trim().isEmpty) {
      return true;
    }
    final until = DateTime.tryParse(booking.warrantyUntil.trim());
    if (until == null) {
      return true;
    }
    final today = DateTime.now();
    final todayDate = DateTime(today.year, today.month, today.day);
    return !until.isBefore(todayDate);
  }

  Future<void> _reportWarrantyIssue(ServiceBooking booking) async {
    final message = await showDialog<String>(
      context: context,
      builder: (context) {
        final controller = TextEditingController();
        return AlertDialog(
          title: const Text('Report warranty issue'),
          content: TextField(
            controller: controller,
            maxLines: 4,
            decoration: const InputDecoration(
              labelText: 'What issue needs follow-up?',
              alignLabelWithHint: true,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Close'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(controller.text),
              child: const Text('Send'),
            ),
          ],
        );
      },
    );
    if (message == null || message.trim().isEmpty) return;
    setState(() => _busy = true);
    try {
      await _api.createSupportThread(
        guestId: servicesGuestId,
        bookingId: booking.id,
        subject: 'Warranty issue for ${booking.bookingNumber}',
        message: [
          'Booking: ${booking.bookingNumber}',
          'Service: ${booking.serviceName}',
          if (booking.providerName.isNotEmpty)
            'Provider: ${booking.providerName}',
          if (booking.preferredDate.isNotEmpty ||
              booking.preferredTime.isNotEmpty)
            'Schedule: ${booking.preferredDate} ${booking.preferredTime}'
                .trim(),
          if (booking.warrantyUntil.isNotEmpty)
            'Warranty until: ${booking.warrantyUntil}',
          '',
          message.trim(),
        ].join('\n'),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Warranty issue sent to support')),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _cancel(ServiceBooking booking) async {
    final note = await showDialog<String>(
      context: context,
      builder: (context) {
        final controller = TextEditingController();
        return AlertDialog(
          title: const Text('Request cancellation'),
          content: TextField(
            controller: controller,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'Reason optional',
              alignLabelWithHint: true,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Close'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(controller.text),
              child: const Text('Send Request'),
            ),
          ],
        );
      },
    );
    if (note == null) return;
    setState(() => _busy = true);
    try {
      await _api.cancelBooking(
        bookingId: booking.id,
        guestId: servicesGuestId,
        note: note.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cancellation request sent to admin')),
      );
      setState(() {
        _future =
            _api.fetchBooking(bookingId: booking.id, guestId: servicesGuestId);
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _reschedule(ServiceBooking booking) async {
    final result = await showModalBottomSheet<_RescheduleResult>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _RescheduleSheet(api: _api, booking: booking),
    );
    if (result == null) return;
    setState(() => _busy = true);
    try {
      await _api.rescheduleBooking(
        bookingId: booking.id,
        guestId: servicesGuestId,
        preferredDate: result.date,
        preferredTime: result.time,
        slotId: result.slotId,
      );
      if (!mounted) return;
      setState(() {
        _future =
            _api.fetchBooking(bookingId: booking.id, guestId: servicesGuestId);
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _ServiceDetailCard extends StatelessWidget {
  const _ServiceDetailCard({required this.children, this.title});

  final String? title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _servicesDarkCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _servicesDarkLine)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title != null) ...[
            Text(
              title!,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 10),
          ],
          ...children,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    if (value.trim().isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 104,
            child: Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: AppTheme.primary,
          fontSize: 12,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

String _bookingStatusLabel(String status) {
  return switch (status) {
    'cancellation_requested' => 'Cancellation requested',
    'pending' => 'Pending',
    'accepted' => 'Accepted',
    'ongoing' => 'Ongoing',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    _ => status,
  };
}

String _paymentLabel(String method) {
  return switch (method) {
    'cash_on_service' => 'Cash On Service',
    'online_payment' => 'Online Payment',
    'bank_transfer' => 'Bank Transfer',
    _ => method,
  };
}

class _ServiceChecklistCard extends StatelessWidget {
  const _ServiceChecklistCard({
    required this.items,
    required this.accepted,
    required this.onChanged,
  });

  final List<String> items;
  final bool accepted;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.fact_check_outlined, color: Color(0xFFD97706)),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Before the professional arrives',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          for (final item in items)
            Padding(
              padding: const EdgeInsets.only(bottom: 7),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.check_circle_outline,
                      size: 18, color: Color(0xFFD97706)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      item,
                      style: const TextStyle(
                        height: 1.35,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          const Divider(height: 18),
          CheckboxListTile(
            value: accepted,
            onChanged: (value) => onChanged(value ?? false),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            title: const Text(
              'I have read and can follow these preparation steps.',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }
}

class _AddonsCard extends StatelessWidget {
  const _AddonsCard({
    required this.addons,
    required this.currency,
    required this.selectedIds,
    required this.onChanged,
  });

  final List<ServiceAddon> addons;
  final String currency;
  final Set<int> selectedIds;
  final void Function(ServiceAddon addon, bool selected) onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _servicesDarkCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _servicesDarkLine)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Add-ons', style: TextStyle(fontWeight: FontWeight.w900)),
          const SizedBox(height: 8),
          for (final addon in addons)
            CheckboxListTile(
              value: selectedIds.contains(addon.id),
              onChanged: (value) => onChanged(addon, value ?? false),
              contentPadding: EdgeInsets.zero,
              title: Text(addon.name),
              subtitle:
                  addon.description.isEmpty ? null : Text(addon.description),
              secondary: Text('$currency${addon.price.toStringAsFixed(0)}',
                  style: const TextStyle(fontWeight: FontWeight.w900)),
            ),
        ],
      ),
    );
  }
}

class _RescheduleResult {
  const _RescheduleResult({
    required this.date,
    required this.time,
    this.slotId,
  });

  final String date;
  final String time;
  final int? slotId;
}

class _RescheduleSheet extends StatefulWidget {
  const _RescheduleSheet({required this.api, required this.booking});

  final ServicesApiClient api;
  final ServiceBooking booking;

  @override
  State<_RescheduleSheet> createState() => _RescheduleSheetState();
}

class _RescheduleSheetState extends State<_RescheduleSheet> {
  final _date = TextEditingController();
  final _time = TextEditingController();
  Future<List<ServiceSlot>>? _slotsFuture;
  ServiceSlot? _slot;

  @override
  void dispose() {
    _date.dispose();
    _time.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Reschedule Booking',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
            const SizedBox(height: 12),
            TextField(
              controller: _date,
              readOnly: true,
              decoration: const InputDecoration(
                labelText: 'New date',
                prefixIcon: Icon(Icons.event_outlined),
              ),
              onTap: _pickDate,
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _time,
              readOnly: true,
              decoration: const InputDecoration(
                labelText: 'New time',
                prefixIcon: Icon(Icons.schedule_outlined),
              ),
              onTap: _pickTime,
            ),
            if (_slotsFuture != null) ...[
              const SizedBox(height: 12),
              FutureBuilder<List<ServiceSlot>>(
                future: _slotsFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const AppSkeletonList(cardCount: 2);
                  }
                  final slots = snapshot.data ?? const <ServiceSlot>[];
                  if (slots.isEmpty) {
                    return Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF7ED),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFFFE0B2)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.schedule_outlined,
                              color: Color(0xFFF59E0B)),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'No configured slots for this date. Pick a preferred time and admin will confirm availability.',
                              style: TextStyle(
                                height: 1.35,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }
                  return Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final slot in slots)
                        ChoiceChip(
                          label: Text(slot.providerName.isEmpty
                              ? slot.label
                              : '${slot.label} • ${slot.providerName}'),
                          selected: _slot?.id == slot.id,
                          onSelected: slot.isAvailable
                              ? (_) => setState(() {
                                    _slot = slot;
                                    _time.text = slot.label;
                                  })
                              : null,
                        ),
                    ],
                  );
                },
              ),
            ],
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () {
                  if (_date.text.trim().isEmpty) return;
                  Navigator.of(context).pop(_RescheduleResult(
                    date: _date.text.trim(),
                    time: _time.text.trim(),
                    slotId: _slot?.id,
                  ));
                },
                child: const Text('Save Reschedule'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 45)),
    );
    if (picked == null) return;
    final date =
        '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    setState(() {
      _date.text = date;
      _time.clear();
      _slot = null;
      _slotsFuture = widget.api
          .fetchSlots(serviceId: widget.booking.serviceId, date: date);
    });
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked == null) return;
    setState(() {
      _slot = null;
      _time.text =
          '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
    });
  }
}

class _ServiceTile extends StatelessWidget {
  const _ServiceTile({
    required this.service,
    required this.currency,
    required this.onTap,
  });

  final CityService service;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _servicesDarkBg,
      darkSurface: _servicesDarkPanel,
      darkCard: _servicesDarkCard,
      darkLine: _servicesDarkLine,
      darkText: _servicesDarkText,
      darkMuted: _servicesDarkMuted,
    );
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: colors.card,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: _servicesCyan.withValues(alpha: .13),
                  child: Icon(
                    _serviceIcon(service.name),
                    color: _servicesCyan,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        service.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: colors.text,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${service.durationMinutes} min service',
                        style: TextStyle(
                          color: colors.muted,
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (service.warrantyDays > 0) ...[
                        const SizedBox(height: 3),
                        Text(
                          '${service.warrantyDays} day warranty',
                          style: const TextStyle(
                            color: _servicesLime,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                      if (service.providerName.isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Text(
                          service.providerName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.muted,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      '$currency${service.sellingPrice.toStringAsFixed(0)}',
                      style: const TextStyle(
                        color: _servicesCyan,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(color: _servicesCyan),
                      ),
                      child: const Text(
                        'Book',
                        style: TextStyle(
                          color: _servicesCyan,
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ServiceSummary extends StatelessWidget {
  const _ServiceSummary({required this.service, required this.currency});

  final CityService service;
  final String currency;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFFFFF7ED), Colors.white],
        ),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFF59E0B).withValues(alpha: .08),
            blurRadius: 22,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(service.name,
              style:
                  const TextStyle(fontSize: 20, fontWeight: FontWeight.w900)),
          const SizedBox(height: 6),
          Text(service.description.isEmpty
              ? 'Service details will appear here from admin.'
              : service.description),
          if (service.providerName.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('Provider: ${service.providerName}',
                style: const TextStyle(fontWeight: FontWeight.w800)),
          ],
          if (service.warrantyDays > 0) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.verified_user_outlined,
                    color: AppTheme.success, size: 18),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    '${service.warrantyDays} day service warranty after completion',
                    style: const TextStyle(
                      color: AppTheme.success,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 10),
          Text('$currency${service.sellingPrice.toStringAsFixed(0)}',
              style: const TextStyle(
                  color: AppTheme.primary,
                  fontSize: 22,
                  fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.required = true,
    this.maxLines = 1,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool required;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: required
          ? (value) => value == null || value.trim().isEmpty ? 'Required' : null
          : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        filled: true,
        fillColor: Theme.of(context).cardColor,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}

class _EmptyServices extends StatelessWidget {
  const _EmptyServices({this.message = 'No services found.'});

  final String message;

  @override
  Widget build(BuildContext context) {
    return AppActivityEmptyState(
      icon: Icons.home_repair_service_outlined,
      title: message,
      detail: 'Your service activity will appear here after booking.',
      accent: const Color(0xFFF59E0B),
      padding: const EdgeInsets.all(24),
    );
  }
}
