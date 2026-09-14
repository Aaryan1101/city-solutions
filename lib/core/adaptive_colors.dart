import 'package:flutter/material.dart';

import 'app_theme.dart';

bool appIsDark(BuildContext context) =>
    Theme.of(context).brightness == Brightness.dark;

Color adaptiveColor(
  BuildContext context, {
  required Color light,
  required Color dark,
}) {
  return appIsDark(context) ? dark : light;
}

Color adaptiveScaffold(BuildContext context, Color dark) {
  return adaptiveColor(context, light: AppTheme.surface, dark: dark);
}

Color adaptiveSurface(BuildContext context, Color dark) {
  return adaptiveColor(context, light: Colors.white, dark: dark);
}

Color adaptiveText(BuildContext context, Color dark) {
  return adaptiveColor(context, light: AppTheme.ink, dark: dark);
}

Color adaptiveMuted(BuildContext context, Color dark) {
  return adaptiveColor(context, light: AppTheme.muted, dark: dark);
}

Color adaptiveLine(BuildContext context, Color dark) {
  return adaptiveColor(context, light: AppTheme.line, dark: dark);
}

Color adaptiveModuleHeaderTint(
  BuildContext context, {
  required Color accent,
  required Color darkBase,
}) {
  final dark = appIsDark(context);
  return Color.alphaBlend(
    accent.withValues(alpha: dark ? 0.16 : 0.10),
    dark ? darkBase : Colors.white,
  );
}

class AdaptiveModuleHeaderBand extends StatelessWidget {
  const AdaptiveModuleHeaderBand({
    super.key,
    required this.accent,
    required this.darkBase,
    required this.child,
    this.margin = EdgeInsets.zero,
    this.padding = EdgeInsets.zero,
    this.bottomRadius = 24,
  });

  final Color accent;
  final Color darkBase;
  final Widget child;
  final EdgeInsetsGeometry margin;
  final EdgeInsetsGeometry padding;
  final double bottomRadius;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      padding: padding,
      decoration: BoxDecoration(
        color: adaptiveModuleHeaderTint(
          context,
          accent: accent,
          darkBase: darkBase,
        ),
        borderRadius: BorderRadius.vertical(
          bottom: Radius.circular(bottomRadius),
        ),
      ),
      child: child,
    );
  }
}

class AdaptiveModuleColors {
  const AdaptiveModuleColors({
    required this.bg,
    required this.surface,
    required this.card,
    required this.line,
    required this.text,
    required this.muted,
  });

  final Color bg;
  final Color surface;
  final Color card;
  final Color line;
  final Color text;
  final Color muted;
}

AdaptiveModuleColors adaptiveModuleColors(
  BuildContext context, {
  required Color darkBg,
  required Color darkSurface,
  required Color darkCard,
  required Color darkLine,
  required Color darkText,
  required Color darkMuted,
}) {
  return AdaptiveModuleColors(
    bg: adaptiveColor(context, light: AppTheme.surface, dark: darkBg),
    surface: adaptiveColor(context, light: Colors.white, dark: darkSurface),
    card: adaptiveColor(context, light: Colors.white, dark: darkCard),
    line: adaptiveColor(context, light: AppTheme.line, dark: darkLine),
    text: adaptiveColor(context, light: AppTheme.ink, dark: darkText),
    muted: adaptiveColor(context, light: AppTheme.muted, dark: darkMuted),
  );
}
