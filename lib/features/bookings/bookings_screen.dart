import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../hotel/presentation/hotel_home_screen.dart';
import '../mart/data/mart_session_store.dart';
import '../mart/domain/mart_models.dart';
import '../real_estate/presentation/real_estate_home_screen.dart';
import '../restaurant/presentation/restaurant_home_screen.dart';
import '../services/presentation/services_home_screen.dart';

class BookingsScreen extends StatelessWidget {
  const BookingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<MartCustomerSession>(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const AppSkeletonPage();
        }
        final session = snapshot.data ?? MartCustomerSession.guest();
        final signedIn = session.isLoggedIn;
        return DefaultTabController(
          length: 4,
          child: SafeArea(
            bottom: false,
            child: Column(
              children: [
                const _BookingsHeader(),
                const _BookingsTabs(),
                Expanded(
                  child: TabBarView(
                    children: [
                      if (signedIn)
                        const ServiceBookingsScreen(showAppBar: false)
                      else
                        const _SignedOutBookingState(
                          title: 'Sign in to view service bookings.',
                          detail:
                              'Appointments are private and load only after account login.',
                        ),
                      if (signedIn)
                        const RestaurantBookingsScreen(showAppBar: false)
                      else
                        const _SignedOutBookingState(
                          title: 'Sign in to view restaurant bookings.',
                          detail:
                              'Reservations and waitlist requests are linked to your account.',
                        ),
                      if (signedIn)
                        const HotelBookingsScreen(showAppBar: false)
                      else
                        const _SignedOutBookingState(
                          title: 'Sign in to view hotel bookings.',
                          detail:
                              'Hotel stays are linked to your account for privacy.',
                        ),
                      if (signedIn)
                        const _RealEstateVisitsTab()
                      else
                        const _SignedOutBookingState(
                          title: 'Sign in to view site visits.',
                          detail:
                              'Real estate visit requests appear after account login.',
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
}

class _SignedOutBookingState extends StatelessWidget {
  const _SignedOutBookingState({
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
                color: const Color(0xFF0D9488).withValues(alpha: .06),
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
                  color: const Color(0xFF0D9488).withValues(alpha: .1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.lock_outline_rounded,
                  color: Color(0xFF0D9488),
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

class _BookingsHeader extends StatelessWidget {
  const _BookingsHeader();

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
          colors: [Color(0xFF0B4F6C), Color(0xFF0D9488), Color(0xFFF59E0B)],
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0D9488).withValues(alpha: .18),
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
            child: const Icon(Icons.event_available_outlined,
                color: Colors.white, size: 28),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Bookings',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Manage service appointments, table reservations, hotel stays and site visits',
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

class _BookingsTabs extends StatelessWidget {
  const _BookingsTabs();

  @override
  Widget build(BuildContext context) {
    return Container(
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
        unselectedLabelColor: Theme.of(context).colorScheme.onSurfaceVariant,
        labelStyle: const TextStyle(fontWeight: FontWeight.w900),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w800),
        tabs: const [
          Tab(text: 'Services'),
          Tab(text: 'Dining'),
          Tab(text: 'Hotels'),
          Tab(text: 'Visits'),
        ],
      ),
    );
  }
}

class _RealEstateVisitsTab extends StatelessWidget {
  const _RealEstateVisitsTab();

  @override
  Widget build(BuildContext context) {
    return FutureBuilder(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const AppSkeletonPage();
        }
        final session = snapshot.data ?? MartCustomerSession.guest();
        return RealEstateSiteVisitsScreen(
          guestId: session.guestId,
          showAppBar: false,
        );
      },
    );
  }
}
