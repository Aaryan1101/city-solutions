// ignore_for_file: unused_element

import 'package:flutter/material.dart';

import '../../core/adaptive_colors.dart';
import '../../core/app_assets.dart';
import '../../core/app_theme.dart';
import '../ads/floating_ad_overlay.dart';
import '../bookings/bookings_screen.dart';
import '../cart/unified_cart_screen.dart';
import '../mart/presentation/mart_account_screen.dart';
import '../complaint/complaint_screen.dart';
import '../ecommerce/data/ecommerce_api_client.dart';
import '../ecommerce/data/ecommerce_session_store.dart';
import '../ecommerce/presentation/ecommerce_home_screen.dart';
import '../ecommerce/presentation/ecommerce_notifications_screen.dart';
import '../hotel/data/hotel_api_client.dart';
import '../hotel/presentation/hotel_home_screen.dart';
import '../mart/data/mart_api_client.dart';
import '../mart/data/mart_session_store.dart';
import '../mart/presentation/mart_home_screen.dart';
import '../mart/presentation/mart_notifications_screen.dart';
import '../medical/presentation/medical_home_screen.dart';
import '../modules/city_module.dart';
import '../offers/offers_screen.dart';
import '../orders/orders_screen.dart';
import '../real_estate/data/real_estate_api_client.dart';
import '../real_estate/presentation/real_estate_home_screen.dart';
import '../restaurant/presentation/restaurant_home_screen.dart';
import '../services/data/services_api_client.dart';
import '../services/presentation/services_home_screen.dart';
import '../taxi/presentation/taxi_home_screen.dart';
import '../zone/location_picker_screen.dart';
import '../zone/zone_models.dart';
import '../zone/zone_store.dart';
import 'home_search_screen.dart';

const _homeDarkBg = Color(0xFF070807);
const _homeDarkPanel = Color(0xFF111211);
const _homeDarkCard = Color(0xFF181A18);
const _homeDarkLine = Color(0xFF242824);
const _homeLime = Color(0xFFB9FF38);
const _homeText = Color(0xFFF7FAF3);
const _homeMuted = Color(0xFF8D978C);

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      FloatingAdGate.enable();
    });
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      _HomeContent(onTabRequested: (index) {
        setState(() => _selectedIndex = index);
      }),
      const BookingsScreen(),
      const OrdersScreen(),
      const OffersScreen(),
      const MartAccountScreen(showAppBar: false),
    ];

    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _homeDarkBg),
      body: pages[_selectedIndex],
      bottomNavigationBar: _CityBottomNav(
        selectedIndex: _selectedIndex,
        onTap: (index) => setState(() => _selectedIndex = index),
      ),
    );
  }
}

class _HomeContent extends StatefulWidget {
  const _HomeContent({required this.onTabRequested});

  final ValueChanged<int> onTabRequested;

  @override
  State<_HomeContent> createState() => _HomeContentState();
}

class _HomeContentState extends State<_HomeContent> {
  @override
  void initState() {
    super.initState();
    ZoneStore.instance.load().then((_) {
      _applySelectedLocation(ZoneStore.instance.selectedLocation.value);
      if (mounted) setState(() {});
    });
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _HomeGradientHeader(onActivityTap: () {
                  widget.onTabRequested(2);
                }, onAccountTap: () {
                  widget.onTabRequested(4);
                }),
                const _SearchBox(),
                _QuickChipRow(
                  onReorder: () => widget.onTabRequested(2),
                  onNearMe: () => _openLocationPicker(context),
                  onOffers: () => widget.onTabRequested(3),
                ),
                const Padding(
                  padding: EdgeInsets.fromLTRB(18, 14, 18, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _PromoStrip(),
                      SizedBox(height: 20),
                      _SectionHeader(title: 'Everything in one place'),
                      SizedBox(height: 14),
                      _ModuleGrid(modules: cityModules),
                      SizedBox(height: 22),
                      _RaiseComplaintBanner(),
                      SizedBox(height: 24),
                      _SectionHeader(
                        title: 'Trending near you',
                        trailing: 'See all',
                      ),
                      SizedBox(height: 14),
                      _TrendingStrip(),
                      SizedBox(height: 104),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

void _applySelectedLocation(SelectedLocation? location) {
  final id = location?.zone.id;
  MartZoneSelection.currentZoneId = id;
  EcommerceZoneSelection.currentZoneId = id;
  HotelZoneSelection.currentZoneId = id;
  ServicesZoneSelection.currentZoneId = id;
  RealEstateZoneSelection.currentZoneId = id;
}

Future<void> _openLocationPicker(BuildContext context) async {
  final location = await Navigator.of(context).push<SelectedLocation>(
    MaterialPageRoute(builder: (_) => const LocationPickerScreen()),
  );
  if (location != null) {
    _applySelectedLocation(location);
  }
}

// ── Shell Navigation ────────────────────────────────────────────────────────

class _CityBottomNav extends StatelessWidget {
  const _CityBottomNav({
    required this.selectedIndex,
    required this.onTap,
  });

  final int selectedIndex;
  final ValueChanged<int> onTap;

  static const _items = [
    (Icons.home_rounded, 'Home'),
    (Icons.calendar_month_outlined, 'Bookings'),
    (Icons.receipt_long_outlined, 'Orders'),
    (Icons.local_offer_outlined, 'Offers'),
    (Icons.person_outline_rounded, 'Account'),
  ];

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: adaptiveSurface(context, const Color(0xFF101210)),
        border: Border(
          top: BorderSide(color: adaptiveLine(context, _homeDarkLine)),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black
                .withValues(alpha: appIsDark(context) ? 0.34 : 0.07),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 68,
          child: Row(
            children: [
              for (var i = 0; i < _items.length; i++)
                Expanded(
                  child: _BottomNavItem(
                    icon: _items[i].$1,
                    label: _items[i].$2,
                    selected: i == selectedIndex,
                    onTap: () => onTap(i),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BottomNavItem extends StatelessWidget {
  const _BottomNavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final selectedColor = appIsDark(context) ? _homeLime : AppTheme.primary;
    final mutedColor = adaptiveMuted(context, _homeMuted);
    return InkWell(
      onTap: onTap,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.start,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            width: selected ? 28 : 0,
            height: 3,
            decoration: BoxDecoration(
              color: selected ? selectedColor : Colors.transparent,
              borderRadius: const BorderRadius.vertical(
                bottom: Radius.circular(3),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Icon(
            icon,
            color: selected ? selectedColor : mutedColor,
            size: 22,
          ),
          const SizedBox(height: 5),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: selected ? selectedColor : mutedColor,
              fontSize: 10.5,
              fontWeight: selected ? FontWeight.w900 : FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Redesigned Home Header ──────────────────────────────────────────────────

class _HomeGradientHeader extends StatelessWidget {
  const _HomeGradientHeader({
    required this.onActivityTap,
    required this.onAccountTap,
  });

  final VoidCallback onActivityTap;
  final VoidCallback onAccountTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 8, 18, 12),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: appIsDark(context)
              ? const [Color(0xFF070807), Color(0xFF0E120C)]
              : const [
                  Color(0xFFDCE8FF),
                  Color(0xFFF0E8FF),
                  Color(0xFFE4F8EE),
                ],
        ),
        border: Border(
          bottom: BorderSide(
            color: appIsDark(context)
                ? _homeDarkLine
                : AppTheme.primary.withValues(alpha: .12),
          ),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _HeaderBrandRow(onAccountTap: onAccountTap),
          const SizedBox(height: 8),
          _HomeWelcomeScene(
            colors: colors,
            onActivityTap: onActivityTap,
          ),
        ],
      ),
    );
  }
}

class _HeaderGlow extends StatelessWidget {
  const _HeaderGlow({required this.size, required this.opacity});

  final double size;
  final double opacity;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [
            Colors.white.withValues(alpha: opacity),
            Colors.white.withValues(alpha: 0),
          ],
        ),
      ),
    );
  }
}

class _HeaderBrandRow extends StatelessWidget {
  const _HeaderBrandRow({required this.onAccountTap});

  final VoidCallback onAccountTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Row(
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
            color: _homeLime,
            borderRadius: BorderRadius.circular(9),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: Image.asset(
              AppAssets.logo,
              fit: BoxFit.contain,
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: InkWell(
            onTap: () => _openLocationPicker(context),
            borderRadius: BorderRadius.circular(10),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 3),
              child: ValueListenableBuilder<SelectedLocation?>(
                valueListenable: ZoneStore.instance.selectedLocation,
                builder: (context, location, _) => Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'City Solution',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: colors.text,
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Icon(
                          Icons.location_on_rounded,
                          color:
                              appIsDark(context) ? _homeLime : AppTheme.primary,
                          size: 13,
                        ),
                        const SizedBox(width: 3),
                        Flexible(
                          child: Text(
                            location?.shortAddress ?? 'Select delivery address',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: colors.muted,
                              fontSize: 10.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        Icon(
                          Icons.keyboard_arrow_down_rounded,
                          color: colors.muted,
                          size: 14,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
        IconButton(
          tooltip: 'Cart',
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => const UnifiedCartScreen(),
            ),
          ),
          icon: const Icon(
            Icons.shopping_cart_outlined,
          ),
          color: colors.text,
        ),
        Stack(
          clipBehavior: Clip.none,
          children: [
            IconButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => const _NotificationsHubScreen(),
                ),
              ),
              icon: const Icon(Icons.notifications_none_rounded),
              color: colors.text,
            ),
            Positioned(
              right: 12,
              top: 10,
              child: Container(
                width: 7,
                height: 7,
                decoration: BoxDecoration(
                  color: AppTheme.danger,
                  shape: BoxShape.circle,
                  border: Border.all(color: colors.bg, width: 1.2),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(width: 6),
        FutureBuilder<String>(
          future: _homeAccountName(),
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return const AppDelayedSkeletonBox(
                width: 34,
                height: 34,
                radius: 17,
              );
            }
            final name = snapshot.data?.trim().isNotEmpty == true
                ? snapshot.data!.trim()
                : 'Customer';
            return InkWell(
              onTap: onAccountTap,
              customBorder: const CircleBorder(),
              child: Container(
                width: 36,
                height: 36,
                padding: const EdgeInsets.all(2),
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: SweepGradient(
                    colors: [
                      _homeLime,
                      Color(0xFFFF2F68),
                      _homeLime,
                    ],
                  ),
                ),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color:
                        appIsDark(context) ? colors.bg : AppTheme.primaryDark,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: Text(
                      name[0].toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }
}

class _HomeWelcomeScene extends StatelessWidget {
  const _HomeWelcomeScene({
    required this.colors,
    required this.onActivityTap,
  });

  final AdaptiveModuleColors colors;
  final VoidCallback onActivityTap;

  @override
  Widget build(BuildContext context) {
    final dark = appIsDark(context);
    return LayoutBuilder(
      builder: (context, constraints) {
        final compact = constraints.maxWidth < 350;
        return SizedBox(
          height: compact ? 152 : 166,
          child: Stack(
            fit: StackFit.expand,
            children: [
              Positioned.fill(
                top: compact ? 38 : 34,
                child: CustomPaint(
                  painter: _CityWelcomePainter(dark: dark),
                ),
              ),
              Align(
                alignment: Alignment.topCenter,
                child: FutureBuilder<String>(
                  future: _homeAccountName(),
                  builder: (context, snapshot) {
                    if (snapshot.connectionState != ConnectionState.done) {
                      return const AppDelayedSkeletonBox(
                        width: 210,
                        height: 25,
                        radius: 13,
                      );
                    }
                    final name = snapshot.data?.trim().isNotEmpty == true
                        ? snapshot.data!.trim()
                        : 'Customer';
                    return Text.rich(
                      TextSpan(
                        children: [
                          const TextSpan(
                            text: 'Welcome, ',
                            style: TextStyle(fontWeight: FontWeight.w700),
                          ),
                          TextSpan(
                            text: name,
                            style: const TextStyle(fontWeight: FontWeight.w900),
                          ),
                        ],
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: colors.text,
                        fontSize: compact ? 19 : 21,
                      ),
                    );
                  },
                ),
              ),
              Positioned(
                right: 0,
                bottom: 4,
                child: _LiveStatusChip(
                  onTap: onActivityTap,
                  compact: true,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _CityWelcomePainter extends CustomPainter {
  const _CityWelcomePainter({required this.dark});

  final bool dark;

  @override
  void paint(Canvas canvas, Size size) {
    final groundY = size.height - 20;
    final sky = dark ? const Color(0xFF16283B) : const Color(0xFFDCEFFF);
    final far = dark ? const Color(0xFF29445D) : const Color(0xFFB9DFF5);
    final mid = dark ? const Color(0xFF356481) : const Color(0xFF7FC4EA);
    final blue = dark ? const Color(0xFF4BA3D1) : const Color(0xFF258CD0);
    final deep = dark ? const Color(0xFF72C5E9) : const Color(0xFF1268A5);
    final window = dark ? const Color(0xFFFFD36A) : Colors.white;

    void building(Rect rect, Color color, {bool hotel = false}) {
      final paint = Paint()..color = color;
      canvas.drawRRect(
        RRect.fromRectAndRadius(rect, const Radius.circular(2)),
        paint,
      );
      final columns = (rect.width / 11).floor().clamp(2, 5);
      final rows = (rect.height / 15).floor().clamp(2, 5);
      final cellW = rect.width / (columns + 1);
      final cellH = rect.height / (rows + 1);
      final windowPaint = Paint()..color = window.withValues(alpha: .86);
      for (var row = 1; row <= rows; row++) {
        for (var col = 1; col <= columns; col++) {
          canvas.drawRect(
            Rect.fromCenter(
              center: Offset(
                rect.left + cellW * col,
                rect.top + cellH * row,
              ),
              width: 4.5,
              height: 7,
            ),
            windowPaint,
          );
        }
      }
      if (hotel) {
        final sign =
            Rect.fromLTWH(rect.left + 3, rect.top - 13, rect.width - 6, 13);
        canvas.drawRRect(
          RRect.fromRectAndRadius(sign, const Radius.circular(2)),
          Paint()..color = blue,
        );
        final textPainter = TextPainter(
          text: TextSpan(
            text: 'HOTEL',
            style: TextStyle(
              color: window,
              fontSize: 7,
              fontWeight: FontWeight.w900,
            ),
          ),
          textDirection: TextDirection.ltr,
        )..layout(maxWidth: sign.width);
        textPainter.paint(
          canvas,
          Offset(sign.center.dx - textPainter.width / 2, sign.top + 2),
        );
      }
    }

    void cloud(Offset center, double scale) {
      final paint = Paint()..color = sky.withValues(alpha: dark ? .48 : .88);
      canvas.drawOval(
        Rect.fromCenter(center: center, width: 46 * scale, height: 12 * scale),
        paint,
      );
      canvas.drawCircle(
          center.translate(-10 * scale, -4 * scale), 9 * scale, paint);
      canvas.drawCircle(
          center.translate(4 * scale, -7 * scale), 12 * scale, paint);
      canvas.drawCircle(
          center.translate(16 * scale, -3 * scale), 7 * scale, paint);
    }

    cloud(Offset(size.width * .10, 34), .65);
    cloud(Offset(size.width * .87, 25), .72);
    cloud(Offset(size.width * .62, 49), .42);

    building(Rect.fromLTWH(size.width * .07, groundY - 40, 30, 40), far);
    building(Rect.fromLTWH(size.width * .17, groundY - 57, 35, 57), mid);
    building(Rect.fromLTWH(size.width * .30, groundY - 74, 38, 74), blue);
    building(Rect.fromLTWH(size.width * .64, groundY - 57, 36, 57), mid);
    building(
      Rect.fromLTWH(size.width * .76, groundY - 76, 47, 76),
      blue,
      hotel: true,
    );
    building(Rect.fromLTWH(size.width * .91, groundY - 42, 25, 42), far);

    final houseLeft = size.width * .19;
    final house = Path()
      ..moveTo(houseLeft, groundY - 42)
      ..lineTo(houseLeft + 48, groundY - 83)
      ..lineTo(houseLeft + 96, groundY - 42)
      ..lineTo(houseLeft + 86, groundY - 42)
      ..lineTo(houseLeft + 86, groundY)
      ..lineTo(houseLeft + 10, groundY)
      ..lineTo(houseLeft + 10, groundY - 42)
      ..close();
    canvas.drawPath(house, Paint()..color = deep);
    canvas.drawPath(
      Path()
        ..moveTo(houseLeft - 6, groundY - 39)
        ..lineTo(houseLeft + 48, groundY - 88)
        ..lineTo(houseLeft + 102, groundY - 39),
      Paint()
        ..color = blue
        ..strokeWidth = 7
        ..strokeCap = StrokeCap.round
        ..style = PaintingStyle.stroke,
    );
    canvas.drawRect(
      Rect.fromLTWH(houseLeft + 42, groundY - 30, 16, 30),
      Paint()..color = blue,
    );
    for (final x in [houseLeft + 20, houseLeft + 69]) {
      canvas.drawRRect(
        RRect.fromRectAndRadius(
          Rect.fromLTWH(x, groundY - 40, 12, 16),
          const Radius.circular(2),
        ),
        Paint()..color = window,
      );
    }

    final roadPaint = Paint()..color = far.withValues(alpha: .48);
    canvas.drawOval(
      Rect.fromLTWH(0, groundY - 5, size.width, 22),
      roadPaint,
    );
    final scooterX = size.width * .50;
    final scooterY = groundY - 4;
    final wheelPaint = Paint()
      ..color = dark ? const Color(0xFFD7E6F3) : const Color(0xFF173B59)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 4;
    canvas.drawCircle(Offset(scooterX - 19, scooterY), 10, wheelPaint);
    canvas.drawCircle(Offset(scooterX + 27, scooterY), 10, wheelPaint);
    canvas.drawPath(
      Path()
        ..moveTo(scooterX - 18, scooterY - 3)
        ..quadraticBezierTo(
            scooterX - 3, scooterY - 19, scooterX + 20, scooterY - 7)
        ..lineTo(scooterX + 29, scooterY - 1)
        ..moveTo(scooterX + 15, scooterY - 8)
        ..lineTo(scooterX + 21, scooterY - 28)
        ..lineTo(scooterX + 30, scooterY - 31),
      Paint()
        ..color = blue
        ..style = PaintingStyle.stroke
        ..strokeWidth = 6
        ..strokeCap = StrokeCap.round,
    );
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(scooterX - 31, scooterY - 25, 27, 16),
        const Radius.circular(4),
      ),
      Paint()..color = deep,
    );
    canvas.drawCircle(
      Offset(scooterX + 2, scooterY - 47),
      7,
      Paint()..color = dark ? const Color(0xFFFFC58E) : const Color(0xFF935530),
    );
    canvas.drawPath(
      Path()
        ..moveTo(scooterX + 1, scooterY - 39)
        ..lineTo(scooterX + 12, scooterY - 21)
        ..lineTo(scooterX + 23, scooterY - 26),
      Paint()
        ..color = dark ? const Color(0xFF7FD6F4) : const Color(0xFF075A91)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 7
        ..strokeCap = StrokeCap.round,
    );
  }

  @override
  bool shouldRepaint(covariant _CityWelcomePainter oldDelegate) =>
      oldDelegate.dark != dark;
}

class _LiveStatusChip extends StatelessWidget {
  const _LiveStatusChip({required this.onTap, this.compact = false});

  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(13),
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: MediaQuery.sizeOf(context).width - 36,
        ),
        child: Container(
          padding: EdgeInsets.fromLTRB(
            compact ? 8 : 9,
            compact ? 6 : 7,
            compact ? 8 : 10,
            compact ? 6 : 7,
          ),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: colors.line),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 7,
                height: 7,
                decoration: BoxDecoration(
                  color: AppTheme.success,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.success.withValues(alpha: 0.35),
                      blurRadius: 0,
                      spreadRadius: 4,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Text(
                  'Track active orders & bookings',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: compact ? 10.5 : 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              const SizedBox(width: 5),
              Icon(
                Icons.chevron_right_rounded,
                color: colors.muted,
                size: 15,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NotificationsHubScreen extends StatelessWidget {
  const _NotificationsHubScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
        children: [
          _NotificationModuleTile(
            title: 'Mart notifications',
            subtitle: 'Order updates, refunds and grocery alerts',
            icon: Icons.storefront_outlined,
            color: const Color(0xFF20A66A),
            onTap: () async {
              final session = await MartSessionStore().load();
              if (!context.mounted) return;
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) =>
                      MartNotificationsScreen(guestId: session.guestId),
                ),
              );
            },
          ),
          _NotificationModuleTile(
            title: 'E-Commerce notifications',
            subtitle: 'Shopping order and vendor updates',
            icon: Icons.shopping_bag_outlined,
            color: AppTheme.primary,
            onTap: () async {
              final session = await EcommerceSessionStore().load();
              if (!context.mounted) return;
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => EcommerceNotificationsScreen(
                    guestId: session.guestId,
                  ),
                ),
              );
            },
          ),
          _NotificationModuleTile(
            title: 'Medical notifications',
            subtitle: 'Prescription, medicine and pharmacy updates',
            icon: Icons.medication_liquid_outlined,
            color: const Color(0xFFE94C3D),
            onTap: () async {
              final session = await EcommerceSessionStore().load();
              if (!context.mounted) return;
              Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => EcommerceNotificationsScreen(
                    guestId: session.guestId,
                    apiBaseUrl:
                        'https://snow-grouse-381496.hostingersite.com/api/v1/medical',
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

class _NotificationModuleTile extends StatelessWidget {
  const _NotificationModuleTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(18),
          child: Ink(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: colors.card,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: colors.line),
            ),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
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
                        style: TextStyle(
                          color: colors.text,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        subtitle,
                        style: TextStyle(
                          color: colors.muted,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.chevron_right_rounded,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SkylineWatermark extends StatelessWidget {
  const _SkylineWatermark();

  @override
  Widget build(BuildContext context) {
    final heights = [30.0, 46.0, 26.0, 38.0, 18.0, 42.0, 58.0, 22.0, 34.0];
    return Opacity(
      opacity: 0.13,
      child: SizedBox(
        height: 60,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            const SizedBox(width: 14),
            const _RoofMark(),
            for (final height in heights) ...[
              const SizedBox(width: 6),
              Container(
                width: height > 40 ? 24 : 17,
                height: height,
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(
                    top: Radius.circular(3),
                  ),
                ),
              ),
            ],
            const SizedBox(width: 6),
            const _RoofMark(),
          ],
        ),
      ),
    );
  }
}

class _RoofMark extends StatelessWidget {
  const _RoofMark();

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: const Size(25, 18),
      painter: _RoofPainter(),
    );
  }
}

class _RoofPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final path = Path()
      ..moveTo(0, size.height)
      ..lineTo(size.width / 2, 0)
      ..lineTo(size.width, size.height)
      ..close();
    canvas.drawPath(path, Paint()..color = Colors.white);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

// ── Top Bar ─────────────────────────────────────────────────────────────────

class _TopBar extends StatelessWidget {
  const _TopBar();

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final compact = width < 360;
        final logoSize = (width * 0.12).clamp(36.0, 46.0);
        final actionSize = (width * 0.11).clamp(36.0, 44.0);
        final titleSize = compact ? 15.0 : 17.0;
        final gap = compact ? 6.0 : 10.0;

        return Container(
          padding: EdgeInsets.symmetric(
            horizontal: compact ? 8 : 10,
            vertical: compact ? 7 : 8,
          ),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(compact ? 14 : 16),
            border: Border.all(color: Theme.of(context).dividerColor),
          ),
          child: Row(
            children: [
              SizedBox.square(
                dimension: logoSize,
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: const Color(0xFFF5FAFF),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(3),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(9),
                      child: Image.asset(
                        AppAssets.logo,
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                ),
              ),
              SizedBox(width: gap),
              Expanded(
                child: InkWell(
                  borderRadius: BorderRadius.circular(10),
                  onTap: () => _openLocationPicker(context),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 2),
                    child: ValueListenableBuilder<SelectedLocation?>(
                      valueListenable: ZoneStore.instance.selectedLocation,
                      builder: (context, location, _) {
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              'City Solutions',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: titleSize,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Icon(Icons.location_on_outlined,
                                    color: AppTheme.primary, size: 14),
                                const SizedBox(width: 2),
                                Expanded(
                                  child: Text(
                                    location?.shortAddress ??
                                        'Select delivery address',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      color: location == null
                                          ? AppTheme.primary
                                          : Theme.of(context)
                                              .colorScheme
                                              .onSurfaceVariant,
                                      fontSize: compact ? 10.5 : 11.5,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                const Icon(Icons.keyboard_arrow_down_rounded,
                                    size: 16),
                              ],
                            ),
                          ],
                        );
                      },
                    ),
                  ),
                ),
              ),
              SizedBox(width: compact ? 2 : 4),
              SizedBox.square(
                dimension: actionSize,
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Center(
                      child: IconButton(
                        padding: EdgeInsets.zero,
                        constraints: BoxConstraints.tightFor(
                          width: actionSize,
                          height: actionSize,
                        ),
                        onPressed: () {},
                        icon: Icon(
                          Icons.notifications_outlined,
                          size: compact ? 23 : 25,
                        ),
                      ),
                    ),
                    Positioned(
                      right: 1,
                      top: 1,
                      child: Container(
                        width: compact ? 16 : 18,
                        height: compact ? 16 : 18,
                        alignment: Alignment.center,
                        decoration: const BoxDecoration(
                          color: Color(0xFFE94C3D),
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '3',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: compact ? 9 : 10,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              SizedBox(width: compact ? 4 : 6),
              Container(
                width: actionSize,
                height: actionSize,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF7ED3FF), AppTheme.primary],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primary.withValues(alpha: 0.28),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: FutureBuilder<String>(
                  future: _homeAccountName(),
                  builder: (context, snapshot) {
                    if (snapshot.connectionState != ConnectionState.done) {
                      return const AppDelayedSkeletonBox(
                        width: 22,
                        height: 22,
                        radius: 11,
                      );
                    }
                    final name = snapshot.data?.trim().isNotEmpty == true
                        ? snapshot.data!.trim()
                        : 'Customer';
                    return Center(
                      child: Text(
                        name[0].toUpperCase(),
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: compact ? 16 : 18,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

// ── City Hero Banner ─────────────────────────────────────────────────────────

class _CityHero extends StatelessWidget {
  const _CityHero();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 190,
      margin: const EdgeInsets.fromLTRB(18, 16, 18, 0),
      child: Stack(
        alignment: Alignment.bottomCenter,
        children: [
          Positioned.fill(child: CustomPaint(painter: _CityHeroPainter())),
          Positioned(
            top: 6,
            child: FutureBuilder<String>(
              future: _homeAccountName(),
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) {
                  return const AppDelayedSkeletonBox(
                    width: 180,
                    height: 24,
                    radius: 12,
                  );
                }
                final name = snapshot.data?.trim().isNotEmpty == true
                    ? snapshot.data!.trim()
                    : 'Customer';
                return Column(
                  children: [
                    Text(
                      'Welcome, $name',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    ValueListenableBuilder<SelectedLocation?>(
                      valueListenable: ZoneStore.instance.selectedLocation,
                      builder: (context, location, _) {
                        return Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.location_on,
                                size: 16, color: AppTheme.primary),
                            const SizedBox(width: 3),
                            ConstrainedBox(
                              constraints: BoxConstraints(
                                maxWidth:
                                    MediaQuery.of(context).size.width - 80,
                              ),
                              child: Text(
                                location?.shortAddress ??
                                    'Select delivery address',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                        );
                      },
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

Future<String> _homeAccountName() async {
  final mart = await MartSessionStore().load();
  if (mart.name.trim().isNotEmpty) return mart.name.trim();
  final ecommerce = await EcommerceSessionStore().load();
  if (ecommerce.name.trim().isNotEmpty) return ecommerce.name.trim();
  return 'Customer';
}

// ── Search Box ───────────────────────────────────────────────────────────────

class _SearchBox extends StatelessWidget {
  const _SearchBox();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const HomeSearchScreen()),
        ),
        borderRadius: BorderRadius.circular(17),
        child: Container(
          height: 56,
          margin: const EdgeInsets.fromLTRB(18, 16, 18, 0),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: colors.line),
            boxShadow: appIsDark(context)
                ? const []
                : [
                    BoxShadow(
                      color: AppTheme.primary.withValues(alpha: 0.10),
                      blurRadius: 16,
                      offset: const Offset(0, 7),
                    ),
                  ],
          ),
          child: Row(
            children: [
              Icon(Icons.search_rounded, color: colors.muted, size: 23),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Search medicine, groceries, services...',
                  style: TextStyle(
                    color: colors.muted,
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Icon(Icons.mic_none_rounded, color: colors.muted, size: 20),
              const SizedBox(width: 10),
              SizedBox(
                height: 20,
                child: VerticalDivider(width: 1, color: colors.line),
              ),
              const SizedBox(width: 10),
              Icon(Icons.tune_rounded, color: colors.muted, size: 20),
            ],
          ),
        ),
      ),
    );
  }
}

class _QuickChipRow extends StatelessWidget {
  const _QuickChipRow({
    required this.onReorder,
    required this.onNearMe,
    required this.onOffers,
  });

  final VoidCallback onReorder;
  final VoidCallback onNearMe;
  final VoidCallback onOffers;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 50,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 8, 18, 4),
        scrollDirection: Axis.horizontal,
        children: [
          _QuickChip(
            icon: Icons.restart_alt_rounded,
            label: 'Reorder',
            color: const Color(0xFFE8912A),
            onTap: onReorder,
          ),
          _QuickChip(
            icon: Icons.near_me_outlined,
            label: 'Near me',
            color: AppTheme.primary,
            onTap: onNearMe,
          ),
          _QuickChip(
            icon: Icons.local_offer_outlined,
            label: 'Offers for you',
            color: AppTheme.danger,
            onTap: onOffers,
          ),
        ],
      ),
    );
  }
}

class _QuickChip extends StatelessWidget {
  const _QuickChip({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: const Color(0xFF181A18),
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(12),
          child: Ink(
            padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 8),
            decoration: BoxDecoration(
              color: colors.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: colors.line),
            ),
            child: Row(
              children: [
                Icon(icon, color: color, size: 15),
                const SizedBox(width: 7),
                Text(
                  label,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _PromoStrip extends StatelessWidget {
  const _PromoStrip();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 108,
      child: Row(
        children: [
          Expanded(
            child: _PromoCard(
              title: '50% off first Mart order',
              subtitle: 'Use code FRESH50',
              icon: Icons.eco_outlined,
              darkColors: const [Color(0xFF123B26), Color(0xFF081C13)],
              lightColors: const [Color(0xFFDDF6E8), Color(0xFFC9EFD9)],
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => const MartHomeScreen()),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _PromoCard(
              title: 'Home cleaning from ₹999',
              subtitle: 'Book in under 60 seconds',
              icon: Icons.cleaning_services_outlined,
              darkColors: const [Color(0xFF321B0D), Color(0xFF1A100A)],
              lightColors: const [Color(0xFFFFE9DA), Color(0xFFFFDCC3)],
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => const ServicesHomeScreen(),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PromoCard extends StatelessWidget {
  const _PromoCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.darkColors,
    required this.lightColors,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final List<Color> darkColors;
  final List<Color> lightColors;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final dark = appIsDark(context);
    final palette = dark ? darkColors : lightColors;
    final textColor =
        dark ? Colors.white : Theme.of(context).colorScheme.onSurface;
    final mutedColor = dark
        ? Colors.white.withValues(alpha: .78)
        : AppTheme.ink.withValues(alpha: .72);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: palette,
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: dark
                  ? Colors.white.withValues(alpha: 0.08)
                  : palette.first.withValues(alpha: .95),
            ),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: Stack(
              children: [
                Positioned(
                  right: -12,
                  bottom: -16,
                  child: Icon(
                    icon,
                    color: textColor.withValues(alpha: 0.14),
                    size: 72,
                  ),
                ),
                Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: textColor,
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                        height: 1.12,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      subtitle,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: mutedColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        height: 1.2,
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

// ── Raise Complaint Banner ────────────────────────────────────────────────────

class _RaiseComplaintBanner extends StatelessWidget {
  const _RaiseComplaintBanner();

  @override
  Widget build(BuildContext context) {
    final dark = appIsDark(context);
    final text = dark ? Colors.white : Theme.of(context).colorScheme.onSurface;
    final muted =
        dark ? Colors.white70 : Theme.of(context).colorScheme.onSurfaceVariant;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => const ComplaintScreen()),
        ),
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          decoration: BoxDecoration(
            color: dark ? const Color(0xFF180B11) : const Color(0xFFFFEDF3),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFFFF2F68), width: 1.4),
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            child: Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: const Color(0xFFFF2F68),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(Icons.report_problem_outlined,
                      color: Colors.white, size: 24),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Raise Your Complaint',
                        style: TextStyle(
                          color: text,
                          fontSize: 13,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        'Report potholes, streetlights & more',
                        style: TextStyle(
                          color: muted,
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  width: 32,
                  height: 32,
                  decoration: const BoxDecoration(
                    color: Color(0xFFFF2F68),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.arrow_forward_rounded,
                      color: Colors.white, size: 18),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _TrendingStrip extends StatelessWidget {
  const _TrendingStrip();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 150,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final cardWidth = (constraints.maxWidth - 12) / 2;
          final cards = [
            _TrendingCard(
              width: cardWidth,
              name: 'Home deep cleaning',
              price: '₹1,299',
              rating: '4.7',
              icon: Icons.cleaning_services_outlined,
              accent: const Color(0xFFE8912A),
              bg: const Color(0xFF2C1A0D),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => const ServicesHomeScreen(),
                ),
              ),
            ),
            _TrendingCard(
              width: cardWidth,
              name: 'Fresh grocery basket',
              price: '₹499',
              rating: '4.8',
              icon: Icons.eco_outlined,
              accent: AppTheme.success,
              bg: const Color(0xFF07351E),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => const MartHomeScreen()),
              ),
            ),
            _TrendingCard(
              width: cardWidth,
              name: 'Hotel stays nearby',
              price: '₹1,999',
              rating: '4.5',
              icon: Icons.hotel_outlined,
              accent: const Color(0xFF149E8A),
              bg: const Color(0xFF082C2A),
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                    builder: (_) => const HotelHomeScreen()),
              ),
            ),
          ];
          return ListView.separated(
            scrollDirection: Axis.horizontal,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemCount: cards.length,
            itemBuilder: (context, index) => cards[index],
          );
        },
      ),
    );
  }
}

class _TrendingCard extends StatelessWidget {
  const _TrendingCard({
    required this.width,
    required this.name,
    required this.price,
    required this.rating,
    required this.icon,
    required this.accent,
    required this.bg,
    required this.onTap,
  });

  final double width;
  final String name;
  final String price;
  final String rating;
  final IconData icon;
  final Color accent;
  final Color bg;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: _homeDarkCard,
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Ink(
          width: width,
          decoration: BoxDecoration(
            color: colors.card,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: colors.line),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                height: 82,
                decoration: BoxDecoration(
                  color: appIsDark(context)
                      ? bg
                      : Color.alphaBlend(
                          accent.withValues(alpha: .14),
                          Colors.white,
                        ),
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(18),
                  ),
                ),
                child: Center(child: Icon(icon, color: accent, size: 32)),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 0),
                child: Text(
                  name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 12.5,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        price,
                        style: TextStyle(
                          color: colors.text,
                          fontSize: 13.5,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                    const Icon(Icons.star_rounded,
                        color: AppTheme.warning, size: 14),
                    Text(
                      rating,
                      style: const TextStyle(
                        color: Color(0xFFE8912A),
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Section Header ───────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, this.trailing});

  final String title;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: adaptiveText(context, _homeText),
              fontSize: 14,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        if (trailing != null)
          Text(
            trailing!,
            style: const TextStyle(
              color: _homeLime,
              fontSize: 11,
              fontWeight: FontWeight.w900,
            ),
          ),
      ],
    );
  }
}

// ── Module Grid ──────────────────────────────────────────────────────────────

class _ModuleGrid extends StatelessWidget {
  const _ModuleGrid({required this.modules});

  final List<CityModule> modules;

  @override
  Widget build(BuildContext context) {
    if (modules.isEmpty) return const SizedBox.shrink();

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: modules.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        mainAxisSpacing: 14,
        crossAxisSpacing: 10,
        childAspectRatio: 0.84,
      ),
      itemBuilder: (context, index) => _ModuleIconTile(
        module: modules[index],
        onTap: () => _push(context, modules[index].title),
      ),
    );
  }

  Future<void> _push(BuildContext context, String title) async {
    final moduleRequiresLocation = {
      'Mart',
      'E-Commerce',
      'Medical',
      'Services',
      'Hotel',
      'Real Estate',
      'Restaurant',
      'Taxi',
    }.contains(title);
    if (moduleRequiresLocation &&
        ZoneStore.instance.selectedLocation.value == null) {
      await _openLocationPicker(context);
      if (!context.mounted ||
          ZoneStore.instance.selectedLocation.value == null) {
        return;
      }
    }
    if (title == 'Mart') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const MartHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'E-Commerce') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const EcommerceHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Medical') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const MedicalHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Services') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const ServicesHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Hotel') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const HotelHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Real Estate') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const RealEstateHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Restaurant') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const RestaurantHomeScreen(),
        ),
      );
      return;
    }
    if (title == 'Taxi') {
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => const TaxiHomeScreen(),
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$title is not available in this build.')),
    );
  }
}

class _ModuleIconTile extends StatelessWidget {
  const _ModuleIconTile({
    required this.module,
    required this.onTap,
  });

  final CityModule module;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Column(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: module.color.withValues(alpha: 0.16),
              shape: BoxShape.circle,
              border: Border.all(color: module.color.withValues(alpha: 0.18)),
              boxShadow: [
                BoxShadow(
                  color: module.color.withValues(alpha: 0.10),
                  blurRadius: 14,
                  offset: const Offset(0, 7),
                ),
              ],
            ),
            child: Icon(module.icon, color: module.color, size: 24),
          ),
          const SizedBox(height: 8),
          Text(
            module.title,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: adaptiveText(context, _homeText),
              fontSize: 11.5,
              fontWeight: FontWeight.w900,
              height: 1.12,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Featured Card (first module) ─────────────────────────────────────────────

class _FeaturedCard extends StatelessWidget {
  const _FeaturedCard({required this.module, required this.onTap});

  final CityModule module;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Ink(
          height: 100,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF56B4F5), AppTheme.primary],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primary.withValues(alpha: 0.38),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Row(
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.22),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(
                    module.icon,
                    color: Colors.white,
                    size: 36,
                  ),
                ),
                const SizedBox(width: 18),
                Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        module.title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        module.subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.82),
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.22),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.arrow_forward_rounded,
                    color: Colors.white,
                    size: 20,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ── Regular Module Card ──────────────────────────────────────────────────────

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({required this.module, required this.onTap});

  final CityModule module;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _homeDarkBg,
      darkSurface: _homeDarkPanel,
      darkCard: _homeDarkCard,
      darkLine: _homeDarkLine,
      darkText: _homeText,
      darkMuted: _homeMuted,
    );
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Ink(
          decoration: BoxDecoration(
            color: colors.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: colors.line),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primaryDark.withValues(alpha: 0.08),
                blurRadius: 14,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 12),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(
                    color: module.color.withValues(alpha: 0.11),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(module.icon, color: module.color, size: 30),
                ),
                const SizedBox(height: 12),
                Text(
                  module.title,
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    height: 1.15,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ── City Hero Custom Painter ─────────────────────────────────────────────────

class _CityHeroPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final cloudPaint = Paint()..color = const Color(0xFFE7F3FF);
    final palePaint = Paint()..color = const Color(0xFFDCEEFF);
    final buildingPaint = Paint()..color = const Color(0xFFB9DCF7);
    final bluePaint = Paint()..color = AppTheme.primary;
    final darkPaint = Paint()..color = AppTheme.primaryDark;
    final groundPaint = Paint()..color = const Color(0xFFEAF5FF);

    canvas.drawRect(
      Rect.fromLTWH(0, size.height - 36, size.width, 36),
      groundPaint,
    );

    _cloud(canvas, cloudPaint, Offset(size.width * 0.1, 72), 0.8);
    _cloud(canvas, cloudPaint, Offset(size.width * 0.78, 55), 0.75);
    _cloud(canvas, palePaint, Offset(size.width * 0.58, 92), 0.55);

    final base = size.height - 36;
    for (var i = 0; i < 7; i++) {
      final w = 22.0 + (i % 3) * 7;
      final h = 42.0 + (i % 4) * 16;
      final x = 36.0 + i * (size.width - 80) / 7;
      canvas.drawRect(Rect.fromLTWH(x, base - h, w, h), buildingPaint);
      for (var row = 0; row < 3; row++) {
        for (var col = 0; col < 2; col++) {
          canvas.drawRect(
            Rect.fromLTWH(x + 5 + col * 9, base - h + 9 + row * 13, 5, 7),
            Paint()..color = Colors.white.withValues(alpha: 0.85),
          );
        }
      }
    }

    final housePath = Path()
      ..moveTo(size.width * 0.14, base)
      ..lineTo(size.width * 0.14, base - 38)
      ..lineTo(size.width * 0.29, base - 78)
      ..lineTo(size.width * 0.44, base - 38)
      ..lineTo(size.width * 0.44, base)
      ..close();
    canvas.drawPath(housePath, Paint()..color = const Color(0xFFA9DCFF));

    final roof = Path()
      ..moveTo(size.width * 0.1, base - 36)
      ..lineTo(size.width * 0.29, base - 88)
      ..lineTo(size.width * 0.48, base - 36)
      ..lineTo(size.width * 0.45, base - 27)
      ..lineTo(size.width * 0.29, base - 70)
      ..lineTo(size.width * 0.13, base - 27)
      ..close();
    canvas.drawPath(roof, darkPaint);

    canvas.drawRect(
      Rect.fromLTWH(size.width * 0.23, base - 30, 24, 30),
      bluePaint,
    );
    canvas.drawRect(
      Rect.fromLTWH(size.width * 0.34, base - 30, 25, 20),
      Paint()..color = Colors.white,
    );

    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(size.width * 0.64, base - 78, 78, 78),
        const Radius.circular(2),
      ),
      Paint()..color = const Color(0xFF82C6F2),
    );
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(size.width * 0.68, base - 94, 44, 18),
        const Radius.circular(2),
      ),
      bluePaint,
    );
    _windowGrid(canvas, size.width * 0.66, base - 66, 4, 3);

    _scooter(
      canvas,
      Offset(size.width * 0.52, base - 14),
      bluePaint,
      darkPaint,
    );
  }

  void _cloud(Canvas canvas, Paint paint, Offset origin, double scale) {
    canvas.drawCircle(origin, 14 * scale, paint);
    canvas.drawCircle(
      origin.translate(18 * scale, -7 * scale),
      19 * scale,
      paint,
    );
    canvas.drawCircle(
      origin.translate(38 * scale, 1 * scale),
      12 * scale,
      paint,
    );
    canvas.drawRect(
      Rect.fromLTWH(origin.dx - 10 * scale, origin.dy, 58 * scale, 12 * scale),
      paint,
    );
  }

  void _windowGrid(Canvas canvas, double x, double y, int rows, int cols) {
    final paint = Paint()..color = Colors.white.withValues(alpha: 0.85);
    for (var row = 0; row < rows; row++) {
      for (var col = 0; col < cols; col++) {
        canvas.drawRect(Rect.fromLTWH(x + col * 18, y + row * 15, 9, 9), paint);
      }
    }
  }

  void _scooter(
    Canvas canvas,
    Offset origin,
    Paint bluePaint,
    Paint darkPaint,
  ) {
    final tirePaint = Paint()
      ..color = const Color(0xFF24516F)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 5;
    canvas.drawCircle(origin.translate(-28, 0), 13, tirePaint);
    canvas.drawCircle(origin.translate(28, 0), 13, tirePaint);
    canvas.drawRRect(
      RRect.fromRectAndRadius(
        Rect.fromLTWH(origin.dx - 32, origin.dy - 25, 52, 18),
        const Radius.circular(8),
      ),
      bluePaint,
    );
    final riderPaint = Paint()
      ..color = darkPaint.color
      ..strokeWidth = 6
      ..strokeCap = StrokeCap.round;
    final handlePaint = Paint()
      ..color = darkPaint.color
      ..strokeWidth = 5
      ..strokeCap = StrokeCap.round;
    canvas.drawLine(
      origin.translate(16, -22),
      origin.translate(34, -44),
      handlePaint,
    );
    canvas.drawCircle(
      origin.translate(10, -52),
      8,
      Paint()..color = const Color(0xFFFFC783),
    );
    canvas.drawLine(
      origin.translate(9, -43),
      origin.translate(2, -22),
      riderPaint,
    );
    canvas.drawLine(
      origin.translate(1, -28),
      origin.translate(-17, -13),
      handlePaint,
    );
    canvas.drawLine(
      origin.translate(6, -25),
      origin.translate(24, -10),
      handlePaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
