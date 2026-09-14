import 'package:flutter/material.dart';

import '../../../core/firebase_service.dart';

import '../../../core/app_theme.dart';
import '../../../core/theme_mode_controller.dart';
import '../../auth/customer_firebase_auth.dart';
import '../../ecommerce/data/ecommerce_session_store.dart';
import '../../ecommerce/domain/ecommerce_models.dart';
import '../../ecommerce/presentation/ecommerce_order_history_screen.dart';
import '../../ecommerce/presentation/ecommerce_support_screen.dart';
import '../../ecommerce/presentation/ecommerce_wishlist_screen.dart';
import '../../real_estate/presentation/real_estate_home_screen.dart';
import '../../services/presentation/services_home_screen.dart';
import '../data/mart_api_client.dart';
import '../data/mart_locale_store.dart';
import '../data/mart_session_store.dart';
import '../domain/mart_i18n.dart';
import '../domain/mart_models.dart';
import 'mart_address_screen.dart';
import 'mart_cms_screen.dart';
import 'mart_order_history_screen.dart';
import 'mart_support_screen.dart';
import 'mart_wallet_screen.dart';
import 'mart_wishlist_screen.dart';

const _medicalApiBaseUrl =
    'https://snow-grouse-381496.hostingersite.com/api/v1/medical';

class MartAccountScreen extends StatefulWidget {
  const MartAccountScreen({super.key, this.showAppBar = true});

  final bool showAppBar;

  @override
  State<MartAccountScreen> createState() => _MartAccountScreenState();
}

class _MartAccountScreenState extends State<MartAccountScreen> {
  final _store = MartSessionStore();
  final _ecommerceStore = EcommerceSessionStore();
  final _localeStore = MartLocaleStore();
  final _api = MartApiClient();
  late Future<MartCustomerSession> _future;
  late Future<MartConfig> _configFuture;

  @override
  void initState() {
    super.initState();
    _future = _store.load();
    _configFuture = _api.fetchConfig();
    _syncLocale();
  }

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: MartLocaleController.locale,
      builder: (context, locale, _) => Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: widget.showAppBar
            ? AppBar(title: Text(MartI18n.text('my_account', locale)))
            : null,
        body: SafeArea(
          child: FutureBuilder<MartCustomerSession>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const AppSkeletonPage();
              }
              final session = snapshot.data ?? MartCustomerSession.guest();
              if (!session.isLoggedIn) {
                return _LoginForm(
                  onSaved: _saveSession,
                  configFuture: _configFuture,
                );
              }

              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _AccountHero(session: session),
                  const SizedBox(height: 16),
                  _AccountSection(
                    title: 'Activity',
                    children: [
                      _AccountAction(
                        icon: Icons.receipt_long_outlined,
                        title: 'Orders',
                        subtitle: 'Mart, E-Commerce and Medical orders',
                        onTap: () => _openOrderCenter(session),
                      ),
                      _AccountAction(
                        icon: Icons.calendar_month_outlined,
                        title: 'Bookings',
                        subtitle: 'Services, hotels and restaurant bookings',
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => const ServiceBookingsScreen(),
                          ),
                        ),
                      ),
                      _AccountAction(
                        icon: Icons.real_estate_agent_outlined,
                        title: 'Real Estate Activity',
                        subtitle: 'Site visits, inquiries and saved searches',
                        onTap: () => _openRealEstateCenter(session),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _AccountSection(
                    title: 'Money',
                    children: [
                      _AccountAction(
                        icon: Icons.account_balance_wallet_outlined,
                        title: 'Wallet',
                        subtitle: 'User-level balance, credits and withdrawals',
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => MartWalletScreen(session: session),
                          ),
                        ),
                      ),
                      _AccountAction(
                        icon: Icons.assignment_return_outlined,
                        title: 'Refund Center',
                        subtitle: 'All refund requests in one place',
                        onTap: () => _openRefundCenter(session),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _AccountSection(
                    title: 'Saved',
                    children: [
                      _AccountAction(
                        icon: Icons.location_on_outlined,
                        title: 'Addresses',
                        subtitle: 'Saved addresses and active zone',
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => MartAddressScreen(session: session),
                          ),
                        ),
                      ),
                      _AccountAction(
                        icon: Icons.favorite_border_rounded,
                        title: 'Saved Items',
                        subtitle: 'Wishlists, favorite properties and searches',
                        onTap: () => _openSavedCenter(session),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  FutureBuilder<MartConfig>(
                    future: _configFuture,
                    builder: (context, configSnapshot) {
                      if (configSnapshot.connectionState ==
                          ConnectionState.waiting) {
                        return const AppSkeletonBox(height: 74, radius: 22);
                      }
                      final config = configSnapshot.data;
                      final supported =
                          config?.supportedLocales ?? const ['en', 'hi'];
                      return Column(
                        children: [
                          _AccountSection(
                            title: 'Settings',
                            children: [
                              _LanguageAction(
                                title: MartI18n.text('language', locale),
                                value: locale,
                                supportedLocales: supported,
                                onChanged: _setLocale,
                              ),
                              _ThemeModeAction(
                                onChanged: (mode) => AppThemeModeController
                                    .instance
                                    .setMode(mode),
                              ),
                            ],
                          ),
                          if ((config?.cmsPages ?? const <MartCmsPage>[])
                              .isNotEmpty) ...[
                            const SizedBox(height: 14),
                            _AccountSection(
                              title: 'Information',
                              children: [
                                _AccountAction(
                                  icon: Icons.article_outlined,
                                  title: MartI18n.text('policies_info', locale),
                                  subtitle:
                                      'Terms, privacy and refund policies',
                                  onTap: () => Navigator.of(context).push(
                                    MaterialPageRoute<void>(
                                      builder: (_) => MartCmsScreen(
                                          pages: config!.cmsPages),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      );
                    },
                  ),
                  const SizedBox(height: 14),
                  _AccountSection(
                    title: 'Help',
                    children: [
                      _AccountAction(
                        icon: Icons.support_agent_rounded,
                        title: 'Support Center',
                        subtitle: 'Chat with support by module',
                        onTap: () => _openSupportCenter(session),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _AccountSection(
                    title: 'Session',
                    children: [
                      _AccountAction(
                        icon: Icons.logout_rounded,
                        title: MartI18n.text('logout', locale),
                        subtitle: 'Sign out from this device',
                        danger: true,
                        onTap: () async {
                          await CustomerFirebaseAuth.instance.signOut();
                          await _store.clear();
                          await _ecommerceStore.clear();
                          final guest = await _store.load();
                          await CityFirebaseService.instance.syncCustomer(
                            customerId: 0,
                            guestId: guest.guestId,
                          );
                          if (!mounted) return;
                          setState(() => _future = Future.value(guest));
                        },
                      ),
                    ],
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Future<void> _saveSession(MartCustomerSession session) async {
    await _store.save(session);
    await _ecommerceStore.save(EcommerceCustomerSession(
      customerId: session.customerId,
      guestId: session.guestId,
      token: session.token,
      name: session.name,
      phone: session.phone,
      email: session.email,
    ));
    await CityFirebaseService.instance.syncCustomer(
      customerId: session.customerId,
      guestId: session.guestId,
      bearerToken: session.token,
    );
    if (!mounted) return;
    setState(() => _future = Future.value(session));
  }

  Future<EcommerceCustomerSession> _ecommerceSession(
      MartCustomerSession fallback) async {
    final ecommerce = await _ecommerceStore.load();
    if (ecommerce.isLoggedIn) return ecommerce;
    return EcommerceCustomerSession(
      customerId: fallback.customerId,
      guestId: fallback.guestId,
      token: fallback.token,
      name: fallback.name,
      phone: fallback.phone,
      email: fallback.email,
    );
  }

  Future<String> _ecommerceGuestId(MartCustomerSession fallback) async {
    return (await _ecommerceSession(fallback)).guestId;
  }

  Future<void> _openOrderCenter(MartCustomerSession fallback) async {
    final guestId = await _ecommerceGuestId(fallback);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _OrderCenterScreen(
          martGuestId: fallback.guestId,
          ecommerceGuestId: guestId,
        ),
      ),
    );
  }

  Future<void> _openRefundCenter(MartCustomerSession fallback) async {
    final guestId = await _ecommerceGuestId(fallback);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _RefundCenterScreen(
          martGuestId: fallback.guestId,
          ecommerceGuestId: guestId,
        ),
      ),
    );
  }

  Future<void> _openSupportCenter(MartCustomerSession fallback) async {
    final session = await _ecommerceSession(fallback);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _SupportCenterScreen(
          martSession: fallback,
          ecommerceSession: session,
        ),
      ),
    );
  }

  Future<void> _openSavedCenter(MartCustomerSession fallback) async {
    final guestId = await _ecommerceGuestId(fallback);
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _SavedCenterScreen(
          martGuestId: fallback.guestId,
          ecommerceGuestId: guestId,
        ),
      ),
    );
  }

  Future<void> _openRealEstateCenter(MartCustomerSession session) async {
    if (!mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _RealEstateActivityScreen(guestId: session.guestId),
      ),
    );
  }

  Future<void> _syncLocale() async {
    final saved = await _localeStore.load();
    if (saved != null && saved.isNotEmpty) {
      MartLocaleController.locale.value = saved;
      return;
    }
    final config = await _api.fetchConfig();
    MartLocaleController.locale.value = config.appLocale;
  }

  Future<void> _setLocale(String locale) async {
    MartLocaleController.locale.value = locale;
    await _localeStore.save(locale);
  }
}

class _OrderCenterScreen extends StatelessWidget {
  const _OrderCenterScreen({
    required this.martGuestId,
    required this.ecommerceGuestId,
  });

  final String martGuestId;
  final String ecommerceGuestId;

  @override
  Widget build(BuildContext context) {
    return _AccountHubShell(
      title: 'Orders',
      subtitle: 'Track every delivery from one place.',
      icon: Icons.receipt_long_outlined,
      accent: AppTheme.primary,
      tabs: const [
        _HubTab(icon: Icons.storefront_outlined, label: 'Mart'),
        _HubTab(icon: Icons.shopping_bag_outlined, label: 'E-Commerce'),
        _HubTab(icon: Icons.medical_services_outlined, label: 'Medical'),
      ],
      children: [
        MartOrderHistoryScreen(
          guestId: martGuestId,
          showAppBar: false,
          title: 'Mart Orders',
        ),
        EcommerceOrderHistoryScreen(
          guestId: ecommerceGuestId,
          showAppBar: false,
          title: 'E-Commerce Orders',
          emptyTitle: 'No e-commerce orders yet.',
          emptyDetail:
              'E-Commerce purchases will appear here with their live status.',
        ),
        EcommerceOrderHistoryScreen(
          guestId: ecommerceGuestId,
          showAppBar: false,
          apiBaseUrl: _medicalApiBaseUrl,
          title: 'Medical Orders',
          emptyTitle: 'No medical orders yet.',
          emptyDetail:
              'Medicine orders will appear here with their live status.',
        ),
      ],
    );
  }
}

class _RefundCenterScreen extends StatelessWidget {
  const _RefundCenterScreen({
    required this.martGuestId,
    required this.ecommerceGuestId,
  });

  final String martGuestId;
  final String ecommerceGuestId;

  @override
  Widget build(BuildContext context) {
    return _AccountHubShell(
      title: 'Refund Center',
      subtitle: 'Follow refund status and credits clearly.',
      icon: Icons.assignment_return_outlined,
      accent: AppTheme.success,
      tabs: const [
        _HubTab(icon: Icons.storefront_outlined, label: 'Mart'),
        _HubTab(icon: Icons.shopping_bag_outlined, label: 'E-Commerce'),
        _HubTab(icon: Icons.medical_services_outlined, label: 'Medical'),
      ],
      children: [
        MartRefundsScreen(
          guestId: martGuestId,
          showAppBar: false,
          title: 'Mart Refunds',
        ),
        EcommerceRefundsScreen(
          guestId: ecommerceGuestId,
          showAppBar: false,
          title: 'E-Commerce Refunds',
          emptyTitle: 'No e-commerce refunds yet.',
          emptyDetail: 'E-Commerce refund requests will appear here.',
        ),
        EcommerceRefundsScreen(
          guestId: ecommerceGuestId,
          showAppBar: false,
          apiBaseUrl: _medicalApiBaseUrl,
          title: 'Medical Refunds',
          emptyTitle: 'No medical refunds yet.',
          emptyDetail: 'Medical refund requests will appear here.',
        ),
      ],
    );
  }
}

class _SupportCenterScreen extends StatelessWidget {
  const _SupportCenterScreen({
    required this.martSession,
    required this.ecommerceSession,
  });

  final MartCustomerSession martSession;
  final EcommerceCustomerSession ecommerceSession;

  @override
  Widget build(BuildContext context) {
    return _AccountHubShell(
      title: 'Support Center',
      subtitle: 'Choose the module and continue the right conversation.',
      icon: Icons.support_agent_rounded,
      accent: const Color(0xFF7C3AED),
      tabs: const [
        _HubTab(icon: Icons.storefront_outlined, label: 'Mart'),
        _HubTab(icon: Icons.shopping_bag_outlined, label: 'E-Commerce'),
        _HubTab(icon: Icons.medical_services_outlined, label: 'Medical'),
      ],
      children: [
        MartSupportScreen(
          session: martSession,
          showAppBar: false,
          title: 'Mart Support',
        ),
        EcommerceSupportScreen(
          session: ecommerceSession,
          showAppBar: false,
          title: 'E-Commerce Support',
        ),
        EcommerceSupportScreen(
          session: ecommerceSession,
          apiBaseUrl: _medicalApiBaseUrl,
          showAppBar: false,
          title: 'Medical Support',
        ),
      ],
    );
  }
}

class _SavedCenterScreen extends StatelessWidget {
  const _SavedCenterScreen({
    required this.martGuestId,
    required this.ecommerceGuestId,
  });

  final String martGuestId;
  final String ecommerceGuestId;

  @override
  Widget build(BuildContext context) {
    return _AccountHubShell(
      title: 'Saved Items',
      subtitle: 'Products, properties and searches you want to revisit.',
      icon: Icons.favorite_border_rounded,
      accent: AppTheme.danger,
      isScrollable: true,
      tabs: const [
        _HubTab(icon: Icons.storefront_outlined, label: 'Mart'),
        _HubTab(icon: Icons.shopping_bag_outlined, label: 'E-Commerce'),
        _HubTab(icon: Icons.apartment_rounded, label: 'Properties'),
        _HubTab(icon: Icons.bookmark_border_rounded, label: 'Searches'),
      ],
      children: [
        MartWishlistScreen(
          guestId: martGuestId,
          showAppBar: false,
        ),
        EcommerceWishlistScreen(
          guestId: ecommerceGuestId,
          showAppBar: false,
          title: 'E-Commerce Wishlist',
          emptyText: 'No e-commerce wishlist products yet.',
        ),
        RealEstateFavoritesScreen(
          guestId: martGuestId,
          showAppBar: false,
        ),
        RealEstateSavedSearchesScreen(
          guestId: martGuestId,
          showAppBar: false,
        ),
      ],
    );
  }
}

class _RealEstateActivityScreen extends StatelessWidget {
  const _RealEstateActivityScreen({required this.guestId});

  final String guestId;

  @override
  Widget build(BuildContext context) {
    return _AccountHubShell(
      title: 'Real Estate Activity',
      subtitle: 'Track property visits and conversations.',
      icon: Icons.real_estate_agent_outlined,
      accent: const Color(0xFF0F9F8F),
      tabs: const [
        _HubTab(icon: Icons.tour_outlined, label: 'Visits'),
        _HubTab(icon: Icons.chat_bubble_outline, label: 'Inquiries'),
      ],
      children: [
        RealEstateSiteVisitsScreen(
          guestId: guestId,
          showAppBar: false,
        ),
        RealEstateInquiriesScreen(
          guestId: guestId,
          showAppBar: false,
        ),
      ],
    );
  }
}

class _HubTab {
  const _HubTab({required this.icon, required this.label});

  final IconData icon;
  final String label;
}

class _AccountHubShell extends StatelessWidget {
  const _AccountHubShell({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.accent,
    required this.tabs,
    required this.children,
    this.isScrollable = false,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color accent;
  final List<_HubTab> tabs;
  final List<Widget> children;
  final bool isScrollable;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: tabs.length,
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(title: Text(title)),
        body: SafeArea(
          top: false,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
                child: Column(
                  children: [
                    _HubHeader(
                      title: title,
                      subtitle: subtitle,
                      icon: icon,
                      accent: accent,
                    ),
                    const SizedBox(height: 12),
                    _HubTabs(
                      tabs: tabs,
                      accent: accent,
                      isScrollable: isScrollable,
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      borderRadius: BorderRadius.circular(26),
                      border: Border.all(color: Theme.of(context).dividerColor),
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primary.withValues(alpha: .05),
                          blurRadius: 24,
                          offset: const Offset(0, 14),
                        ),
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(25),
                      child: TabBarView(children: children),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HubHeader extends StatelessWidget {
  const _HubHeader({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.accent,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            accent,
            Color.lerp(accent, AppTheme.primaryDark, .36) ?? accent,
          ],
        ),
        borderRadius: BorderRadius.circular(26),
        boxShadow: [
          BoxShadow(
            color: accent.withValues(alpha: .20),
            blurRadius: 24,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: .18),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: Colors.white.withValues(alpha: .2)),
            ),
            child: Icon(icon, color: Colors.white, size: 28),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .78),
                    fontSize: 12,
                    height: 1.25,
                    fontWeight: FontWeight.w700,
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

class _HubTabs extends StatelessWidget {
  const _HubTabs({
    required this.tabs,
    required this.accent,
    required this.isScrollable,
  });

  final List<_HubTab> tabs;
  final Color accent;
  final bool isScrollable;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 48,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: .04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: TabBar(
        isScrollable: isScrollable,
        tabAlignment: isScrollable ? TabAlignment.start : null,
        indicatorSize: TabBarIndicatorSize.tab,
        dividerColor: Colors.transparent,
        labelColor: Colors.white,
        unselectedLabelColor: Theme.of(context).colorScheme.onSurfaceVariant,
        labelStyle: const TextStyle(
          fontWeight: FontWeight.w900,
          fontSize: 12,
        ),
        unselectedLabelStyle: const TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
        indicator: BoxDecoration(
          color: accent,
          borderRadius: BorderRadius.circular(16),
        ),
        padding: const EdgeInsets.all(4),
        tabs: tabs
            .map(
              (tab) => Tab(
                height: 40,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(tab.icon, size: 17),
                    const SizedBox(width: 6),
                    Flexible(
                      child: Text(
                        tab.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _LanguageAction extends StatelessWidget {
  const _LanguageAction({
    required this.title,
    required this.value,
    required this.supportedLocales,
    required this.onChanged,
  });

  final String title;
  final String value;
  final List<String> supportedLocales;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AppTheme.primary.withValues(alpha: .1),
                borderRadius: BorderRadius.circular(14),
              ),
              child:
                  const Icon(Icons.language_rounded, color: AppTheme.primary),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 2),
                  const Text('App display language',
                      style: TextStyle(fontSize: 12)),
                ],
              ),
            ),
            DropdownButton<String>(
              value: value,
              underline: const SizedBox.shrink(),
              items: supportedLocales
                  .map((locale) => DropdownMenuItem(
                        value: locale,
                        child: Text(locale == 'hi'
                            ? MartI18n.text('hindi', value)
                            : MartI18n.text('english', value)),
                      ))
                  .toList(),
              onChanged: (newValue) {
                if (newValue != null) onChanged(newValue);
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _ThemeModeAction extends StatelessWidget {
  const _ThemeModeAction({
    required this.onChanged,
  });

  final ValueChanged<ThemeMode> onChanged;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<ThemeMode>(
      valueListenable: AppThemeModeController.instance.themeMode,
      builder: (context, current, _) {
        return Material(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: .1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(
                    Icons.dark_mode_outlined,
                    color: AppTheme.primary,
                  ),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Appearance',
                        style: TextStyle(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Choose light, dark or system mode',
                        style: TextStyle(fontSize: 12),
                      ),
                    ],
                  ),
                ),
                DropdownButton<ThemeMode>(
                  value: current,
                  underline: const SizedBox.shrink(),
                  items: const [
                    DropdownMenuItem(
                      value: ThemeMode.system,
                      child: Text('System'),
                    ),
                    DropdownMenuItem(
                      value: ThemeMode.light,
                      child: Text('Light'),
                    ),
                    DropdownMenuItem(
                      value: ThemeMode.dark,
                      child: Text('Dark'),
                    ),
                  ],
                  onChanged: (newValue) {
                    if (newValue != null) onChanged(newValue);
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _AccountHero extends StatelessWidget {
  const _AccountHero({required this.session});

  final MartCustomerSession session;

  @override
  Widget build(BuildContext context) {
    final initial = session.name.trim().isEmpty
        ? 'C'
        : session.name.trim().characters.first.toUpperCase();
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.primaryDark, AppTheme.primaryMid, AppTheme.primary],
        ),
        borderRadius: BorderRadius.circular(26),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .22),
            blurRadius: 26,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              border: Border.all(
                color: Colors.white.withValues(alpha: .7),
                width: 3,
              ),
            ),
            alignment: Alignment.center,
            child: Text(
              initial,
              style: const TextStyle(
                color: AppTheme.primary,
                fontSize: 24,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  session.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  session.phone,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .78),
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (session.email.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    session.email,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: .68),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AccountSection extends StatelessWidget {
  const _AccountSection({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(2, 0, 2, 8),
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        Container(
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
          child: Column(
            children: [
              for (var index = 0; index < children.length; index++) ...[
                children[index],
                if (index != children.length - 1)
                  Divider(height: 1, color: Theme.of(context).dividerColor),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _LoginForm extends StatefulWidget {
  const _LoginForm({
    required this.onSaved,
    required this.configFuture,
  });

  final ValueChanged<MartCustomerSession> onSaved;
  final Future<MartConfig> configFuture;

  @override
  State<_LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<_LoginForm> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController(text: 'Jitendra');
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _registerMode = false;
  bool _saving = false;
  bool _passwordVisible = false;
  late final Future<FirebaseAuthAvailability> _firebaseAvailability;

  @override
  void initState() {
    super.initState();
    _firebaseAvailability = CustomerFirebaseAuth.instance.availability();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 24),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  AppTheme.primaryDark,
                  AppTheme.primaryMid,
                  AppTheme.primary
                ],
              ),
              borderRadius: BorderRadius.circular(26),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: .22),
                  blurRadius: 28,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 54,
                  height: 54,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .16),
                    borderRadius: BorderRadius.circular(18),
                    border:
                        Border.all(color: Colors.white.withValues(alpha: .2)),
                  ),
                  child: const Icon(Icons.person_outline_rounded,
                      color: Colors.white, size: 30),
                ),
                const SizedBox(height: 18),
                Text(
                  _registerMode ? 'Create your account' : 'Welcome back',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 26,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _registerMode
                      ? 'Use one City Solutions account across all modules.'
                      : 'Login to track orders, bookings, wallet and support.',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .78),
                    height: 1.35,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          if (_registerMode) ...[
            _Field(
                controller: _nameController,
                label: 'Name',
                icon: Icons.person_outline),
            const SizedBox(height: 12),
          ],
          _Field(
              controller: _phoneController,
              label: 'Phone',
              icon: Icons.phone_outlined,
              keyboardType: TextInputType.phone),
          const SizedBox(height: 12),
          if (_registerMode) ...[
            _Field(
                controller: _emailController,
                label: 'Email optional',
                icon: Icons.email_outlined,
                required: false,
                keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 12),
          ],
          _Field(
            controller: _passwordController,
            label: 'Password',
            icon: Icons.lock_outline,
            obscureText: !_passwordVisible,
            minLength: 6,
            suffixIcon: IconButton(
              tooltip: _passwordVisible ? 'Hide password' : 'Show password',
              onPressed: () =>
                  setState(() => _passwordVisible = !_passwordVisible),
              icon: Icon(_passwordVisible
                  ? Icons.visibility_off_outlined
                  : Icons.visibility_outlined),
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Use your registered 10-digit phone number. Password must be at least 6 characters.',
            style: TextStyle(
              fontSize: 12,
              height: 1.35,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 18),
          FutureBuilder<FirebaseAuthAvailability>(
            future: _firebaseAvailability,
            builder: (context, snapshot) {
              final auth = snapshot.data ?? FirebaseAuthAvailability.disabled;
              if (!auth.enabled ||
                  (!auth.phoneEnabled && !auth.googleEnabled)) {
                return const SizedBox.shrink();
              }
              return Column(
                children: [
                  if (auth.phoneEnabled)
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: FilledButton.icon(
                        onPressed: _saving ? null : _signInWithOtp,
                        icon: const Icon(Icons.sms_outlined),
                        label: const Text('Continue with phone OTP'),
                      ),
                    ),
                  if (auth.phoneEnabled && auth.googleEnabled)
                    const SizedBox(height: 10),
                  if (auth.googleEnabled)
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: OutlinedButton.icon(
                        onPressed: _saving ? null : _signInWithGoogle,
                        icon: const Icon(Icons.g_mobiledata_rounded, size: 30),
                        label: const Text('Continue with Google'),
                      ),
                    ),
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 16),
                    child: Row(
                      children: [
                        Expanded(child: Divider()),
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 12),
                          child: Text('or use password'),
                        ),
                        Expanded(child: Divider()),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),
          SizedBox(
            height: 52,
            child: FilledButton(
              onPressed: _saving ? null : _submit,
              child: Text(_saving
                  ? 'Saving...'
                  : _registerMode
                      ? 'Create Account'
                      : 'Login'),
            ),
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: _saving
                ? null
                : () => setState(() => _registerMode = !_registerMode),
            child: Text(_registerMode
                ? 'Already have an account? Login'
                : 'New customer? Create account'),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: _openPolicies,
            icon: const Icon(Icons.verified_user_outlined),
            label: const Text('Legal & Privacy'),
          ),
        ],
      ),
    );
  }

  Future<void> _openPolicies() async {
    try {
      final config = await widget.configFuture;
      if (!mounted) return;
      if (config.cmsPages.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Legal information is not available.')),
        );
        return;
      }
      Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => MartCmsScreen(pages: config.cmsPages),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = MartApiClient();
      final session = _registerMode
          ? await api.registerCustomer(
              name: _nameController.text.trim(),
              phone: _phoneController.text.trim(),
              email: _emailController.text.trim(),
              password: _passwordController.text,
            )
          : await api.loginCustomer(
              phone: _phoneController.text.trim(),
              password: _passwordController.text,
            );
      widget.onSaved(session);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _signInWithGoogle() async {
    await _runFirebaseSignIn(
      () => CustomerFirebaseAuth.instance.signInWithGoogle(),
    );
  }

  Future<void> _signInWithOtp() async {
    final phone = _phoneController.text.trim();
    if (phone.isEmpty) {
      _showError('Enter your phone number first.');
      return;
    }
    await _runFirebaseSignIn(
      () => CustomerFirebaseAuth.instance.signInWithPhone(
        phoneNumber: phone,
        requestSmsCode: _requestSmsCode,
      ),
    );
  }

  Future<void> _runFirebaseSignIn(
    Future<MartCustomerSession> Function() action,
  ) async {
    setState(() => _saving = true);
    try {
      widget.onSaved(await action());
    } catch (error) {
      if (mounted) _showError(AppErrorState.userMessage(error));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<String?> _requestSmsCode() async {
    if (!mounted) return null;
    final controller = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Enter OTP'),
        content: TextField(
          controller: controller,
          autofocus: true,
          keyboardType: TextInputType.number,
          maxLength: 6,
          decoration: const InputDecoration(
            labelText: '6-digit SMS code',
            prefixIcon: Icon(Icons.password_rounded),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, controller.text),
            child: const Text('Verify'),
          ),
        ],
      ),
    );
    controller.dispose();
    return code;
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}

class _AccountAction extends StatelessWidget {
  const _AccountAction({
    required this.icon,
    required this.title,
    required this.onTap,
    this.subtitle,
    this.danger = false,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final bool danger;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = danger ? AppTheme.danger : AppTheme.primary;
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: .1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: color),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title,
                        style: TextStyle(
                            color: danger
                                ? AppTheme.danger
                                : Theme.of(context).colorScheme.onSurface,
                            fontWeight: FontWeight.w900)),
                    if (subtitle != null && subtitle!.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(subtitle!, style: const TextStyle(fontSize: 12)),
                    ],
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
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.required = true,
    this.keyboardType,
    this.obscureText = false,
    this.minLength,
    this.suffixIcon,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool required;
  final TextInputType? keyboardType;
  final bool obscureText;
  final int? minLength;
  final Widget? suffixIcon;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      obscureText: obscureText,
      validator: required
          ? (value) {
              if (value == null || value.trim().isEmpty) return 'Required';
              if (keyboardType == TextInputType.phone) {
                final digits = value.replaceAll(RegExp(r'\D'), '');
                if (digits.length < 10) return 'Enter a valid phone number';
              }
              if (minLength != null && value.length < minLength!) {
                return 'Minimum $minLength characters';
              }
              return null;
            }
          : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: Theme.of(context).colorScheme.surfaceContainerHighest,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none),
      ),
    );
  }
}
