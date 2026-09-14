import 'package:flutter/material.dart';

import 'app_assets.dart';

class AppBrandLogo extends StatelessWidget {
  const AppBrandLogo({
    super.key,
    required this.size,
    this.radius = 22,
    this.padding = 4,
  });

  final double size;
  final double radius;
  final double padding;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    // The supplied JPEG has a white canvas, so present it as a deliberate
    // brand tile instead of allowing its square edge to appear accidental.
    return Container(
      width: size,
      height: size,
      padding: EdgeInsets.all(padding),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(
          color:
              isDark ? Colors.white.withValues(alpha: .18) : theme.dividerColor,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? .22 : .07),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Image.asset(AppAssets.logo, fit: BoxFit.cover),
    );
  }
}
