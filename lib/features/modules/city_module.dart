import 'package:flutter/material.dart';

enum ModuleStatus { ready }

class CityModule {
  const CityModule({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.status,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final ModuleStatus status;
}

const cityModules = <CityModule>[
  CityModule(
    title: 'E-Commerce',
    subtitle: 'Products, carts and online orders',
    icon: Icons.shopping_bag_outlined,
    color: Color(0xFF2F6BF0),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Mart',
    subtitle: 'Daily essentials and quick grocery',
    icon: Icons.storefront_outlined,
    color: Color(0xFF20A66A),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Services',
    subtitle: 'Home, repair and professional bookings',
    icon: Icons.plumbing_outlined,
    color: Color(0xFFFFB23F),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Real Estate',
    subtitle: 'Buy, rent and discover properties',
    icon: Icons.real_estate_agent_outlined,
    color: Color(0xFFD15F3E),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Hotel',
    subtitle: 'Rooms, stays and reservations',
    icon: Icons.hotel_outlined,
    color: Color(0xFF149E8A),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Medical',
    subtitle: 'Medicine delivery and pharmacy orders',
    icon: Icons.medical_services_outlined,
    color: Color(0xFFE94C3D),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Restaurant',
    subtitle: 'Reserve tables at nearby restaurants',
    icon: Icons.restaurant_outlined,
    color: Color(0xFFE0444E),
    status: ModuleStatus.ready,
  ),
  CityModule(
    title: 'Taxi',
    subtitle: 'Book city cabs and local rides',
    icon: Icons.local_taxi_outlined,
    color: Color(0xFF7C3AED),
    status: ModuleStatus.ready,
  ),
];
