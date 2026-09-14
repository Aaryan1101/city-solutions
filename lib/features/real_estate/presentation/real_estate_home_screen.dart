import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/activity_empty_state.dart';
import '../../../core/app_greeting.dart';
import '../../../core/app_theme.dart';
import '../../mart/data/mart_session_store.dart';
import '../../mart/domain/mart_models.dart';
import '../../zone/zone_store.dart';
import '../data/real_estate_api_client.dart';
import '../domain/real_estate_models.dart';

const _rePrimary = Color(0xFFD15F3E);
const _reInk = Color(0xFF20344F);
const _reSoft = Color(0xFFF6F1EE);
const _reDarkBg = Color(0xFF090909);
const _reDarkPanel = Color(0xFF151515);
const _reDarkCard = Color(0xFF1D1D1D);
const _reDarkLine = Color(0xFF2C2C2C);
const _reAmber = Color(0xFFFFB21A);
const _reLime = Color(0xFFB9FF38);
const _reDarkText = Color(0xFFF8F5EE);
const _reDarkMuted = Color(0xFF9E978D);
const _realEstateAppVersion =
    String.fromEnvironment('REAL_ESTATE_APP_VERSION', defaultValue: 'dev');

class RealEstateHomeScreen extends StatefulWidget {
  const RealEstateHomeScreen({super.key});

  @override
  State<RealEstateHomeScreen> createState() => _RealEstateHomeScreenState();
}

class _RealEstateHomeScreenState extends State<RealEstateHomeScreen> {
  final _api = RealEstateApiClient();
  late Future<RealEstateHomeData> _future;
  String _query = '';
  String _purpose = 'sell';
  String _propertyCategory = '';

  @override
  void initState() {
    super.initState();
    _future = _loadHome();
  }

  Future<RealEstateHomeData> _loadHome() async {
    await ZoneStore.instance.load();
    RealEstateZoneSelection.currentZoneId = ZoneStore.instance.selectedZoneId;
    return _api.fetchHome();
  }

  void _reload() {
    setState(() {
      _future = _loadHome();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _reDarkBg),
      body: FutureBuilder<RealEstateHomeData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.serviceGrid,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: _reload);
          }
          final data = snapshot.data;
          if (data == null) {
            return _ErrorState(
                message: 'No real estate data found', onRetry: _reload);
          }
          final forceUpdateRequired =
              data.config.forceUpdateVersion.isNotEmpty &&
                  data.config.forceUpdateVersion != _realEstateAppVersion;
          if (data.config.maintenanceMode || forceUpdateRequired) {
            return _ErrorState(
              message: forceUpdateRequired
                  ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                  : (data.config.maintenanceMessage.trim().isEmpty
                      ? 'Real estate is temporarily unavailable.'
                      : data.config.maintenanceMessage),
              onRetry: _reload,
            );
          }
          final properties = _filteredProperties(data);
          final featuredMatches =
              properties.where((property) => property.isFeatured).toList();
          final spotlight = featuredMatches.isNotEmpty
              ? featuredMatches.first
              : (properties.isNotEmpty ? properties.first : null);
          final nearby = properties
              .where((property) =>
                  spotlight == null || property.id != spotlight.id)
              .toList();
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            color: _reAmber,
            child: SafeArea(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 24),
                children: [
                  AdaptiveModuleHeaderBand(
                    accent: _reAmber,
                    darkBase: _reDarkBg,
                    margin: const EdgeInsets.fromLTRB(-14, -10, -14, 0),
                    padding: const EdgeInsets.fromLTRB(14, 10, 14, 16),
                    child: Column(
                      children: [
                        _RealEstateDarkTopBar(
                          onInquiries: _openInquiries,
                          onMenu: _openSavedSearches,
                        ),
                        const SizedBox(height: 10),
                        _RealEstateLocationMeta(
                          listingCount: properties.length,
                        ),
                        if (data.config.latestAppVersion.isNotEmpty &&
                            data.config.latestAppVersion !=
                                _realEstateAppVersion) ...[
                          const SizedBox(height: 10),
                          _RealEstateVersionNotice(
                            currentVersion: _realEstateAppVersion,
                            latestVersion: data.config.latestAppVersion,
                          ),
                        ],
                        const SizedBox(height: 12),
                        _RealEstatePurposeSwitch(
                          purpose: _purpose,
                          onChanged: (value) =>
                              setState(() => _purpose = value),
                        ),
                        const SizedBox(height: 10),
                        _RealEstateDarkSearch(
                          query: _query,
                          onChanged: (value) => setState(() => _query = value),
                          onSearch: () => _openSearch(_query, _purpose),
                        ),
                        const SizedBox(height: 12),
                        _RealEstateCategoryRail(
                          selectedCategory: _propertyCategory,
                          onChanged: (value) =>
                              setState(() => _propertyCategory = value),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  if (spotlight != null)
                    _RealEstateSpotlightCard(
                      property: spotlight,
                      onTap: () => _openProperty(spotlight),
                    )
                  else
                    _RealEstateHomeEmpty(
                      onSearch: () => _openSearch(_query, _purpose),
                    ),
                  const SizedBox(height: 18),
                  _RealEstateDarkSectionHeader(
                    title: 'Nearby Properties',
                    trailing: nearby.isEmpty ? null : 'See all',
                    onTrailingTap: () => _openSearch(_query, _purpose),
                  ),
                  const SizedBox(height: 10),
                  _NearbyPropertyRail(
                    properties: nearby,
                    onTap: _openProperty,
                  ),
                  if (data.projects.isNotEmpty) ...[
                    const SizedBox(height: 18),
                    _RealEstateDarkSectionHeader(
                      title: 'New Projects',
                      trailing: 'See all',
                      onTrailingTap: () => _openSearch(_query, _purpose),
                    ),
                    const SizedBox(height: 10),
                    _RealEstateProjectRail(
                      projects: data.projects,
                      onTap: _openProject,
                    ),
                  ],
                  const SizedBox(height: 16),
                  _EmiCalculatorCard(onTap: _openSearchFromEmi),
                  const SizedBox(height: 16),
                  _RealEstateQuickActions(
                    onFavorites: _openFavorites,
                    onSavedSearches: _openSavedSearches,
                    onInquiries: _openInquiries,
                    onVisits: _openSiteVisits,
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  List<RealEstateProperty> _filteredProperties(RealEstateHomeData data) {
    final byId = <int, RealEstateProperty>{};
    for (final property in data.featuredProperties) {
      byId[property.id] = property;
    }
    for (final property in data.latestProperties) {
      byId[property.id] = property;
    }
    final query = _query.trim().toLowerCase();
    final purpose = _purpose.trim().toLowerCase();
    final category = _propertyCategory.trim().toLowerCase();
    return byId.values.where((property) {
      final purposeOk =
          purpose.isEmpty || property.listingPurpose.toLowerCase() == purpose;
      final categoryOk = category.isEmpty ||
          property.propertyCategory.toLowerCase().contains(category);
      final text = [
        property.title,
        property.city,
        property.area,
        property.address,
        property.agentName,
        property.propertyCategory,
      ].join(' ').toLowerCase();
      final queryOk = query.isEmpty || text.contains(query);
      return purposeOk && categoryOk && queryOk;
    }).toList();
  }

  void _openSearchFromEmi() {
    _openSearch(_query, _purpose);
  }

  void _openSearch(String query, String purpose) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateSearchScreen(
          initialQuery: query,
          initialPurpose: purpose,
        ),
      ),
    );
  }

  void _openProperty(RealEstateProperty property) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            RealEstatePropertyDetailsScreen(propertyId: property.id),
      ),
    );
  }

  void _openProject(RealEstateProject project) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateProjectDetailsScreen(projectId: project.id),
      ),
    );
  }

  Future<void> _openFavorites() async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateFavoritesScreen(guestId: session.guestId),
      ),
    );
  }

  Future<void> _openInquiries() async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateInquiriesScreen(guestId: session.guestId),
      ),
    );
  }

  Future<void> _openSavedSearches() async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateSavedSearchesScreen(guestId: session.guestId),
      ),
    );
  }

  Future<void> _openSiteVisits() async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateSiteVisitsScreen(guestId: session.guestId),
      ),
    );
  }
}

class _RealEstateDarkTopBar extends StatelessWidget {
  const _RealEstateDarkTopBar({
    required this.onInquiries,
    required this.onMenu,
  });

  final VoidCallback onInquiries;
  final VoidCallback onMenu;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return FutureBuilder<MartCustomerSession>(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        final name = appDisplayName(snapshot.data?.name);
        return Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: _reAmber,
              child: Text(
                name.characters.first.toUpperCase(),
                style: const TextStyle(
                  color: Colors.black,
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
            _DarkIconButton(
              tooltip: 'Inquiries',
              icon: Icons.chat_bubble_outline,
              onTap: onInquiries,
            ),
            const SizedBox(width: 8),
            _DarkIconButton(
              tooltip: 'Saved searches',
              icon: Icons.menu_rounded,
              onTap: onMenu,
            ),
          ],
        );
      },
    );
  }
}

class _RealEstateLocationMeta extends StatelessWidget {
  const _RealEstateLocationMeta({required this.listingCount});

  final int listingCount;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return ValueListenableBuilder(
      valueListenable: ZoneStore.instance.selectedLocation,
      builder: (context, location, _) {
        final label = location?.shortAddress ??
            ZoneStore.instance.selectedZone.value?.name ??
            'Lucknow';
        return Row(
          children: [
            const Icon(Icons.location_on, color: _reAmber, size: 14),
            const SizedBox(width: 4),
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
              '$listingCount new listings',
              style: TextStyle(
                color: colors.muted,
                fontSize: 10,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        );
      },
    );
  }
}

class _RealEstatePurposeSwitch extends StatelessWidget {
  const _RealEstatePurposeSwitch({
    required this.purpose,
    required this.onChanged,
  });

  final String purpose;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return Container(
      height: 44,
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          Expanded(
            child: _RealEstatePurposeSegment(
              label: 'Buy',
              selected: purpose == 'sell',
              onTap: () => onChanged('sell'),
            ),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: _RealEstatePurposeSegment(
              label: 'Rent',
              selected: purpose == 'rent',
              onTap: () => onChanged('rent'),
            ),
          ),
        ],
      ),
    );
  }
}

class _RealEstatePurposeSegment extends StatelessWidget {
  const _RealEstatePurposeSegment({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(11),
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: selected ? _reAmber : Colors.transparent,
          borderRadius: BorderRadius.circular(11),
          border: Border.all(
            color: selected ? _reAmber : _reDarkLine,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? Colors.black : _reDarkText,
            fontSize: 12,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class _RealEstateDarkSearch extends StatelessWidget {
  const _RealEstateDarkSearch({
    required this.query,
    required this.onChanged,
    required this.onSearch,
  });

  final String query;
  final ValueChanged<String> onChanged;
  final VoidCallback onSearch;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return TextField(
      controller: TextEditingController(text: query)
        ..selection = TextSelection.collapsed(offset: query.length),
      onChanged: onChanged,
      onSubmitted: (_) => onSearch(),
      style: TextStyle(
        color: colors.text,
        fontWeight: FontWeight.w700,
      ),
      decoration: InputDecoration(
        hintText: 'Search locality, builder, project...',
        hintStyle: TextStyle(color: colors.muted, fontSize: 12),
        prefixIcon: Icon(Icons.search_rounded, color: colors.muted),
        suffixIcon: IconButton(
          tooltip: 'Filters',
          onPressed: onSearch,
          icon: Icon(Icons.tune_rounded, color: colors.muted),
        ),
        filled: true,
        fillColor: colors.surface,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
          borderSide: const BorderSide(color: _reAmber),
        ),
      ),
    );
  }
}

class _RealEstateCategoryRail extends StatelessWidget {
  const _RealEstateCategoryRail({
    required this.selectedCategory,
    required this.onChanged,
  });

  final String selectedCategory;
  final ValueChanged<String> onChanged;

  static const _items = <({String key, String label, IconData icon})>[
    (key: 'apartment', label: 'Apartment', icon: Icons.apartment_rounded),
    (key: 'house', label: 'House', icon: Icons.home_rounded),
    (key: 'commercial', label: 'Commercial', icon: Icons.store_rounded),
    (key: 'plot', label: 'Plot', icon: Icons.schema_rounded),
  ];

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return SizedBox(
      height: 70,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 14),
        itemBuilder: (context, index) {
          final item = _items[index];
          final selected = selectedCategory == item.key;
          return InkWell(
            borderRadius: BorderRadius.circular(18),
            onTap: () => onChanged(selected ? '' : item.key),
            child: SizedBox(
              width: 76,
              child: Column(
                children: [
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 160),
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: selected ? _reAmber : colors.card,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: selected ? _reAmber : colors.line,
                      ),
                    ),
                    child: Icon(
                      item.icon,
                      color: selected ? Colors.black : _reAmber,
                      size: 20,
                    ),
                  ),
                  const SizedBox(height: 7),
                  Text(
                    item.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: selected ? colors.text : colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
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

class _RealEstateSpotlightCard extends StatelessWidget {
  const _RealEstateSpotlightCard({
    required this.property,
    required this.onTap,
  });

  final RealEstateProperty property;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(18)),
              child: SizedBox(
                height: 176,
                width: double.infinity,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    _NetworkImageOrIcon(url: property.thumbnailUrl),
                    const DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [Colors.transparent, Color(0xCC000000)],
                        ),
                      ),
                    ),
                    Positioned(
                      left: 10,
                      top: 10,
                      child: _DarkBadge(
                        label: property.isFeatured ? 'Featured' : 'Verified',
                        color: _reAmber,
                      ),
                    ),
                    if (property.isVerified)
                      const Positioned(
                        right: 10,
                        bottom: 10,
                        child: _DarkBadge(
                          label: 'RERA Verified',
                          color: _reLime,
                        ),
                      ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          property.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.text,
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          property.locationLabel,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.muted,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        _RealEstateSpecLine(property: property),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        _priceLabel(property),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: _reAmber,
                          fontSize: 15,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        property.listingPurpose == 'rent'
                            ? 'monthly'
                            : 'onwards',
                        style: TextStyle(
                          color: colors.muted,
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RealEstateSpecLine extends StatelessWidget {
  const _RealEstateSpecLine({required this.property});

  final RealEstateProperty property;

  @override
  Widget build(BuildContext context) {
    final beds = property.bedrooms > 0
        ? '${property.bedrooms} BHK'
        : property.categoryLabel;
    final area = property.displayArea > 0
        ? '${property.displayArea.toStringAsFixed(0)} ${property.areaUnit}'
        : property.categoryLabel;
    final parking =
        property.parking > 0 ? '${property.parking} Parking' : 'Parking info';
    return Row(
      children: [
        _DarkSpec(icon: Icons.bed_outlined, label: beds),
        const SizedBox(width: 8),
        _DarkSpec(icon: Icons.square_foot_outlined, label: area),
        const SizedBox(width: 8),
        Expanded(
          child: _DarkSpec(icon: Icons.local_parking_outlined, label: parking),
        ),
      ],
    );
  }
}

class _NearbyPropertyRail extends StatelessWidget {
  const _NearbyPropertyRail({
    required this.properties,
    required this.onTap,
  });

  final List<RealEstateProperty> properties;
  final ValueChanged<RealEstateProperty> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    if (properties.isEmpty) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: Text(
          'Properties added from admin will appear here.',
          style: TextStyle(color: colors.muted, fontWeight: FontWeight.w700),
        ),
      );
    }
    return SizedBox(
      height: 174,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: properties.take(10).length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final property = properties[index];
          return _NearbyPropertyCard(
            property: property,
            onTap: () => onTap(property),
          );
        },
      ),
    );
  }
}

class _NearbyPropertyCard extends StatelessWidget {
  const _NearbyPropertyCard({
    required this.property,
    required this.onTap,
  });

  final RealEstateProperty property;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Container(
        width: 132,
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: colors.line),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(14)),
              child: SizedBox(
                height: 92,
                width: double.infinity,
                child: _NetworkImageOrIcon(url: property.thumbnailUrl),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    property.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    property.locationLabel,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 9,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _priceLabel(property),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: _reAmber,
                      fontSize: 12,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  Text(
                    property.bedrooms > 0
                        ? '${property.bedrooms} BHK'
                        : property.categoryLabel,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 9,
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
  }
}

class _RealEstateProjectRail extends StatelessWidget {
  const _RealEstateProjectRail({
    required this.projects,
    required this.onTap,
  });

  final List<RealEstateProject> projects;
  final ValueChanged<RealEstateProject> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return SizedBox(
      height: 132,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: projects.take(10).length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final project = projects[index];
          return InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: () => onTap(project),
            child: Container(
              width: 238,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: colors.line),
              ),
              child: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: SizedBox(
                      width: 86,
                      height: 112,
                      child: _NetworkImageOrIcon(url: project.thumbnailUrl),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          project.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.text,
                            fontSize: 12,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          project.locationLabel,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.muted,
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          project.builderName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: _reAmber,
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
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

class _EmiCalculatorCard extends StatelessWidget {
  const _EmiCalculatorCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: _reAmber.withValues(alpha: .13),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.calculate_outlined,
                color: _reAmber,
                size: 20,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'EMI Calculator',
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 12,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Check affordability instantly',
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              height: 28,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(999),
                border: Border.all(color: _reAmber),
              ),
              child: const Text(
                'Try',
                style: TextStyle(
                  color: _reAmber,
                  fontSize: 11,
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

class _RealEstateQuickActions extends StatelessWidget {
  const _RealEstateQuickActions({
    required this.onFavorites,
    required this.onSavedSearches,
    required this.onInquiries,
    required this.onVisits,
  });

  final VoidCallback onFavorites;
  final VoidCallback onSavedSearches;
  final VoidCallback onInquiries;
  final VoidCallback onVisits;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          _QuickActionItem(
            icon: Icons.favorite_border,
            label: 'Saved',
            selected: true,
            onTap: onFavorites,
          ),
          _QuickActionItem(
            icon: Icons.search_rounded,
            label: 'Search',
            onTap: onSavedSearches,
          ),
          _QuickActionItem(
            icon: Icons.chat_bubble_outline,
            label: 'Inquiries',
            onTap: onInquiries,
          ),
          _QuickActionItem(
            icon: Icons.event_available_outlined,
            label: 'Visits',
            onTap: onVisits,
          ),
        ],
      ),
    );
  }
}

class _RealEstateDarkSectionHeader extends StatelessWidget {
  const _RealEstateDarkSectionHeader({
    required this.title,
    this.trailing,
    this.onTrailingTap,
  });

  final String title;
  final String? trailing;
  final VoidCallback? onTrailingTap;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _reDarkText);
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
        if (trailing != null)
          InkWell(
            onTap: onTrailingTap,
            child: Text(
              trailing!,
              style: const TextStyle(
                color: _reAmber,
                fontSize: 10,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
      ],
    );
  }
}

class _RealEstateHomeEmpty extends StatelessWidget {
  const _RealEstateHomeEmpty({required this.onSearch});

  final VoidCallback onSearch;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Column(
        children: [
          const Icon(Icons.real_estate_agent_outlined,
              color: _reAmber, size: 34),
          const SizedBox(height: 8),
          Text(
            'No properties found',
            style: TextStyle(
              color: colors.text,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Try another category or search term.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: colors.muted,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton(
            style: OutlinedButton.styleFrom(
              foregroundColor: _reAmber,
              side: const BorderSide(color: _reAmber),
            ),
            onPressed: onSearch,
            child: const Text('Open full search'),
          ),
        ],
      ),
    );
  }
}

class _DarkIconButton extends StatelessWidget {
  const _DarkIconButton({
    required this.tooltip,
    required this.icon,
    required this.onTap,
  });

  final String tooltip;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _reDarkBg,
      darkSurface: _reDarkPanel,
      darkCard: _reDarkCard,
      darkLine: _reDarkLine,
      darkText: _reDarkText,
      darkMuted: _reDarkMuted,
    );
    return Tooltip(
      message: tooltip,
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: onTap,
        child: Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: colors.surface,
            shape: BoxShape.circle,
            border: Border.all(color: colors.line),
          ),
          child: Icon(icon, color: colors.text, size: 18),
        ),
      ),
    );
  }
}

class _DarkBadge extends StatelessWidget {
  const _DarkBadge({
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(7),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.black,
          fontSize: 9,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _DarkSpec extends StatelessWidget {
  const _DarkSpec({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final muted = adaptiveMuted(context, _reDarkMuted);
    return Flexible(
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: muted, size: 13),
          const SizedBox(width: 3),
          Flexible(
            child: Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: muted,
                fontSize: 9,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionItem extends StatelessWidget {
  const _QuickActionItem({
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
    final muted = adaptiveMuted(context, _reDarkMuted);
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              color: selected ? _reLime : muted,
              size: 18,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: selected ? _reLime : muted,
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

class RealEstateSearchScreen extends StatefulWidget {
  const RealEstateSearchScreen({
    super.key,
    this.initialQuery = '',
    this.initialPurpose = '',
    this.initialCategory = '',
    this.initialBedrooms = 0,
    this.initialBudgetIndex = 0,
    this.initialFurnishing = '',
    this.initialMinArea = 0,
    this.initialVerifiedOnly = false,
  });

  final String initialQuery;
  final String initialPurpose;
  final String initialCategory;
  final int initialBedrooms;
  final int initialBudgetIndex;
  final String initialFurnishing;
  final int initialMinArea;
  final bool initialVerifiedOnly;

  @override
  State<RealEstateSearchScreen> createState() => _RealEstateSearchScreenState();
}

class _RealEstateSearchScreenState extends State<RealEstateSearchScreen> {
  final _api = RealEstateApiClient();
  late final TextEditingController _queryController =
      TextEditingController(text: widget.initialQuery);
  late String _purpose = widget.initialPurpose;
  late String _category = widget.initialCategory;
  late String _furnishing = widget.initialFurnishing;
  late int _bedrooms = widget.initialBedrooms;
  late int _budgetIndex = widget.initialBudgetIndex;
  late int _minArea = widget.initialMinArea;
  late bool _verifiedOnly = widget.initialVerifiedOnly;
  late Future<List<RealEstateProperty>> _future = _search();

  Future<List<RealEstateProperty>> _search() {
    final budget =
        _budgetRanges[_budgetIndex.clamp(0, _budgetRanges.length - 1)];
    return _api.searchProperties(
      query: _queryController.text,
      listingPurpose: _purpose,
      propertyCategory: _category,
      bedrooms: _bedrooms,
      minPrice: budget.$1,
      maxPrice: budget.$2,
      minArea: _minArea.toDouble(),
      furnishing: _furnishing,
      verifiedOnly: _verifiedOnly,
    );
  }

  void _runSearch() {
    setState(() {
      _future = _search();
    });
  }

  @override
  void dispose() {
    _queryController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: Theme.of(context).cardColor,
        foregroundColor: _reInk,
        elevation: 0,
        title: const Text('Find property'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          TextField(
            controller: _queryController,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => _runSearch(),
            decoration: InputDecoration(
              hintText: 'Search city, area, project',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: IconButton(
                onPressed: _runSearch,
                icon: const Icon(Icons.tune_outlined),
              ),
              filled: true,
              fillColor: Theme.of(context).cardColor,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(18),
                borderSide: BorderSide.none,
              ),
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _FilterChip(
                label: 'Buy',
                selected: _purpose == 'sell',
                onTap: () =>
                    setState(() => _purpose = _purpose == 'sell' ? '' : 'sell'),
              ),
              _FilterChip(
                label: 'Rent',
                selected: _purpose == 'rent',
                onTap: () =>
                    setState(() => _purpose = _purpose == 'rent' ? '' : 'rent'),
              ),
              _FilterChip(
                label: 'Apartment',
                selected: _category == 'apartment',
                onTap: () => setState(() =>
                    _category = _category == 'apartment' ? '' : 'apartment'),
              ),
              _FilterChip(
                label: 'Villa',
                selected: _category == 'villa',
                onTap: () => setState(
                    () => _category = _category == 'villa' ? '' : 'villa'),
              ),
              _FilterChip(
                label: '2+ BHK',
                selected: _bedrooms == 2,
                onTap: () => setState(() => _bedrooms = _bedrooms == 2 ? 0 : 2),
              ),
              _FilterChip(
                label: '3+ BHK',
                selected: _bedrooms == 3,
                onTap: () => setState(() => _bedrooms = _bedrooms == 3 ? 0 : 3),
              ),
              _FilterChip(
                label: 'Verified',
                selected: _verifiedOnly,
                onTap: () => setState(() => _verifiedOnly = !_verifiedOnly),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _InfoCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'More filters',
                  style: TextStyle(
                    color: _reInk,
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 12),
                _SegmentedOptions<int>(
                  value: _budgetIndex,
                  options: const {
                    0: 'Any budget',
                    1: '< 50L',
                    2: '50L-1Cr',
                    3: '1Cr+',
                  },
                  onChanged: (value) => setState(() => _budgetIndex = value),
                ),
                const SizedBox(height: 10),
                _SegmentedOptions<String>(
                  value: _furnishing,
                  options: const {
                    '': 'Any furnishing',
                    'Semi furnished': 'Semi',
                    'Fully furnished': 'Full',
                    'Unfurnished': 'None',
                  },
                  onChanged: (value) => setState(() => _furnishing = value),
                ),
                const SizedBox(height: 10),
                _SegmentedOptions<int>(
                  value: _minArea,
                  options: const {
                    0: 'Any area',
                    500: '500+',
                    1000: '1000+',
                    2000: '2000+',
                  },
                  onChanged: (value) => setState(() => _minArea = value),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 46,
            child: Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _rePrimary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: _runSearch,
                    icon: const Icon(Icons.manage_search_outlined),
                    label: const Text('Apply filters'),
                  ),
                ),
                const SizedBox(width: 10),
                SizedBox(
                  width: 54,
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: _rePrimary,
                      side: const BorderSide(color: _rePrimary),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      padding: EdgeInsets.zero,
                    ),
                    onPressed: _saveCurrentSearch,
                    child: const Icon(Icons.bookmark_add_outlined),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          FutureBuilder<List<RealEstateProperty>>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Padding(
                  padding: EdgeInsets.only(top: 8),
                  child: AppSkeletonList(cardCount: 4),
                );
              }
              if (snapshot.hasError) {
                return _ErrorState(
                  message: AppErrorState.userMessage(snapshot.error),
                  onRetry: _runSearch,
                );
              }
              final properties = snapshot.data ?? const [];
              if (properties.isEmpty) {
                return const _EmptyState(
                    message: 'No matching properties found.');
              }
              return Column(
                children: [
                  for (final property in properties)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _PropertyCard(
                        property: property,
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => RealEstatePropertyDetailsScreen(
                              propertyId: property.id,
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
    );
  }

  Future<void> _saveCurrentSearch() async {
    try {
      final session = await MartSessionStore().load();
      await _api.saveSearch(
        guestId: session.guestId,
        name: _queryController.text.trim().isEmpty
            ? 'Property search'
            : _queryController.text.trim(),
        filters: {
          'query': _queryController.text.trim(),
          'listing_purpose': _purpose,
          'property_category': _category,
          'bedrooms': _bedrooms,
          'budget_index': _budgetIndex,
          'furnishing': _furnishing,
          'min_area': _minArea,
          'verified_only': _verifiedOnly,
        },
        notify: true,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Search saved')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    }
  }
}

class RealEstatePropertyDetailsScreen extends StatefulWidget {
  const RealEstatePropertyDetailsScreen({super.key, required this.propertyId});

  final int propertyId;

  @override
  State<RealEstatePropertyDetailsScreen> createState() =>
      _RealEstatePropertyDetailsScreenState();
}

class _RealEstatePropertyDetailsScreenState
    extends State<RealEstatePropertyDetailsScreen> {
  final _api = RealEstateApiClient();
  late Future<RealEstateDetails> _future =
      _api.fetchProperty(widget.propertyId);
  bool _savingFavorite = false;

  void _reload() {
    setState(() {
      _future = _api.fetchProperty(widget.propertyId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: FutureBuilder<RealEstateDetails>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.detail,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: _reload);
          }
          final details = snapshot.data;
          if (details == null) {
            return _ErrorState(message: 'Property not found', onRetry: _reload);
          }
          final property = details.property;
          return Stack(
            children: [
              CustomScrollView(
                slivers: [
                  SliverAppBar(
                    expandedHeight: 320,
                    pinned: true,
                    backgroundColor: Theme.of(context).cardColor,
                    foregroundColor: _reInk,
                    actions: [
                      IconButton(
                        tooltip: 'Favorite',
                        onPressed: _savingFavorite
                            ? null
                            : () => _toggleFavorite(property),
                        icon: const Icon(Icons.favorite_border),
                      ),
                      IconButton(
                        tooltip: 'Report listing',
                        onPressed: () => _openReport(property),
                        icon: const Icon(Icons.flag_outlined),
                      ),
                    ],
                    flexibleSpace: FlexibleSpaceBar(
                      background: _HeroImage(
                        url: details.images.isNotEmpty
                            ? details.images.first
                            : property.thumbnailUrl,
                      ),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(18, 18, 18, 112),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _InfoCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    _Badge(property.purposeLabel),
                                    const SizedBox(width: 8),
                                    if (property.isVerified)
                                      const _Badge('Verified', green: true),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                Text(
                                  property.title,
                                  style: const TextStyle(
                                    color: _reInk,
                                    fontSize: 24,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    const Icon(Icons.location_on_outlined,
                                        size: 18, color: Colors.blueGrey),
                                    const SizedBox(width: 4),
                                    Expanded(
                                        child: Text(property.locationLabel)),
                                  ],
                                ),
                                if (property.latitude != null &&
                                    property.longitude != null) ...[
                                  const SizedBox(height: 12),
                                  OutlinedButton.icon(
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: _rePrimary,
                                      side: const BorderSide(color: _rePrimary),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(14),
                                      ),
                                    ),
                                    onPressed: () => _openMapLocation(
                                      latitude: property.latitude!,
                                      longitude: property.longitude!,
                                      label: property.title,
                                    ),
                                    icon: const Icon(Icons.map_outlined),
                                    label: const Text('Open map'),
                                  ),
                                ],
                                const SizedBox(height: 16),
                                Text(
                                  _priceLabel(property),
                                  style: const TextStyle(
                                    color: _rePrimary,
                                    fontSize: 24,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 14),
                          _SpecsRow(property: property),
                          if (details.images.length > 1) ...[
                            const SizedBox(height: 14),
                            _GalleryStrip(images: details.images),
                          ],
                          const SizedBox(height: 14),
                          _TrustStrip(property: property),
                          const SizedBox(height: 14),
                          _InfoCard(
                            title: 'Overview',
                            child: Text(
                              property.description.isEmpty
                                  ? 'Details will be updated soon.'
                                  : property.description,
                              style: const TextStyle(
                                color: Color(0xFF65738A),
                                height: 1.45,
                              ),
                            ),
                          ),
                          if (details.amenities.isNotEmpty) ...[
                            const SizedBox(height: 14),
                            _InfoCard(
                              title: 'Amenities',
                              child: Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  for (final amenity in details.amenities)
                                    Chip(
                                      label: Text(amenity.name),
                                      backgroundColor: _reSoft,
                                      side: BorderSide.none,
                                    ),
                                ],
                              ),
                            ),
                          ],
                          if (details.floorPlans.isNotEmpty) ...[
                            const SizedBox(height: 14),
                            _InfoCard(
                              title: 'Floor plans',
                              child: Column(
                                children: [
                                  for (final plan in details.floorPlans)
                                    ListTile(
                                      contentPadding: EdgeInsets.zero,
                                      leading: const CircleAvatar(
                                        backgroundColor: _reSoft,
                                        child: Icon(Icons.apartment_outlined,
                                            color: _rePrimary),
                                      ),
                                      title: Text(plan.name),
                                      subtitle: Text(
                                        '${plan.bedrooms} bed • ${plan.bathrooms} bath • ${plan.area.toStringAsFixed(0)} sq ft',
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ],
                          const SizedBox(height: 14),
                          _InfoCard(
                            title: 'Agent',
                            child: ListTile(
                              contentPadding: EdgeInsets.zero,
                              onTap: property.agentId > 0
                                  ? () => _openAgent(property.agentId)
                                  : null,
                              leading: const CircleAvatar(
                                backgroundColor: _reSoft,
                                child: Icon(Icons.real_estate_agent_outlined,
                                    color: _rePrimary),
                              ),
                              title: Text(property.agentName),
                              subtitle: Text(property.agentPhone.isEmpty
                                  ? 'Phone hidden'
                                  : property.agentPhone),
                              trailing: property.agentId > 0
                                  ? const Icon(Icons.chevron_right_rounded)
                                  : null,
                            ),
                          ),
                          if (details.similarProperties.isNotEmpty) ...[
                            const SizedBox(height: 18),
                            _PropertySection(
                              title: 'Similar properties',
                              properties: details.similarProperties,
                              horizontal: true,
                              onTap: (item) =>
                                  Navigator.of(context).pushReplacement(
                                MaterialPageRoute<void>(
                                  builder: (_) =>
                                      RealEstatePropertyDetailsScreen(
                                    propertyId: item.id,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              Positioned(
                left: 18,
                right: 18,
                bottom: 18,
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: _rePrimary,
                          side: const BorderSide(color: _rePrimary),
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () => _openInquiry(property),
                        icon: const Icon(Icons.chat_bubble_outline),
                        label: const Text('Inquiry'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _rePrimary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () => _openVisit(property),
                        icon: const Icon(Icons.event_available_outlined),
                        label: const Text('Visit'),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _toggleFavorite(RealEstateProperty property) async {
    setState(() => _savingFavorite = true);
    try {
      final session = await MartSessionStore().load();
      final saved = await _api.toggleFavorite(
        guestId: session.guestId,
        propertyId: property.id,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(saved ? 'Saved to favorites' : 'Removed')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _savingFavorite = false);
    }
  }

  Future<void> _openInquiry(RealEstateProperty property) async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    _showLeadSheet(
      context: context,
      title: 'Send inquiry',
      primaryLabel: 'Submit inquiry',
      needsDateTime: false,
      initialName: session.name,
      initialPhone: session.phone,
      initialEmail: session.email,
      onSubmit: (lead) async {
        final number = await _api.submitInquiry(
          propertyId: property.id,
          guestId: session.guestId,
          name: lead.name,
          phone: lead.phone,
          email: lead.email,
          message: lead.note,
        );
        return number.isEmpty
            ? 'Inquiry submitted'
            : 'Inquiry $number submitted';
      },
    );
  }

  Future<void> _openVisit(RealEstateProperty property) async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    _showLeadSheet(
      context: context,
      title: 'Request site visit',
      primaryLabel: 'Request visit',
      needsDateTime: true,
      initialName: session.name,
      initialPhone: session.phone,
      initialEmail: session.email,
      onSubmit: (lead) async {
        final number = await _api.requestSiteVisit(
          propertyId: property.id,
          guestId: session.guestId,
          name: lead.name,
          phone: lead.phone,
          email: lead.email,
          address: property.address,
          requestedDate: lead.date,
          requestedTime: lead.time,
          note: lead.note,
        );
        return number.isEmpty ? 'Visit requested' : 'Visit $number requested';
      },
    );
  }

  Future<void> _openReport(RealEstateProperty property) async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    _showLeadSheet(
      context: context,
      title: 'Report listing',
      primaryLabel: 'Submit report',
      needsDateTime: false,
      initialName: session.name,
      initialPhone: session.phone,
      initialEmail: session.email,
      onSubmit: (lead) async {
        final number = await _api.reportProperty(
          propertyId: property.id,
          guestId: session.guestId,
          name: lead.name,
          phone: lead.phone,
          message: lead.note,
        );
        return number.isEmpty
            ? 'Listing report submitted'
            : 'Report $number submitted';
      },
    );
  }

  void _openAgent(int agentId) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RealEstateAgentProfileScreen(agentId: agentId),
      ),
    );
  }
}

class RealEstateSiteVisitsScreen extends StatelessWidget {
  const RealEstateSiteVisitsScreen({
    super.key,
    required this.guestId,
    this.showAppBar = true,
  });

  final String guestId;
  final bool showAppBar;

  @override
  Widget build(BuildContext context) {
    final api = RealEstateApiClient();
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: showAppBar
          ? AppBar(
              title: const Text('Site visits'),
              backgroundColor: Theme.of(context).cardColor,
              foregroundColor: _reInk,
              elevation: 0,
            )
          : null,
      body: FutureBuilder<List<RealEstateSiteVisit>>(
        future: api.fetchSiteVisits(guestId),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.detail,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: () {},
            );
          }
          final visits = snapshot.data ?? const [];
          if (visits.isEmpty) {
            return const _EmptyState(message: 'No site visits requested yet.');
          }
          return ListView.separated(
            padding: const EdgeInsets.all(18),
            itemCount: visits.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final visit = visits[index];
              return _InfoCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(
                    backgroundColor: _reSoft,
                    child:
                        Icon(Icons.event_available_outlined, color: _rePrimary),
                  ),
                  title: Text(
                    visit.propertyTitle,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  subtitle: Text(
                    '${visit.requestedDate} • ${visit.requestedTime}\n${visit.visitNumber}',
                  ),
                  trailing: _Badge(visit.status),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class RealEstateProjectDetailsScreen extends StatefulWidget {
  const RealEstateProjectDetailsScreen({super.key, required this.projectId});

  final int projectId;

  @override
  State<RealEstateProjectDetailsScreen> createState() =>
      _RealEstateProjectDetailsScreenState();
}

class _RealEstateProjectDetailsScreenState
    extends State<RealEstateProjectDetailsScreen> {
  final _api = RealEstateApiClient();
  late Future<RealEstateProjectDetails> _future =
      _api.fetchProject(widget.projectId);

  void _reload() {
    setState(() {
      _future = _api.fetchProject(widget.projectId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: FutureBuilder<RealEstateProjectDetails>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.detail,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: _reload,
            );
          }
          final details = snapshot.data;
          if (details == null) {
            return _ErrorState(message: 'Project not found', onRetry: _reload);
          }
          final project = details.project;
          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 300,
                pinned: true,
                backgroundColor: Theme.of(context).cardColor,
                foregroundColor: _reInk,
                flexibleSpace: FlexibleSpaceBar(
                  background: _HeroImage(url: project.thumbnailUrl),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(18, 18, 18, 28),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _InfoCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                if (project.isFeatured)
                                  const _Badge('Featured', green: true),
                                if (project.launchDate.isNotEmpty) ...[
                                  const SizedBox(width: 8),
                                  _Badge('Launch ${project.launchDate}'),
                                ],
                              ],
                            ),
                            const SizedBox(height: 12),
                            Text(
                              project.name,
                              style: const TextStyle(
                                color: _reInk,
                                fontSize: 24,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                const Icon(
                                  Icons.location_on_outlined,
                                  size: 18,
                                  color: Colors.blueGrey,
                                ),
                                const SizedBox(width: 4),
                                Expanded(child: Text(project.locationLabel)),
                              ],
                            ),
                            if (project.latitude != null &&
                                project.longitude != null) ...[
                              const SizedBox(height: 12),
                              OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: _rePrimary,
                                  side: const BorderSide(color: _rePrimary),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                ),
                                onPressed: () => _openMapLocation(
                                  latitude: project.latitude!,
                                  longitude: project.longitude!,
                                  label: project.name,
                                ),
                                icon: const Icon(Icons.map_outlined),
                                label: const Text('Open map'),
                              ),
                            ],
                            const SizedBox(height: 14),
                            Text(
                              project.builderName,
                              style: const TextStyle(
                                color: _rePrimary,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            if (project.builderId > 0) ...[
                              const SizedBox(height: 12),
                              OutlinedButton.icon(
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: _rePrimary,
                                  side: const BorderSide(color: _rePrimary),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                ),
                                onPressed: () => Navigator.of(context).push(
                                  MaterialPageRoute<void>(
                                    builder: (_) =>
                                        RealEstateAgentProfileScreen(
                                      agentId: project.builderId,
                                    ),
                                  ),
                                ),
                                icon: const Icon(
                                    Icons.real_estate_agent_outlined),
                                label: const Text('View builder'),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      _TrustStrip(
                        property: RealEstateProperty(
                          id: 0,
                          agentId: project.builderId,
                          zoneId: project.zoneId,
                          title: project.name,
                          listingPurpose: 'sell',
                          propertyCategory: 'project',
                          price: 0,
                          priceUnit: '',
                          city: project.city,
                          area: project.area,
                          address: project.address,
                          latitude: project.latitude,
                          longitude: project.longitude,
                          description: project.description,
                          bedrooms: 0,
                          bathrooms: 0,
                          balconies: 0,
                          parking: 0,
                          builtUpArea: 0,
                          carpetArea: 0,
                          plotArea: 0,
                          areaUnit: 'sq ft',
                          furnishing: '',
                          ownershipType: '',
                          propertyAge: '',
                          thumbnailUrl: project.thumbnailUrl,
                          agentName: project.builderName,
                          agentPhone: project.builderPhone,
                          isVerified: true,
                          isFeatured: project.isFeatured,
                        ),
                      ),
                      const SizedBox(height: 14),
                      _InfoCard(
                        title: 'About project',
                        child: Text(
                          project.description.isEmpty
                              ? 'Project details will be updated soon.'
                              : project.description,
                          style: const TextStyle(
                            color: Color(0xFF65738A),
                            height: 1.45,
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                      _InfoCard(
                        title: 'Available units',
                        child: details.units.isEmpty
                            ? const Text('Units will be updated soon.')
                            : Column(
                                children: [
                                  for (final unit in details.units)
                                    _ProjectUnitTile(unit: unit),
                                ],
                              ),
                      ),
                    ],
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

class _ProjectUnitTile extends StatelessWidget {
  const _ProjectUnitTile({required this.unit});

  final RealEstateProjectUnit unit;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFD),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(14),
            child: SizedBox(
              width: 62,
              height: 62,
              child: _NetworkImageOrIcon(url: unit.floorPlanUrl),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  unit.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: _reInk,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${unit.bedrooms} bed • ${unit.bathrooms} bath • ${unit.area.toStringAsFixed(0)} sq ft',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(
            _priceFromLabel(unit.priceFrom),
            style: const TextStyle(
              color: _rePrimary,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class RealEstateInquiriesScreen extends StatelessWidget {
  const RealEstateInquiriesScreen({
    super.key,
    required this.guestId,
    this.showAppBar = true,
  });

  final String guestId;
  final bool showAppBar;

  @override
  Widget build(BuildContext context) {
    final api = RealEstateApiClient();
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: showAppBar
          ? AppBar(
              title: const Text('Property inquiries'),
              backgroundColor: Theme.of(context).cardColor,
              foregroundColor: _reInk,
              elevation: 0,
            )
          : null,
      body: FutureBuilder<List<RealEstateInquiry>>(
        future: api.fetchInquiries(guestId),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(cardCount: 4, showHero: false);
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: () {},
            );
          }
          final inquiries = snapshot.data ?? const [];
          if (inquiries.isEmpty) {
            return const _EmptyState(message: 'No property inquiries yet.');
          }
          return ListView.separated(
            padding: const EdgeInsets.all(18),
            itemCount: inquiries.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final inquiry = inquiries[index];
              return _InfoCard(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: SizedBox(
                        width: 62,
                        height: 62,
                        child: _NetworkImageOrIcon(url: inquiry.thumbnailUrl),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  inquiry.propertyTitle,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: _reInk,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ),
                              _Badge(inquiry.status),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            inquiry.inquiryNumber,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          if (inquiry.message.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Text(
                              inquiry.message,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(),
                            ),
                          ],
                          if (inquiry.agentReply.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: _reSoft,
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: Text(
                                inquiry.agentReply,
                                style: const TextStyle(
                                  color: _reInk,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class RealEstateAgentProfileScreen extends StatefulWidget {
  const RealEstateAgentProfileScreen({super.key, required this.agentId});

  final int agentId;

  @override
  State<RealEstateAgentProfileScreen> createState() =>
      _RealEstateAgentProfileScreenState();
}

class _RealEstateAgentProfileScreenState
    extends State<RealEstateAgentProfileScreen> {
  final _api = RealEstateApiClient();
  late Future<RealEstateAgentProfile> _future = _api.fetchAgent(widget.agentId);

  void _reload() {
    setState(() {
      _future = _api.fetchAgent(widget.agentId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: const Text('Agent profile'),
        backgroundColor: Theme.of(context).cardColor,
        foregroundColor: _reInk,
        elevation: 0,
      ),
      body: FutureBuilder<RealEstateAgentProfile>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.detail,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: _reload,
            );
          }
          final profile = snapshot.data;
          if (profile == null) {
            return _ErrorState(message: 'Agent not found', onRetry: _reload);
          }
          final agent = profile.agent;
          return ListView(
            padding: const EdgeInsets.all(18),
            children: [
              _InfoCard(
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(22),
                      child: SizedBox(
                        width: 82,
                        height: 82,
                        child: _NetworkImageOrIcon(url: agent.profileImageUrl),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            agent.businessName,
                            style: const TextStyle(
                              color: _reInk,
                              fontSize: 20,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                          if (agent.ownerName.isNotEmpty)
                            Text(
                              agent.ownerName,
                              style: const TextStyle(),
                            ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const _Badge('Verified', green: true),
                              if (agent.zoneName.isNotEmpty) ...[
                                const SizedBox(width: 8),
                                _Badge(agent.zoneName),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  if (agent.phone.isNotEmpty)
                    Expanded(
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _rePrimary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () => _launchUri('tel:${agent.phone}'),
                        icon: const Icon(Icons.call_outlined),
                        label: const Text('Call'),
                      ),
                    ),
                  if (agent.phone.isNotEmpty && agent.email.isNotEmpty)
                    const SizedBox(width: 10),
                  if (agent.email.isNotEmpty)
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: _rePrimary,
                          side: const BorderSide(color: _rePrimary),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        onPressed: () => _launchUri('mailto:${agent.email}'),
                        icon: const Icon(Icons.mail_outline),
                        label: const Text('Email'),
                      ),
                    ),
                ],
              ),
              if (agent.bio.isNotEmpty) ...[
                const SizedBox(height: 14),
                _InfoCard(
                  title: 'About',
                  child: Text(
                    agent.bio,
                    style: const TextStyle(height: 1.45),
                  ),
                ),
              ],
              const SizedBox(height: 18),
              _PropertySection(
                title: 'Active listings',
                properties: profile.properties,
                onTap: (property) => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => RealEstatePropertyDetailsScreen(
                      propertyId: property.id,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              _ProjectSection(
                projects: profile.projects,
                onTap: (project) => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => RealEstateProjectDetailsScreen(
                      projectId: project.id,
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

  Future<void> _launchUri(String value) async {
    final uri = Uri.parse(value);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }
}

class RealEstateFavoritesScreen extends StatelessWidget {
  const RealEstateFavoritesScreen({
    super.key,
    required this.guestId,
    this.showAppBar = true,
  });

  final String guestId;
  final bool showAppBar;

  @override
  Widget build(BuildContext context) {
    final api = RealEstateApiClient();
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: showAppBar
          ? AppBar(
              title: const Text('Favorite properties'),
              backgroundColor: Theme.of(context).cardColor,
              foregroundColor: _reInk,
              elevation: 0,
            )
          : null,
      body: FutureBuilder<List<RealEstateProperty>>(
        future: api.fetchFavorites(guestId),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(cardCount: 4, showHero: false);
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: () {});
          }
          final properties = snapshot.data ?? const [];
          if (properties.isEmpty) {
            return const _EmptyState(message: 'No favorite properties yet.');
          }
          return ListView.builder(
            padding: const EdgeInsets.all(18),
            itemCount: properties.length,
            itemBuilder: (context, index) {
              final property = properties[index];
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _PropertyCard(
                  property: property,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => RealEstatePropertyDetailsScreen(
                        propertyId: property.id,
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class RealEstateSavedSearchesScreen extends StatelessWidget {
  const RealEstateSavedSearchesScreen({
    super.key,
    required this.guestId,
    this.showAppBar = true,
  });

  final String guestId;
  final bool showAppBar;

  @override
  Widget build(BuildContext context) {
    final api = RealEstateApiClient();
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: showAppBar
          ? AppBar(
              title: const Text('Saved searches'),
              backgroundColor: Theme.of(context).cardColor,
              foregroundColor: _reInk,
              elevation: 0,
            )
          : null,
      body: FutureBuilder<List<RealEstateSavedSearch>>(
        future: api.fetchSavedSearches(guestId),
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const AppSkeletonPage(cardCount: 4, showHero: false);
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: () {});
          }
          final searches = snapshot.data ?? const [];
          if (searches.isEmpty) {
            return const _EmptyState(message: 'No saved searches yet.');
          }
          return ListView.separated(
            padding: const EdgeInsets.all(18),
            itemCount: searches.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final search = searches[index];
              final filters = search.filters.entries
                  .where((entry) => entry.value.toString().trim().isNotEmpty)
                  .map((entry) => '${entry.key}: ${entry.value}')
                  .join(' • ');
              return _InfoCard(
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const CircleAvatar(
                    backgroundColor: _reSoft,
                    child: Icon(Icons.bookmark_border, color: _rePrimary),
                  ),
                  title: Text(
                    search.name,
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                  subtitle: Text(filters.isEmpty ? 'No filters' : filters),
                  trailing: search.notify ? const _Badge('alerts') : null,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => RealEstateSearchScreen(
                        initialQuery: search.filters['query']?.toString() ?? '',
                        initialPurpose:
                            search.filters['listing_purpose']?.toString() ?? '',
                        initialCategory:
                            search.filters['property_category']?.toString() ??
                                '',
                        initialBedrooms: _intFilter(search.filters['bedrooms']),
                        initialBudgetIndex:
                            _intFilter(search.filters['budget_index']),
                        initialFurnishing:
                            search.filters['furnishing']?.toString() ?? '',
                        initialMinArea: _intFilter(search.filters['min_area']),
                        initialVerifiedOnly:
                            search.filters['verified_only'] == true ||
                                search.filters['verified_only'] == 1,
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _PropertySection extends StatelessWidget {
  const _PropertySection({
    required this.title,
    required this.properties,
    required this.onTap,
    this.horizontal = false,
  });

  final String title;
  final List<RealEstateProperty> properties;
  final ValueChanged<RealEstateProperty> onTap;
  final bool horizontal;

  @override
  Widget build(BuildContext context) {
    if (properties.isEmpty) {
      return _InfoCard(
        title: title,
        child: const Text('Properties added from admin will appear here.'),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            color: _reInk,
            fontSize: 20,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 12),
        if (horizontal)
          SizedBox(
            height: 286,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: properties.length,
              separatorBuilder: (_, __) => const SizedBox(width: 12),
              itemBuilder: (context, index) => SizedBox(
                width: 238,
                child: _PropertyCard(
                  property: properties[index],
                  compact: true,
                  onTap: () => onTap(properties[index]),
                ),
              ),
            ),
          )
        else
          Column(
            children: [
              for (final property in properties.take(12))
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _PropertyCard(
                    property: property,
                    onTap: () => onTap(property),
                  ),
                ),
            ],
          ),
      ],
    );
  }
}

class _ProjectSection extends StatelessWidget {
  const _ProjectSection({required this.projects, required this.onTap});

  final List<RealEstateProject> projects;
  final ValueChanged<RealEstateProject> onTap;

  @override
  Widget build(BuildContext context) {
    if (projects.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'New projects',
          style: TextStyle(
            color: _reInk,
            fontSize: 20,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 156,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: projects.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (context, index) {
              final project = projects[index];
              return InkWell(
                borderRadius: BorderRadius.circular(22),
                onTap: () => onTap(project),
                child: Container(
                  width: 260,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: adaptiveSurface(context, _reDarkCard),
                    borderRadius: BorderRadius.circular(22),
                  ),
                  child: Row(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(16),
                        child: SizedBox(
                          width: 94,
                          height: 128,
                          child: _NetworkImageOrIcon(url: project.thumbnailUrl),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              project.name,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: _reInk,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              project.locationLabel,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(color: Color(0xFF718096)),
                            ),
                            const Spacer(),
                            Text(
                              project.builderName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: _rePrimary,
                                fontWeight: FontWeight.w800,
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
        ),
      ],
    );
  }
}

class _PropertyCard extends StatelessWidget {
  const _PropertyCard({
    required this.property,
    required this.onTap,
    this.compact = false,
  });

  final RealEstateProperty property;
  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: adaptiveSurface(context, _reDarkCard),
          borderRadius: BorderRadius.circular(22),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.035),
              blurRadius: 14,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: compact
            ? Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: _cardChildren(),
              )
            : Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(16),
                    child: SizedBox(
                      width: 112,
                      height: 112,
                      child: _NetworkImageOrIcon(url: property.thumbnailUrl),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: _textChildren(),
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  List<Widget> _cardChildren() {
    return [
      ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: SizedBox(
          height: 132,
          width: double.infinity,
          child: _NetworkImageOrIcon(url: property.thumbnailUrl),
        ),
      ),
      const SizedBox(height: 10),
      ..._textChildren(),
    ];
  }

  List<Widget> _textChildren() {
    return [
      Row(
        children: [
          _Badge(property.purposeLabel),
          const Spacer(),
          if (property.isVerified)
            const Icon(Icons.verified_rounded, color: Colors.green, size: 18),
        ],
      ),
      const SizedBox(height: 8),
      Text(
        property.title,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(
          color: _reInk,
          fontSize: 16,
          fontWeight: FontWeight.w900,
        ),
      ),
      const SizedBox(height: 6),
      Text(
        property.locationLabel,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(color: Color(0xFF718096)),
      ),
      const SizedBox(height: 10),
      Text(
        _priceLabel(property),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(
          color: _rePrimary,
          fontSize: 18,
          fontWeight: FontWeight.w900,
        ),
      ),
      const SizedBox(height: 8),
      Text(
        '${property.bedrooms} bed • ${property.bathrooms} bath • ${property.displayArea.toStringAsFixed(0)} ${property.areaUnit}',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(color: Color(0xFF718096), fontSize: 12),
      ),
    ];
  }
}

class _SpecsRow extends StatelessWidget {
  const _SpecsRow({required this.property});

  final RealEstateProperty property;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
            child:
                _SpecTile(Icons.bed_outlined, '${property.bedrooms}', 'Beds')),
        const SizedBox(width: 10),
        Expanded(
            child: _SpecTile(
                Icons.bathtub_outlined, '${property.bathrooms}', 'Baths')),
        const SizedBox(width: 10),
        Expanded(
          child: _SpecTile(
            Icons.square_foot_outlined,
            property.displayArea.toStringAsFixed(0),
            property.areaUnit,
          ),
        ),
      ],
    );
  }
}

class _GalleryStrip extends StatelessWidget {
  const _GalleryStrip({required this.images});

  final List<String> images;

  @override
  Widget build(BuildContext context) {
    return _InfoCard(
      title: 'Gallery',
      child: SizedBox(
        height: 92,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          itemCount: images.length,
          separatorBuilder: (_, __) => const SizedBox(width: 10),
          itemBuilder: (context, index) {
            return InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => _ImageGalleryScreen(
                    images: images,
                    initialIndex: index,
                  ),
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: SizedBox(
                  width: 118,
                  height: 92,
                  child: _NetworkImageOrIcon(url: images[index]),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _ImageGalleryScreen extends StatefulWidget {
  const _ImageGalleryScreen({
    required this.images,
    required this.initialIndex,
  });

  final List<String> images;
  final int initialIndex;

  @override
  State<_ImageGalleryScreen> createState() => _ImageGalleryScreenState();
}

class _ImageGalleryScreenState extends State<_ImageGalleryScreen> {
  late final PageController _controller =
      PageController(initialPage: widget.initialIndex);
  late int _index = widget.initialIndex;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text('${_index + 1}/${widget.images.length}'),
      ),
      body: PageView.builder(
        controller: _controller,
        itemCount: widget.images.length,
        onPageChanged: (value) => setState(() => _index = value),
        itemBuilder: (context, index) {
          return InteractiveViewer(
            minScale: 1,
            maxScale: 4,
            child: Center(
              child: Image.network(
                widget.images[index],
                fit: BoxFit.contain,
                loadingBuilder: (context, child, progress) {
                  if (progress == null) return child;
                  return const AppDelayedSkeletonBox(height: 220, radius: 0);
                },
                errorBuilder: (_, __, ___) => const Icon(
                  Icons.broken_image_outlined,
                  color: Colors.white,
                  size: 42,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _TrustStrip extends StatelessWidget {
  const _TrustStrip({required this.property});

  final RealEstateProperty property;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _TrustTile(
            icon: Icons.verified_user_outlined,
            title: property.isVerified ? 'Verified' : 'Under review',
            subtitle: 'Listing check',
          ),
        ),
        const SizedBox(width: 10),
        const Expanded(
          child: _TrustTile(
            icon: Icons.support_agent_outlined,
            title: 'Assisted',
            subtitle: 'Inquiry flow',
          ),
        ),
      ],
    );
  }
}

class _TrustTile extends StatelessWidget {
  const _TrustTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _reDarkCard),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: adaptiveLine(context, _reDarkLine)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: _reSoft,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: _rePrimary),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: _reInk,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Text(
                  subtitle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SpecTile extends StatelessWidget {
  const _SpecTile(this.icon, this.value, this.label);

  final IconData icon;
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _reDarkCard),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          Icon(icon, color: _rePrimary),
          const SizedBox(height: 8),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w900)),
          Text(label, style: const TextStyle(color: Color(0xFF718096))),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({this.title, required this.child});

  final String? title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _reDarkCard),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title != null) ...[
            Text(
              title!,
              style: const TextStyle(
                color: _reInk,
                fontSize: 18,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 12),
          ],
          child,
        ],
      ),
    );
  }
}

class _HeroImage extends StatelessWidget {
  const _HeroImage({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        _NetworkImageOrIcon(url: url),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Colors.transparent, Color(0x66000000)],
            ),
          ),
        ),
      ],
    );
  }
}

class _NetworkImageOrIcon extends StatelessWidget {
  const _NetworkImageOrIcon({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    if (url.trim().isEmpty) {
      return Container(
        color: _reSoft,
        child:
            const Icon(Icons.apartment_outlined, color: _rePrimary, size: 42),
      );
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return const AppDelayedSkeletonBox(height: 190, radius: 0);
      },
      errorBuilder: (_, __, ___) => Container(
        color: _reSoft,
        child:
            const Icon(Icons.apartment_outlined, color: _rePrimary, size: 42),
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      selectedColor: _rePrimary.withValues(alpha: 0.16),
      onSelected: (_) => onTap(),
      labelStyle: TextStyle(
        color: selected ? _rePrimary : _reInk,
        fontWeight: FontWeight.w800,
      ),
      side: BorderSide.none,
      backgroundColor: Theme.of(context).cardColor,
    );
  }
}

class _SegmentedOptions<T> extends StatelessWidget {
  const _SegmentedOptions({
    required this.value,
    required this.options,
    required this.onChanged,
  });

  final T value;
  final Map<T, String> options;
  final ValueChanged<T> onChanged;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final entry in options.entries)
          ChoiceChip(
            label: Text(entry.value),
            selected: entry.key == value,
            selectedColor: _rePrimary.withValues(alpha: .14),
            backgroundColor: const Color(0xFFF5F7FB),
            side: BorderSide.none,
            labelStyle: TextStyle(
              color: entry.key == value ? _rePrimary : _reInk,
              fontWeight: FontWeight.w800,
              fontSize: 12,
            ),
            onSelected: (_) => onChanged(entry.key),
          ),
      ],
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge(this.label, {this.green = false});

  final String label;
  final bool green;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: green ? const Color(0xFFE7F8EF) : _reSoft,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: green ? const Color(0xFF14945A) : _rePrimary,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _RealEstateVersionNotice extends StatelessWidget {
  const _RealEstateVersionNotice({
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
        color: _reSoft,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE8D4CB)),
      ),
      child: Row(
        children: [
          const Icon(Icons.system_update_alt_rounded, color: _rePrimary),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'App update available. Current: $currentVersion  Latest: $latestVersion',
              style: const TextStyle(
                color: _reInk,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final normalized = message.toLowerCase();
    final isNotFound = normalized.contains('not found');
    return AppErrorState(
      title: isNotFound ? message : 'Real estate data could not load',
      detail: isNotFound ? null : 'Check your connection and try again.',
      onRetry: onRetry,
      icon: Icons.location_city_outlined,
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return AppActivityEmptyState(
      icon: Icons.location_city_outlined,
      title: message,
      detail: 'Saved visits, inquiries and property activity will appear here.',
      accent: _rePrimary,
      padding: const EdgeInsets.all(28),
    );
  }
}

class _LeadData {
  const _LeadData({
    required this.name,
    required this.phone,
    required this.email,
    required this.note,
    required this.date,
    required this.time,
  });

  final String name;
  final String phone;
  final String email;
  final String note;
  final String date;
  final String time;
}

void _showLeadSheet({
  required BuildContext context,
  required String title,
  required String primaryLabel,
  required bool needsDateTime,
  required String initialName,
  required String initialPhone,
  required String initialEmail,
  required Future<String> Function(_LeadData data) onSubmit,
}) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Theme.of(context).cardColor,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
    ),
    builder: (sheetContext) => _LeadSheet(
      title: title,
      primaryLabel: primaryLabel,
      needsDateTime: needsDateTime,
      initialName: initialName,
      initialPhone: initialPhone,
      initialEmail: initialEmail,
      onSubmit: onSubmit,
    ),
  );
}

class _LeadSheet extends StatefulWidget {
  const _LeadSheet({
    required this.title,
    required this.primaryLabel,
    required this.needsDateTime,
    required this.initialName,
    required this.initialPhone,
    required this.initialEmail,
    required this.onSubmit,
  });

  final String title;
  final String primaryLabel;
  final bool needsDateTime;
  final String initialName;
  final String initialPhone;
  final String initialEmail;
  final Future<String> Function(_LeadData data) onSubmit;

  @override
  State<_LeadSheet> createState() => _LeadSheetState();
}

class _LeadSheetState extends State<_LeadSheet> {
  late final _name = TextEditingController(text: widget.initialName);
  late final _phone = TextEditingController(text: widget.initialPhone);
  late final _email = TextEditingController(text: widget.initialEmail);
  final _note = TextEditingController();
  DateTime? _date;
  TimeOfDay? _time;
  bool _submitting = false;

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _note.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final inset = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(18, 18, 18, inset + 18),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.title,
              style: const TextStyle(
                color: _reInk,
                fontSize: 22,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 16),
            _LeadField(
                controller: _name, label: 'Name', icon: Icons.person_outline),
            _LeadField(
                controller: _phone, label: 'Phone', icon: Icons.phone_outlined),
            _LeadField(
              controller: _email,
              label: 'Email optional',
              icon: Icons.mail_outline,
            ),
            if (widget.needsDateTime) ...[
              Row(
                children: [
                  Expanded(
                    child: _PickButton(
                      label: _date == null
                          ? 'Select date'
                          : '${_date!.year}-${_date!.month.toString().padLeft(2, '0')}-${_date!.day.toString().padLeft(2, '0')}',
                      icon: Icons.calendar_month_outlined,
                      onTap: _pickDate,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _PickButton(
                      label: _time == null
                          ? 'Select time'
                          : _time!.format(context),
                      icon: Icons.schedule_outlined,
                      onTap: _pickTime,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
            ],
            _LeadField(
              controller: _note,
              label: 'Message optional',
              icon: Icons.notes_outlined,
              maxLines: 3,
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: _rePrimary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(
                        width: 96,
                        child: AppSkeletonBox(
                          height: 14,
                          radius: 8,
                        ),
                      )
                    : Text(widget.primaryLabel),
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
      initialDate: now.add(const Duration(days: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 90)),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked != null) setState(() => _time = picked);
  }

  Future<void> _submit() async {
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Name and phone are required')),
      );
      return;
    }
    if (widget.needsDateTime && (_date == null || _time == null)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select date and time')),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      final dateText = _date == null
          ? ''
          : '${_date!.year}-${_date!.month.toString().padLeft(2, '0')}-${_date!.day.toString().padLeft(2, '0')}';
      final timeText = _time == null
          ? ''
          : '${_time!.hour.toString().padLeft(2, '0')}:${_time!.minute.toString().padLeft(2, '0')}';
      final message = await widget.onSubmit(
        _LeadData(
          name: _name.text.trim(),
          phone: _phone.text.trim(),
          email: _email.text.trim(),
          note: _note.text.trim(),
          date: dateText,
          time: timeText,
        ),
      );
      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(message)));
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }
}

class _LeadField extends StatelessWidget {
  const _LeadField({
    required this.controller,
    required this.label,
    required this.icon,
    this.maxLines = 1,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final int maxLines;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: TextField(
        controller: controller,
        maxLines: maxLines,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon),
          filled: true,
          fillColor: const Color(0xFFF4F6FA),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }
}

class _PickButton extends StatelessWidget {
  const _PickButton({
    required this.label,
    required this.icon,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      style: OutlinedButton.styleFrom(
        foregroundColor: _reInk,
        side: const BorderSide(color: Color(0xFFE1E7F0)),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      ),
      onPressed: onTap,
      icon: Icon(icon, size: 18),
      label: Text(
        label,
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}

String _priceLabel(RealEstateProperty property) {
  final amount = property.price >= 10000000
      ? '${(property.price / 10000000).toStringAsFixed(2)} Cr'
      : property.price >= 100000
          ? '${(property.price / 100000).toStringAsFixed(2)} L'
          : property.price.toStringAsFixed(0);
  final suffix = property.listingPurpose == 'rent' ? '/month' : '';
  return '₹$amount$suffix';
}

String _priceFromLabel(double price) {
  if (price <= 0) return 'Price TBA';
  final amount = price >= 10000000
      ? '${(price / 10000000).toStringAsFixed(2)} Cr'
      : price >= 100000
          ? '${(price / 100000).toStringAsFixed(2)} L'
          : price.toStringAsFixed(0);
  return '₹$amount+';
}

const _budgetRanges = <(double, double)>[
  (0, 0),
  (0, 5000000),
  (5000000, 10000000),
  (10000000, 0),
];

int _intFilter(Object? value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

Future<void> _openMapLocation({
  required double latitude,
  required double longitude,
  required String label,
}) async {
  final encoded = Uri.encodeComponent('$label @$latitude,$longitude');
  final uri = Uri.parse(
    'https://www.google.com/maps/search/?api=1&query=$encoded',
  );
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}
