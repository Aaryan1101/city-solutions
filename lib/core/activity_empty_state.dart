import 'package:flutter/material.dart';

import 'app_theme.dart';

class AppActivityEmptyState extends StatelessWidget {
  const AppActivityEmptyState({
    super.key,
    required this.icon,
    required this.title,
    required this.detail,
    this.accent = AppTheme.primary,
    this.action,
    this.padding = const EdgeInsets.all(28),
  });

  final IconData icon;
  final String title;
  final String detail;
  final Color accent;
  final Widget? action;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = theme.colorScheme;
    final isDark = theme.brightness == Brightness.dark;
    return Center(
      child: Padding(
        padding: padding,
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: colors.surface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: theme.dividerColor),
            boxShadow: [
              BoxShadow(
                color: accent.withValues(alpha: isDark ? .025 : .055),
                blurRadius: isDark ? 12 : 24,
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
                  color: accent.withValues(alpha: .09),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 36, color: accent),
              ),
              const SizedBox(height: 16),
              Text(
                title,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: colors.onSurface,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                detail,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: colors.onSurfaceVariant,
                  height: 1.35,
                  fontWeight: FontWeight.w700,
                ),
              ),
              if (action != null) ...[
                const SizedBox(height: 18),
                action!,
              ],
            ],
          ),
        ),
      ),
    );
  }
}
