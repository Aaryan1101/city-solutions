import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';

class EcommerceRemoteImage extends StatelessWidget {
  const EcommerceRemoteImage({
    super.key,
    required this.url,
    required this.icon,
    this.fit = BoxFit.contain,
    this.iconSize = 40,
  });

  final String? url;
  final IconData icon;
  final BoxFit fit;
  final double iconSize;

  @override
  Widget build(BuildContext context) {
    final imageUrl = url;
    if (imageUrl == null || imageUrl.trim().isEmpty) {
      return _fallback();
    }

    return Image.network(
      imageUrl,
      fit: fit,
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return LayoutBuilder(
          builder: (context, constraints) {
            final height =
                constraints.maxHeight.isFinite ? constraints.maxHeight : 96.0;
            final width =
                constraints.maxWidth.isFinite ? constraints.maxWidth : null;
            return AppDelayedSkeletonBox(
              width: width,
              height: height,
              radius: 14,
            );
          },
        );
      },
      errorBuilder: (_, __, ___) => _fallback(),
    );
  }

  Widget _fallback() {
    return Center(
      child: Icon(icon, size: iconSize, color: const Color(0xFF6C5CE7)),
    );
  }
}
