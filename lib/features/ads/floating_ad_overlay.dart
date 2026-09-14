import 'dart:math' as math;
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

import '../../core/app_theme.dart';
import '../ecommerce/data/ecommerce_api_client.dart';
import '../ecommerce/presentation/ecommerce_product_details_screen.dart';
import '../ecommerce/presentation/ecommerce_vendor_screen.dart';
import '../mart/data/mart_api_client.dart';
import '../mart/presentation/mart_product_details_screen.dart';
import '../mart/presentation/mart_vendor_screen.dart';
import 'floating_ad_client.dart';

class FloatingAdGate {
  FloatingAdGate._();

  static final ValueNotifier<bool> enabled = ValueNotifier<bool>(false);

  static void enable() {
    enabled.value = true;
  }

  static void disable() {
    enabled.value = false;
  }
}

class FloatingAdOverlay extends StatefulWidget {
  const FloatingAdOverlay({super.key, required this.child});

  final Widget child;

  @override
  State<FloatingAdOverlay> createState() => _FloatingAdOverlayState();
}

class _FloatingAdOverlayState extends State<FloatingAdOverlay> {
  FloatingAdData? _ad;
  Offset? _position;
  double? _mediaAspectRatio;
  bool _visible = false;
  bool _loaded = false;
  bool _loadingAd = false;

  @override
  void initState() {
    super.initState();
    FloatingAdGate.enabled.addListener(_handleGateChanged);
    _handleGateChanged();
  }

  @override
  void dispose() {
    FloatingAdGate.enabled.removeListener(_handleGateChanged);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final ad = _ad;
        final showLoader =
            FloatingAdGate.enabled.value && _loadingAd && ad == null;
        final showAd = FloatingAdGate.enabled.value && _visible && ad != null;
        if (!showLoader && !showAd) {
          return widget.child;
        }

        final bounds = Size(constraints.maxWidth, constraints.maxHeight);
        final window = showLoader
            ? _loadingWindowSizeFor(bounds)
            : _windowSizeFor(
                bounds,
                ad!,
                _mediaAspectRatio,
              );
        final defaultPosition = Offset(
          math.max(12, bounds.width - window.width - 18),
          math.max(72, bounds.height - window.height - 96),
        );
        final position = _clampPosition(
          _position ?? defaultPosition,
          bounds,
          window,
        );

        return Stack(
          children: [
            widget.child,
            Positioned(
              left: position.dx,
              top: position.dy,
              width: window.width,
              height: window.height,
              child: SafeArea(
                minimum: EdgeInsets.zero,
                child: GestureDetector(
                  behavior: HitTestBehavior.translucent,
                  onPanUpdate: (details) {
                    setState(() {
                      _position = _clampPosition(
                        (_position ?? position) + details.delta,
                        bounds,
                        window,
                      );
                    });
                  },
                  onPanEnd: (_) {
                    final current = _position ?? position;
                    final snapLeft =
                        current.dx + (window.width / 2) < bounds.width / 2;
                    final target = Offset(
                      snapLeft ? 10 : bounds.width - window.width - 10,
                      current.dy,
                    );
                    setState(() {
                      _position = _clampPosition(target, bounds, window);
                    });
                  },
                  child: showLoader
                      ? const _FloatingAdLoadingWindow()
                      : _FloatingAdMiniWindow(
                          ad: ad!,
                          onClose: _dismiss,
                          onAspectRatio: _setMediaAspectRatio,
                        ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  void _handleGateChanged() {
    if (!FloatingAdGate.enabled.value) {
      if (!mounted) return;
      setState(() => _visible = false);
      return;
    }

    if (!_loaded) {
      _loaded = true;
      _loadAd();
      return;
    }

    if (mounted) setState(() {});
  }

  Offset _clampPosition(Offset value, Size bounds, Size window) {
    const edge = 10.0;
    final maxX = math.max(edge, bounds.width - window.width - edge);
    final maxY = math.max(edge, bounds.height - window.height - edge);
    return Offset(
      value.dx.clamp(edge, maxX).toDouble(),
      value.dy.clamp(edge, maxY).toDouble(),
    );
  }

  Size _windowSizeFor(
    Size bounds,
    FloatingAdData ad,
    double? mediaAspectRatio,
  ) {
    final shortestSide = math.min(bounds.width, bounds.height);
    final maxWidth = math.min(180.0, shortestSide * 0.44);
    final maxHeight = math.min(260.0, bounds.height * 0.34);
    final aspectRatio = (mediaAspectRatio != null && mediaAspectRatio > 0)
        ? mediaAspectRatio
        : (ad.videoUrl.trim().isNotEmpty ? 9 / 16 : 1.0);

    final heightFromWidth = maxWidth / aspectRatio;
    if (heightFromWidth <= maxHeight) {
      return Size(maxWidth, heightFromWidth);
    }

    final widthFromHeight = maxHeight * aspectRatio;
    return Size(math.max(118.0, widthFromHeight), maxHeight);
  }

  Size _loadingWindowSizeFor(Size bounds) {
    final shortestSide = math.min(bounds.width, bounds.height);
    final width = math.min(150.0, shortestSide * 0.38);
    return Size(width, width * 16 / 9);
  }

  void _setMediaAspectRatio(double value) {
    if (!mounted || value <= 0) return;
    if ((_mediaAspectRatio ?? 0) == value) return;
    setState(() => _mediaAspectRatio = value);
  }

  Future<void> _loadAd() async {
    setState(() => _loadingAd = true);
    try {
      final ad = await FloatingAdClient().fetch();
      if (!mounted) return;
      setState(() {
        _ad = ad;
        _mediaAspectRatio = null;
        _visible = ad.enabled && ad.hasContent;
        _loadingAd = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _ad = null;
        _visible = false;
        _loadingAd = false;
      });
    }
  }

  Future<void> _dismiss() async {
    setState(() => _visible = false);
  }
}

class _FloatingAdMiniWindow extends StatefulWidget {
  const _FloatingAdMiniWindow({
    required this.ad,
    required this.onClose,
    required this.onAspectRatio,
  });

  final FloatingAdData ad;
  final VoidCallback onClose;
  final ValueChanged<double> onAspectRatio;

  @override
  State<_FloatingAdMiniWindow> createState() => _FloatingAdMiniWindowState();
}

class _FloatingAdLoadingWindow extends StatelessWidget {
  const _FloatingAdLoadingWindow();

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: Colors.black,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.28),
              blurRadius: 18,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: const _FloatingAdSkeleton(
            mediaKind: _FloatingAdMediaKind.video,
          ),
        ),
      ),
    );
  }
}

class _FloatingAdMiniWindowState extends State<_FloatingAdMiniWindow> {
  bool _controlsVisible = false;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => setState(() => _controlsVisible = !_controlsVisible),
        child: DecoratedBox(
            decoration: BoxDecoration(
              color: Colors.black,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.34),
                  blurRadius: 20,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  _FloatingAdMedia(
                    ad: widget.ad,
                    onAspectRatio: widget.onAspectRatio,
                  ),
                  if (_controlsVisible) ...[
                    if (widget.ad.title.trim().isNotEmpty ||
                        widget.ad.message.trim().isNotEmpty ||
                        widget.ad.ctaText.trim().isNotEmpty)
                      Positioned(
                        left: 0,
                        right: 0,
                        bottom: 0,
                        child: _FloatingAdCaption(
                          ad: widget.ad,
                          onExplore: () => _openTarget(context, widget.ad),
                        ),
                      ),
                    Positioned(
                      right: 6,
                      top: 6,
                      child: Material(
                        color: Colors.black.withValues(alpha: 0.58),
                        shape: const CircleBorder(),
                        child: InkWell(
                          customBorder: const CircleBorder(),
                          onTap: widget.onClose,
                          child: const SizedBox(
                            width: 30,
                            height: 30,
                            child: Icon(
                              Icons.close_rounded,
                              color: Colors.white,
                              size: 19,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            )),
      ),
    );
  }

  Future<void> _openTarget(BuildContext context, FloatingAdData ad) async {
    final module = ad.targetModule.trim();
    final type = ad.targetType.trim();
    final value = ad.targetValue.trim();
    final id = int.tryParse(value);

    try {
      if (type == 'product' && id != null) {
        if (module == 'mart') {
          final product = await MartApiClient().fetchProduct(id);
          if (!context.mounted) return;
          await Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => MartProductDetailsScreen(product: product),
          ));
          return;
        }
        if (module == 'ecommerce' || module == 'medical') {
          final baseUrl = module == 'medical'
              ? 'https://snow-grouse-381496.hostingersite.com/api/v1/medical'
              : null;
          final product =
              await EcommerceApiClient(baseUrl: baseUrl).fetchProduct(id);
          if (!context.mounted) return;
          await Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => EcommerceProductDetailsScreen(
              product: product,
              apiBaseUrl: baseUrl,
            ),
          ));
          return;
        }
      }

      if (type == 'store' && id != null) {
        if (module == 'mart') {
          final vendor = await MartApiClient().fetchVendor(id);
          if (!context.mounted) return;
          await Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => MartVendorScreen(
              vendorId: vendor.id,
              vendorName: vendor.shopName,
            ),
          ));
          return;
        }
        if (module == 'ecommerce' || module == 'medical') {
          final baseUrl = module == 'medical'
              ? 'https://snow-grouse-381496.hostingersite.com/api/v1/medical'
              : null;
          final vendor =
              await EcommerceApiClient(baseUrl: baseUrl).fetchVendor(id);
          if (!context.mounted) return;
          await Navigator.of(context).push(MaterialPageRoute<void>(
            builder: (_) => EcommerceVendorScreen(
              vendorId: vendor.id,
              vendorName: vendor.shopName,
              apiBaseUrl: baseUrl,
            ),
          ));
          return;
        }
      }
    } catch (_) {
      // Fall through to URL fallback below.
    }

    final rawUrl = type == 'url' && value.isNotEmpty ? value : ad.linkUrl;
    final uri = _safeUri(rawUrl);
    if (uri == null || !uri.hasScheme) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class _FloatingAdCaption extends StatelessWidget {
  const _FloatingAdCaption({
    required this.ad,
    required this.onExplore,
  });

  final FloatingAdData ad;
  final VoidCallback onExplore;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Colors.transparent,
            Colors.black.withValues(alpha: 0.78),
          ],
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(10, 28, 10, 10),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (ad.title.trim().isNotEmpty)
              Text(
                ad.title,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w900,
                ),
              ),
            if (ad.message.trim().isNotEmpty) ...[
              const SizedBox(height: 3),
              Text(
                ad.message,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 10.5,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
            if (ad.ctaText.trim().isNotEmpty) ...[
              const SizedBox(height: 7),
              GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: onExplore,
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: AppTheme.primary,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Padding(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    child: Text(
                      ad.ctaText,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10.5,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _FloatingAdMedia extends StatelessWidget {
  const _FloatingAdMedia({
    required this.ad,
    required this.onAspectRatio,
  });

  final FloatingAdData ad;
  final ValueChanged<double> onAspectRatio;

  @override
  Widget build(BuildContext context) {
    final videoUrl = ad.videoUrl.trim();
    if (videoUrl.isNotEmpty) {
      return _FloatingAdVideo(
        url: videoUrl,
        onAspectRatio: onAspectRatio,
      );
    }

    final imageUrl = ad.imageUrl.trim();
    if (imageUrl.isEmpty) {
      return const ColoredBox(
        color: Colors.black,
        child: Center(
          child: Icon(Icons.campaign_outlined, color: Colors.white70, size: 42),
        ),
      );
    }

    return Image.network(
      imageUrl,
      fit: BoxFit.cover,
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return const _FloatingAdSkeleton(mediaKind: _FloatingAdMediaKind.image);
      },
      errorBuilder: (_, __, ___) => const _FloatingAdErrorPlaceholder(
        icon: Icons.campaign_outlined,
      ),
    );
  }
}

enum _FloatingAdMediaKind { image, video }

class _FloatingAdSkeleton extends StatefulWidget {
  const _FloatingAdSkeleton({required this.mediaKind});

  final _FloatingAdMediaKind mediaKind;

  @override
  State<_FloatingAdSkeleton> createState() => _FloatingAdSkeletonState();
}

class _FloatingAdSkeletonState extends State<_FloatingAdSkeleton> {
  bool _visible = false;
  bool _takingLong = false;

  @override
  void initState() {
    super.initState();
    Future<void>.delayed(const Duration(milliseconds: 180), () {
      if (mounted) setState(() => _visible = true);
    });
    Future<void>.delayed(const Duration(seconds: 6), () {
      if (mounted) setState(() => _takingLong = true);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Semantics(
      container: true,
      liveRegion: true,
      label: _takingLong ? 'Advertisement is still loading' : 'Loading ad',
      child: DecoratedBox(
        decoration: const BoxDecoration(color: Color(0xFF0E1420)),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: LayoutBuilder(
            builder: (context, constraints) {
              final shortSide = math.min(
                constraints.maxWidth,
                constraints.maxHeight,
              );
              if (!_visible) {
                return const SizedBox.expand();
              }
              return Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  AppSkeletonBox(
                    width: shortSide * 0.48,
                    height: shortSide * 0.48,
                    radius: shortSide * 0.24,
                  ),
                  const SizedBox(height: 12),
                  const AppSkeletonBox(width: double.infinity, height: 10),
                  const SizedBox(height: 7),
                  FractionallySizedBox(
                    widthFactor: widget.mediaKind == _FloatingAdMediaKind.video
                        ? 0.64
                        : 0.48,
                    child: const AppSkeletonBox(
                      width: double.infinity,
                      height: 9,
                    ),
                  ),
                  if (_takingLong) ...[
                    const SizedBox(height: 10),
                    const ExcludeSemantics(
                      child: Text(
                        'Still loading',
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _FloatingAdErrorPlaceholder extends StatelessWidget {
  const _FloatingAdErrorPlaceholder({required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return ColoredBox(
      color: Colors.black,
      child: Center(
        child: Icon(icon, color: Colors.white70, size: 42),
      ),
    );
  }
}

class _FloatingAdVideo extends StatefulWidget {
  const _FloatingAdVideo({
    required this.url,
    required this.onAspectRatio,
  });

  final String url;
  final ValueChanged<double> onAspectRatio;

  @override
  State<_FloatingAdVideo> createState() => _FloatingAdVideoState();
}

class _FloatingAdVideoState extends State<_FloatingAdVideo> {
  VideoPlayerController? _controller;
  bool _ready = false;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    try {
      final uri = _safeUri(widget.url);
      if (uri == null || !uri.hasScheme) {
        throw const FormatException('Invalid video URL');
      }

      final file = await _downloadVideo(uri);
      final controller = VideoPlayerController.file(file)
        ..setLooping(true)
        ..setVolume(0);
      _controller = controller;
      await controller.initialize();
      if (!mounted) return;
      setState(() => _ready = true);
      widget.onAspectRatio(controller.value.aspectRatio);
      controller.play();
    } catch (_) {
      if (!mounted) return;
      setState(() => _failed = true);
    }
  }

  Future<File> _downloadVideo(Uri uri) async {
    final directory = await getTemporaryDirectory();
    final fileName = 'floating_ad_${uri.toString().hashCode}.mp4';
    final file = File('${directory.path}/$fileName');
    if (await file.exists() && await file.length() > 0) {
      return file;
    }

    final client = HttpClient();
    try {
      final request = await client.getUrl(uri);
      final response = await request.close();
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw HttpException(
          'Video download failed with status ${response.statusCode}',
          uri: uri,
        );
      }

      final sink = file.openWrite();
      await sink.addStream(response);
      await sink.close();
    } finally {
      client.close(force: true);
    }
    return file;
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    if (_failed || controller == null || !_ready) {
      if (_failed) {
        return const _FloatingAdErrorPlaceholder(
          icon: Icons.play_circle_outline_rounded,
        );
      }
      return const _FloatingAdSkeleton(mediaKind: _FloatingAdMediaKind.video);
    }

    final size = controller.value.size;
    if (size.width <= 0 || size.height <= 0) {
      return VideoPlayer(controller);
    }

    return ColoredBox(
      color: Colors.black,
      child: Center(
        child: AspectRatio(
          aspectRatio: controller.value.aspectRatio,
          child: VideoPlayer(controller),
        ),
      ),
    );
  }
}

Uri? _safeUri(String value) {
  final trimmed = value.trim();
  if (trimmed.isEmpty) return null;
  return Uri.tryParse(Uri.encodeFull(trimmed));
}
