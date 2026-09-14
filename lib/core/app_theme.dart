import 'package:flutter/material.dart';

class AppTheme {
  static const Color primary = Color(0xFF2F6BF0);
  static const Color primaryDark = Color(0xFF0A2470);
  static const Color primaryMid = Color(0xFF1C4BD8);
  static const Color ink = Color(0xFF1A2942);
  static const Color muted = Color(0xFF75839A);
  static const Color surface = Color(0xFFF6F8FC);
  static const Color line = Color(0xFFE5EAF3);
  static const Color success = Color(0xFF1FA864);
  static const Color warning = Color(0xFFFFB648);
  static const Color danger = Color(0xFFFF5470);
  static const Color darkBg = Color(0xFF08090B);
  static const Color darkSurface = Color(0xFF121417);
  static const Color darkCard = Color(0xFF181B20);
  static const Color darkLine = Color(0xFF2A2F38);
  static const Color darkText = Color(0xFFF8FAFC);
  static const Color darkMuted = Color(0xFFA3ABB8);

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      brightness: Brightness.light,
      primary: primary,
      secondary: primaryDark,
      surface: Colors.white,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: surface,
      cardColor: Colors.white,
      dividerColor: line,
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
      ),
      dialogTheme: const DialogThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
      ),
      popupMenuTheme: const PopupMenuThemeData(
        color: Colors.white,
        surfaceTintColor: Colors.transparent,
      ),
      fontFamily: 'Roboto',
      appBarTheme: const AppBarTheme(
        backgroundColor: surface,
        foregroundColor: ink,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: ink,
          fontSize: 20,
          fontWeight: FontWeight.w900,
        ),
      ),
      textTheme: const TextTheme(
        headlineMedium: TextStyle(fontWeight: FontWeight.w900, color: ink),
        titleLarge: TextStyle(fontWeight: FontWeight.w900, color: ink),
        titleMedium: TextStyle(fontWeight: FontWeight.w800, color: ink),
        bodyLarge: TextStyle(color: ink),
        bodyMedium: TextStyle(color: muted),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w900),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: primary, width: 1.4),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: ink,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        contentTextStyle: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w700,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: Colors.white,
        selectedItemColor: primary,
        unselectedItemColor: ink,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
        unselectedLabelStyle: TextStyle(
          fontWeight: FontWeight.w500,
          fontSize: 12,
        ),
      ),
    );
  }

  static ThemeData dark() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      brightness: Brightness.dark,
      primary: primary,
      secondary: const Color(0xFFB9FF4D),
      surface: darkSurface,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: darkBg,
      cardColor: darkCard,
      dividerColor: darkLine,
      canvasColor: darkBg,
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: darkSurface,
        surfaceTintColor: Colors.transparent,
        modalBackgroundColor: darkSurface,
      ),
      dialogTheme: const DialogThemeData(
        backgroundColor: darkSurface,
        surfaceTintColor: Colors.transparent,
      ),
      popupMenuTheme: const PopupMenuThemeData(
        color: darkCard,
        surfaceTintColor: Colors.transparent,
        textStyle: TextStyle(color: darkText),
      ),
      fontFamily: 'Roboto',
      appBarTheme: const AppBarTheme(
        backgroundColor: darkBg,
        foregroundColor: darkText,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: darkText,
          fontSize: 20,
          fontWeight: FontWeight.w900,
        ),
      ),
      textTheme: const TextTheme(
        headlineMedium: TextStyle(fontWeight: FontWeight.w900, color: darkText),
        titleLarge: TextStyle(fontWeight: FontWeight.w900, color: darkText),
        titleMedium: TextStyle(fontWeight: FontWeight.w800, color: darkText),
        bodyLarge: TextStyle(color: darkText),
        bodyMedium: TextStyle(color: darkMuted),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w900),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: darkCard,
        labelStyle: const TextStyle(color: darkMuted),
        hintStyle: const TextStyle(color: darkMuted),
        prefixIconColor: darkMuted,
        suffixIconColor: darkMuted,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: darkLine),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: darkLine),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: primary, width: 1.4),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: darkCard,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        contentTextStyle: const TextStyle(
          color: darkText,
          fontWeight: FontWeight.w700,
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: darkSurface,
        selectedItemColor: primary,
        unselectedItemColor: darkMuted,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
        unselectedLabelStyle: TextStyle(
          fontWeight: FontWeight.w500,
          fontSize: 12,
        ),
      ),
    );
  }
}

enum AppSkeletonLayout {
  list,
  storefront,
  serviceGrid,
  detail,
}

class AppSkeletonPage extends StatefulWidget {
  const AppSkeletonPage({
    super.key,
    this.cardCount = 5,
    this.showHero = true,
    this.layout = AppSkeletonLayout.list,
    this.delay = const Duration(milliseconds: 280),
    this.longLoadDelay = const Duration(seconds: 6),
  });

  final int cardCount;
  final bool showHero;
  final AppSkeletonLayout layout;
  final Duration delay;
  final Duration longLoadDelay;

  @override
  State<AppSkeletonPage> createState() => _AppSkeletonPageState();
}

class _AppSkeletonPageState extends State<AppSkeletonPage> {
  bool _visible = false;
  bool _takingLong = false;

  @override
  void initState() {
    super.initState();
    if (widget.delay == Duration.zero) {
      _visible = true;
    } else {
      Future<void>.delayed(widget.delay, () {
        if (mounted) setState(() => _visible = true);
      });
    }
    Future<void>.delayed(widget.longLoadDelay, () {
      if (mounted) setState(() => _takingLong = true);
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!_visible) {
      return const SizedBox.shrink();
    }
    return Semantics(
      container: true,
      label: _takingLong
          ? 'Loading is taking longer than usual'
          : 'Loading content',
      liveRegion: true,
      child: ExcludeSemantics(
        child: ListView(
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(18, 18, 18, 28),
          children: [
            ..._layoutChildren(),
            if (_takingLong) ...[
              const SizedBox(height: 16),
              const Text(
                'This is taking longer than usual. Please keep the screen open.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppTheme.muted,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  List<Widget> _layoutChildren() {
    switch (widget.layout) {
      case AppSkeletonLayout.storefront:
        return const [
          _StorefrontSkeleton(),
        ];
      case AppSkeletonLayout.serviceGrid:
        return const [
          _ServiceGridSkeleton(),
        ];
      case AppSkeletonLayout.detail:
        return const [
          _DetailSkeleton(),
        ];
      case AppSkeletonLayout.list:
        return [
          if (widget.showHero) ...[
            const AppSkeletonBox(height: 128, radius: 24),
            const SizedBox(height: 18),
          ],
          AppSkeletonList(cardCount: widget.cardCount),
        ];
    }
  }
}

class AppSkeletonList extends StatelessWidget {
  const AppSkeletonList({
    super.key,
    this.cardCount = 3,
  });

  final int cardCount;

  @override
  Widget build(BuildContext context) {
    return ExcludeSemantics(
      child: Column(
        children: [
          for (var index = 0; index < cardCount; index++) ...[
            const _AppSkeletonRow(),
            if (index != cardCount - 1) const SizedBox(height: 12),
          ],
        ],
      ),
    );
  }
}

class AppErrorState extends StatelessWidget {
  const AppErrorState({
    super.key,
    this.title = 'Something went wrong',
    this.detail,
    this.onRetry,
    this.icon = Icons.cloud_off_rounded,
  });

  final String title;
  final String? detail;
  final VoidCallback? onRetry;
  final IconData icon;

  static String userMessage(
    Object? error, {
    String fallback = 'The request could not be completed. Please try again.',
  }) {
    final safe = safeDetail(error?.toString());
    return safe == null || safe.isEmpty ? fallback : safe;
  }

  static String? safeDetail(String? detail) {
    final value = detail?.trim();
    if (value == null || value.isEmpty) return null;
    final lower = value.toLowerCase();
    final looksInternal = lower.contains('sqlexception') ||
        lower.contains('sqlstate') ||
        lower.contains('stack trace') ||
        lower.contains('socketexception') ||
        lower.contains('failed host lookup') ||
        lower.contains('no active transaction') ||
        lower.contains('exception:') ||
        lower.startsWith('server error');
    if (looksInternal) {
      return 'The request could not be completed. Please try again.';
    }
    return value;
  }

  @override
  Widget build(BuildContext context) {
    final sanitizedDetail = AppErrorState.safeDetail(detail);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 54, color: AppTheme.muted),
            const SizedBox(height: 14),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppTheme.ink,
                fontSize: 18,
                fontWeight: FontWeight.w900,
              ),
            ),
            if (sanitizedDetail != null && sanitizedDetail.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                sanitizedDetail,
                textAlign: TextAlign.center,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: AppTheme.muted,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class AppSkeletonBox extends StatefulWidget {
  const AppSkeletonBox({
    super.key,
    required this.height,
    this.width,
    this.radius = 16,
  });

  final double height;
  final double? width;
  final double radius;

  @override
  State<AppSkeletonBox> createState() => _AppSkeletonBoxState();
}

class AppDelayedSkeletonBox extends StatefulWidget {
  const AppDelayedSkeletonBox({
    super.key,
    required this.height,
    this.width,
    this.radius = 16,
    this.delay = const Duration(milliseconds: 280),
  });

  final double height;
  final double? width;
  final double radius;
  final Duration delay;

  @override
  State<AppDelayedSkeletonBox> createState() => _AppDelayedSkeletonBoxState();
}

class _AppDelayedSkeletonBoxState extends State<AppDelayedSkeletonBox> {
  bool _visible = false;

  @override
  void initState() {
    super.initState();
    if (widget.delay == Duration.zero) {
      _visible = true;
    } else {
      Future<void>.delayed(widget.delay, () {
        if (mounted) setState(() => _visible = true);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_visible) {
      return SizedBox(width: widget.width, height: widget.height);
    }
    return AppSkeletonBox(
      width: widget.width,
      height: widget.height,
      radius: widget.radius,
    );
  }
}

class _AppSkeletonBoxState extends State<AppSkeletonBox>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1450),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final reduceMotion = MediaQuery.maybeDisableAnimationsOf(context) ?? false;
    final box = Container(
      width: widget.width,
      height: widget.height,
      decoration: BoxDecoration(
        color: const Color(0xFFE0E6F0),
        borderRadius: BorderRadius.circular(widget.radius),
      ),
    );

    if (reduceMotion) return ExcludeSemantics(child: box);

    return ExcludeSemantics(
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          final shift = (_controller.value * 2) - 1;
          return ShaderMask(
            blendMode: BlendMode.srcATop,
            shaderCallback: (rect) {
              return LinearGradient(
                begin: Alignment(-1 + shift, -0.6),
                end: Alignment(1 + shift, 0.6),
                colors: const [
                  Colors.transparent,
                  Color(0x99F4F7FB),
                  Colors.transparent,
                ],
                stops: const [0.2, 0.5, 0.8],
              ).createShader(rect);
            },
            child: child,
          );
        },
        child: box,
      ),
    );
  }
}

class _AppSkeletonRow extends StatelessWidget {
  const _AppSkeletonRow();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: theme.dividerColor),
      ),
      child: const Row(
        children: [
          AppSkeletonBox(width: 56, height: 56, radius: 16),
          SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AppSkeletonBox(height: 13, radius: 7),
                SizedBox(height: 10),
                FractionallySizedBox(
                  widthFactor: 0.62,
                  child: AppSkeletonBox(height: 11, radius: 7),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StorefrontSkeleton extends StatelessWidget {
  const _StorefrontSkeleton();

  @override
  Widget build(BuildContext context) {
    return const Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppSkeletonBox(height: 142, radius: 28),
        SizedBox(height: 14),
        AppSkeletonBox(height: 56, radius: 18),
        SizedBox(height: 14),
        _SkeletonChips(count: 3),
        SizedBox(height: 18),
        _SkeletonBannerRow(),
        SizedBox(height: 22),
        _SkeletonSectionHeader(),
        SizedBox(height: 12),
        _SkeletonTileGrid(),
        SizedBox(height: 20),
        _SkeletonSectionHeader(short: true),
        SizedBox(height: 12),
        AppSkeletonList(cardCount: 2),
      ],
    );
  }
}

class _ServiceGridSkeleton extends StatelessWidget {
  const _ServiceGridSkeleton();

  @override
  Widget build(BuildContext context) {
    return const Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppSkeletonBox(height: 118, radius: 26),
        SizedBox(height: 14),
        AppSkeletonBox(height: 54, radius: 18),
        SizedBox(height: 18),
        _SkeletonSectionHeader(),
        SizedBox(height: 12),
        _SkeletonTileGrid(),
        SizedBox(height: 20),
        _SkeletonSectionHeader(short: true),
        SizedBox(height: 12),
        AppSkeletonList(cardCount: 3),
      ],
    );
  }
}

class _DetailSkeleton extends StatelessWidget {
  const _DetailSkeleton();

  @override
  Widget build(BuildContext context) {
    return const Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppSkeletonBox(height: 240, radius: 28),
        SizedBox(height: 16),
        AppSkeletonBox(height: 112, radius: 22),
        SizedBox(height: 16),
        _SkeletonChips(count: 2),
        SizedBox(height: 18),
        _SkeletonSectionHeader(),
        SizedBox(height: 12),
        AppSkeletonBox(height: 96, radius: 20),
        SizedBox(height: 18),
        AppSkeletonBox(height: 52, radius: 18),
      ],
    );
  }
}

class _SkeletonSectionHeader extends StatelessWidget {
  const _SkeletonSectionHeader({this.short = false});

  final bool short;

  @override
  Widget build(BuildContext context) {
    return FractionallySizedBox(
      widthFactor: short ? 0.42 : 0.62,
      child: const AppSkeletonBox(height: 18, radius: 9),
    );
  }
}

class _SkeletonChips extends StatelessWidget {
  const _SkeletonChips({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (var index = 0; index < count; index++) ...[
          const Expanded(child: AppSkeletonBox(height: 42, radius: 14)),
          if (index != count - 1) const SizedBox(width: 10),
        ],
      ],
    );
  }
}

class _SkeletonBannerRow extends StatelessWidget {
  const _SkeletonBannerRow();

  @override
  Widget build(BuildContext context) {
    return const Row(
      children: [
        Expanded(flex: 3, child: AppSkeletonBox(height: 112, radius: 24)),
        SizedBox(width: 12),
        Expanded(flex: 2, child: AppSkeletonBox(height: 112, radius: 24)),
      ],
    );
  }
}

class _SkeletonTileGrid extends StatelessWidget {
  const _SkeletonTileGrid();

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 4,
      mainAxisSpacing: 14,
      crossAxisSpacing: 14,
      childAspectRatio: 0.86,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      children: const [
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
        _SkeletonTile(),
      ],
    );
  }
}

class _SkeletonTile extends StatelessWidget {
  const _SkeletonTile();

  @override
  Widget build(BuildContext context) {
    return const Column(
      children: [
        AppSkeletonBox(height: 58, radius: 20),
        SizedBox(height: 9),
        AppSkeletonBox(height: 10, radius: 6),
      ],
    );
  }
}
