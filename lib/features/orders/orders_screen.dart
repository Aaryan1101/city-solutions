import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../ecommerce/data/ecommerce_session_store.dart';
import '../ecommerce/domain/ecommerce_models.dart';
import '../ecommerce/presentation/ecommerce_order_history_screen.dart';
import '../mart/data/mart_session_store.dart';
import '../mart/domain/mart_models.dart';
import '../mart/presentation/mart_order_history_screen.dart';

class OrdersScreen extends StatelessWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<_OrdersSessions>(
      future: _loadSessions(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const AppSkeletonPage();
        }

        final sessions = snapshot.data ??
            _OrdersSessions(
              mart: MartCustomerSession.guest(),
              ecommerce: EcommerceCustomerSession.guest(),
            );
        return DefaultTabController(
          length: 3,
          child: SafeArea(
            bottom: false,
            child: Column(
              children: [
                const _ActivityHeader(
                  title: 'Orders',
                  subtitle:
                      'Track deliveries from Mart, E-Commerce and Medical',
                  icon: Icons.receipt_long_outlined,
                ),
                Container(
                  margin: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: Theme.of(context).dividerColor),
                    boxShadow: [
                      BoxShadow(
                        color: AppTheme.primary.withValues(alpha: .07),
                        blurRadius: 18,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: TabBar(
                    dividerColor: Colors.transparent,
                    indicatorSize: TabBarIndicatorSize.tab,
                    indicator: BoxDecoration(
                      color: AppTheme.primary,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    labelColor: Colors.white,
                    unselectedLabelColor:
                        Theme.of(context).colorScheme.onSurfaceVariant,
                    labelStyle: const TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 12,
                    ),
                    unselectedLabelStyle: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                    tabs: const [
                      Tab(text: 'Mart'),
                      Tab(text: 'E-Com'),
                      Tab(text: 'Medical'),
                    ],
                  ),
                ),
                Expanded(
                  child: TabBarView(
                    children: [
                      if (sessions.mart.isLoggedIn)
                        MartOrderHistoryScreen(
                          guestId: sessions.mart.guestId,
                          showAppBar: false,
                          title: 'Mart Orders',
                        )
                      else
                        const _SignedOutActivityState(
                          title: 'Sign in to view mart orders.',
                          detail:
                              'Order history is private and only loads after account login.',
                        ),
                      if (sessions.ecommerce.isLoggedIn)
                        EcommerceOrderHistoryScreen(
                          guestId: sessions.ecommerce.guestId,
                          showAppBar: false,
                          title: 'E-Commerce Orders',
                          emptyTitle: 'No e-commerce orders yet.',
                          emptyDetail:
                              'E-Commerce purchases will appear here with their live status.',
                        )
                      else
                        const _SignedOutActivityState(
                          title: 'Sign in to view e-commerce orders.',
                          detail:
                              'Purchases are linked to your account for privacy.',
                        ),
                      if (sessions.ecommerce.isLoggedIn)
                        EcommerceOrderHistoryScreen(
                          guestId: sessions.ecommerce.guestId,
                          showAppBar: false,
                          apiBaseUrl:
                              'https://snow-grouse-381496.hostingersite.com/api/v1/medical',
                          title: 'Medical Orders',
                          emptyTitle: 'No medical orders yet.',
                          emptyDetail:
                              'Medicine orders will appear here with their live status.',
                        )
                      else
                        const _SignedOutActivityState(
                          title: 'Sign in to view medical orders.',
                          detail:
                              'Medical order records require an account session.',
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<_OrdersSessions> _loadSessions() async {
    final mart = await MartSessionStore().load();
    final ecommerce = await EcommerceSessionStore().load();
    return _OrdersSessions(mart: mart, ecommerce: ecommerce);
  }
}

class _ActivityHeader extends StatelessWidget {
  const _ActivityHeader({
    required this.title,
    required this.subtitle,
    required this.icon,
  });

  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 14),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppTheme.primaryDark, AppTheme.primaryMid, AppTheme.primary],
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .22),
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
              color: Colors.white.withValues(alpha: .16),
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
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .78),
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

class _OrdersSessions {
  const _OrdersSessions({required this.mart, required this.ecommerce});

  final MartCustomerSession mart;
  final EcommerceCustomerSession ecommerce;
}

class _SignedOutActivityState extends StatelessWidget {
  const _SignedOutActivityState({
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
          width: double.infinity,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: Theme.of(context).dividerColor),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primary.withValues(alpha: .05),
                blurRadius: 24,
                offset: const Offset(0, 14),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 74,
                height: 74,
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: .1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.lock_outline_rounded,
                  color: AppTheme.primary,
                  size: 34,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                detail,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  height: 1.35,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'Open Account tab to login or create an account.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppTheme.primary,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
