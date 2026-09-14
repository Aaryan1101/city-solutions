import 'package:flutter/material.dart';

import '../../core/app_theme.dart';
import '../ecommerce/presentation/ecommerce_home_screen.dart';
import '../hotel/presentation/hotel_home_screen.dart';
import '../mart/presentation/mart_home_screen.dart';
import '../medical/presentation/medical_home_screen.dart';
import '../services/presentation/services_home_screen.dart';

class OffersScreen extends StatelessWidget {
  const OffersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 110),
        children: [
          const Text(
            'Offers',
            style: TextStyle(
              fontSize: 26,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Open a module to use live coupons, banners and deals from admin.',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 18),
          _OfferTile(
            title: 'Fresh Mart Deals',
            subtitle: 'Groceries, daily essentials and quick delivery offers',
            icon: Icons.eco_outlined,
            colors: const [Color(0xFF1FB083), Color(0xFF0E8F66)],
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const MartHomeScreen()),
            ),
          ),
          _OfferTile(
            title: 'Shopping Offers',
            subtitle: 'Electronics, products, brands and vendor deals',
            icon: Icons.shopping_bag_outlined,
            colors: const [AppTheme.primaryMid, AppTheme.primary],
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const EcommerceHomeScreen(),
              ),
            ),
          ),
          _OfferTile(
            title: 'Medicine Offers',
            subtitle: 'Pharmacy products and medical delivery discounts',
            icon: Icons.medication_liquid_outlined,
            colors: const [Color(0xFFE94C3D), Color(0xFFFF5470)],
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const MedicalHomeScreen(),
              ),
            ),
          ),
          _OfferTile(
            title: 'Service Packages',
            subtitle: 'Cleaning, repair and home-service offers',
            icon: Icons.plumbing_outlined,
            colors: const [Color(0xFFFFB648), Color(0xFFE8912A)],
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(
                builder: (_) => const ServicesHomeScreen(),
              ),
            ),
          ),
          _OfferTile(
            title: 'Hotel Stays',
            subtitle: 'Room deals, family stays and business bookings',
            icon: Icons.hotel_outlined,
            colors: const [Color(0xFF149E8A), Color(0xFF2FADA0)],
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute<void>(builder: (_) => const HotelHomeScreen()),
            ),
          ),
        ],
      ),
    );
  }
}

class _OfferTile extends StatelessWidget {
  const _OfferTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.colors,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final List<Color> colors;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(22),
          child: Ink(
            height: 116,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: colors,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(22),
              boxShadow: [
                BoxShadow(
                  color: colors.last.withValues(alpha: 0.22),
                  blurRadius: 18,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: Stack(
              children: [
                Positioned(
                  right: -12,
                  bottom: -16,
                  child: Icon(
                    icon,
                    size: 86,
                    color: Colors.white.withValues(alpha: 0.16),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(18),
                  child: Row(
                    children: [
                      Container(
                        width: 54,
                        height: 54,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Icon(icon, color: Colors.white, size: 28),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            const SizedBox(height: 5),
                            Text(
                              subtitle,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                color: Colors.white.withValues(alpha: 0.84),
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                height: 1.25,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.chevron_right_rounded,
                        color: Colors.white,
                        size: 26,
                      ),
                    ],
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
