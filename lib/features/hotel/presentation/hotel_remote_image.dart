import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/hotel_api_client.dart';

class HotelRemoteImage extends StatelessWidget {
  const HotelRemoteImage({
    super.key,
    required this.path,
    this.width,
    this.height,
    this.borderRadius = 8,
  });

  final String path;
  final double? width;
  final double? height;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    final imageUrl = _url(path);
    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: imageUrl == null
          ? _fallback()
          : Image.network(
              imageUrl,
              width: width,
              height: height,
              fit: BoxFit.cover,
              loadingBuilder: (context, child, progress) {
                if (progress == null) return child;
                return AppDelayedSkeletonBox(
                  width: width,
                  height: height ?? 120,
                  radius: borderRadius,
                );
              },
              errorBuilder: (_, __, ___) => _fallback(),
            ),
    );
  }

  Widget _fallback() {
    return Container(
      width: width,
      height: height,
      color: const Color(0xFFEAF4F6),
      child: const Icon(Icons.hotel_outlined, color: Color(0xFF0B7285)),
    );
  }

  String? _url(String value) {
    if (value.trim().isEmpty) return null;
    if (value.startsWith('http')) return value;
    final origin = HotelApiConfig.baseUrl.split('/api/').first;
    return '$origin${value.startsWith('/') ? value : '/$value'}';
  }
}
