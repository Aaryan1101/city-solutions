import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/app_theme.dart';
import '../mart/data/mart_api_client.dart';
import '../zone/zone_store.dart';

class ComplaintScreen extends StatefulWidget {
  const ComplaintScreen({
    super.key,
    this.initialModuleKey = 'global',
    this.initialReferenceId,
    this.initialCategory,
    this.initialLocation,
    this.initialName,
    this.initialPhone,
    this.initialDescription,
    this.requirePhoto = true,
    this.lockRouting = false,
  });

  final String initialModuleKey;
  final int? initialReferenceId;
  final String? initialCategory;
  final String? initialLocation;
  final String? initialName;
  final String? initialPhone;
  final String? initialDescription;
  final bool requirePhoto;
  final bool lockRouting;

  @override
  State<ComplaintScreen> createState() => _ComplaintScreenState();
}

class _ComplaintScreenState extends State<ComplaintScreen> {
  File? _image;
  final _locationController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _referenceController = TextEditingController();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  String? _selectedCategory;
  String _moduleKey = 'global';
  String _severity = 'normal';
  bool _isSubmitting = false;

  static const _categories = [
    ('Pothole', Icons.warning_amber_rounded),
    ('Streetlight', Icons.lightbulb_outline),
    ('Water Leakage', Icons.water_drop_outlined),
    ('Garbage', Icons.delete_outline),
    ('Road Damage', Icons.construction_outlined),
    ('Order Issue', Icons.inventory_2_outlined),
    ('Other', Icons.more_horiz_rounded),
  ];

  static const _modules = [
    ('global', 'City issue'),
    ('mart', 'Mart'),
    ('ecommerce', 'E-Commerce'),
    ('medical', 'Medical'),
    ('services', 'Services'),
    ('hotel', 'Hotel'),
    ('restaurant', 'Restaurant'),
    ('real_estate', 'Real Estate'),
  ];

  static const _severityOptions = [
    ('low', 'Low'),
    ('normal', 'Normal'),
    ('high', 'High'),
    ('urgent', 'Urgent'),
  ];

  @override
  void initState() {
    super.initState();
    _moduleKey = _modules.any((module) => module.$1 == widget.initialModuleKey)
        ? widget.initialModuleKey
        : 'global';
    _selectedCategory = widget.initialCategory;
    _referenceController.text = widget.initialReferenceId?.toString() ?? '';
    _nameController.text = widget.initialName ?? '';
    _phoneController.text = widget.initialPhone ?? '';
    _descriptionController.text = widget.initialDescription ?? '';
    final selected = ZoneStore.instance.selectedLocation.value;
    if ((widget.initialLocation ?? '').trim().isNotEmpty) {
      _locationController.text = widget.initialLocation!.trim();
    } else if (selected != null) {
      _locationController.text = selected.address;
    }
  }

  @override
  void dispose() {
    _locationController.dispose();
    _descriptionController.dispose();
    _referenceController.dispose();
    _nameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(source: source, imageQuality: 85);
      if (picked != null && mounted) {
        setState(() => _image = File(picked.path));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not access camera or gallery.')),
        );
      }
    }
  }

  void _showImageSourceSheet() {
    showModalBottomSheet<void>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Theme.of(context).dividerColor,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined,
                  color: AppTheme.primary),
              title: const Text('Take a Photo',
                  style: TextStyle(fontWeight: FontWeight.w700)),
              onTap: () {
                Navigator.pop(ctx);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined,
                  color: AppTheme.primary),
              title: const Text('Choose from Gallery',
                  style: TextStyle(fontWeight: FontWeight.w700)),
              onTap: () {
                Navigator.pop(ctx);
                _pickImage(ImageSource.gallery);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (widget.requirePhoto && _image == null) {
      _snack('Please attach a photo of the issue.');
      return;
    }
    if (_locationController.text.trim().isEmpty) {
      _snack('Please enter the location.');
      return;
    }
    if (_descriptionController.text.trim().length < 10) {
      _snack('Please describe the issue in at least 10 characters.');
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final bytes = _image == null ? null : await _image!.readAsBytes();
      final client = HttpClient();
      try {
        final request = await client.postUrl(
          Uri.parse(
                  '${MartApiConfig.baseUrl.replaceAll(RegExp(r'/$'), '')}/../complaints')
              .normalizePath(),
        );
        request.headers.set(HttpHeaders.acceptHeader, 'application/json');
        request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
        request.write(jsonEncode({
          'module_key': _moduleKey,
          'severity': _severity,
          'zone_id': ZoneStore.instance.selectedLocation.value?.zone.id,
          'customer_name': _nameController.text.trim(),
          'customer_phone': _phoneController.text.trim(),
          ..._referencePayload(),
          'category': _selectedCategory ?? 'Other',
          'location': _locationController.text.trim(),
          'description': _descriptionController.text.trim(),
          if (_image != null) 'image_name': _image!.path.split('/').last,
          if (bytes != null) 'image_base64': base64Encode(bytes),
        }));
        final response = await request.close();
        final raw = await response.transform(utf8.decoder).join();
        if (response.statusCode < 200 || response.statusCode >= 300) {
          final decoded = raw.isEmpty ? null : jsonDecode(raw);
          throw ComplaintSubmitException(decoded is Map
              ? decoded['message']?.toString() ?? raw
              : 'Could not submit complaint.');
        }
      } finally {
        client.close(force: true);
      }
    } catch (error) {
      if (mounted) {
        setState(() => _isSubmitting = false);
        _snack(AppErrorState.userMessage(error));
      }
      return;
    }
    if (!mounted) return;
    setState(() => _isSubmitting = false);

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: const EdgeInsets.fromLTRB(24, 28, 24, 8),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 76,
              height: 76,
              decoration: BoxDecoration(
                color: Colors.green.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle_rounded,
                  color: Colors.green, size: 46),
            ),
            const SizedBox(height: 18),
            const Text(
              'Complaint Submitted!',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Your complaint has been registered. Our team will review and take action shortly.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, height: 1.5),
            ),
            const SizedBox(height: 8),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.pop(context);
            },
            child: const Text('Done',
                style: TextStyle(
                    fontWeight: FontWeight.w800, color: AppTheme.primary)),
          ),
        ],
      ),
    );
  }

  Map<String, dynamic> _referencePayload() {
    final id = int.tryParse(_referenceController.text.trim());
    if (id == null || id <= 0) return const {};
    if (['mart', 'ecommerce', 'medical'].contains(_moduleKey)) {
      return {'order_id': id};
    }
    if (['services', 'hotel', 'restaurant'].contains(_moduleKey)) {
      return {'booking_id': id};
    }
    if (_moduleKey == 'real_estate') {
      return {'real_estate_property_id': id};
    }
    return const {};
  }

  void _snack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: const Text('Raise a Complaint',
            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18)),
        backgroundColor: Theme.of(context).colorScheme.surface,
        foregroundColor: Theme.of(context).colorScheme.onSurface,
        elevation: 0,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: Theme.of(context).dividerColor),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(18, 22, 18, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _sectionLabel('Routing'),
            const SizedBox(height: 10),
            if (widget.lockRouting)
              _LockedRouteChip(label: _moduleLabel(_moduleKey))
            else
              _SegmentWrap(
                values: _modules,
                selected: _moduleKey,
                onSelected: (value) => setState(() => _moduleKey = value),
              ),
            const SizedBox(height: 16),
            _sectionLabel('Severity'),
            const SizedBox(height: 10),
            _SegmentWrap(
              values: _severityOptions,
              selected: _severity,
              onSelected: (value) => setState(() => _severity = value),
            ),
            const SizedBox(height: 20),
            _sectionLabel('Category'),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _categories.map((cat) {
                final selected = _selectedCategory == cat.$1;
                return GestureDetector(
                  onTap: () => setState(() => _selectedCategory = cat.$1),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    padding:
                        const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                    decoration: BoxDecoration(
                      color: selected
                          ? AppTheme.primary
                          : Theme.of(context).colorScheme.surface,
                      border: Border.all(
                          color: selected
                              ? AppTheme.primary
                              : Theme.of(context).dividerColor),
                      borderRadius: BorderRadius.circular(22),
                      boxShadow: selected
                          ? [
                              BoxShadow(
                                color: AppTheme.primary.withValues(alpha: 0.3),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              )
                            ]
                          : null,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(cat.$2,
                            size: 16,
                            color: selected
                                ? Colors.white
                                : Theme.of(context)
                                    .colorScheme
                                    .onSurfaceVariant),
                        const SizedBox(width: 6),
                        Text(
                          cat.$1,
                          style: TextStyle(
                            color: selected
                                ? Colors.white
                                : Theme.of(context).colorScheme.onSurface,
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 26),
            _sectionLabel('Photo Evidence'),
            const SizedBox(height: 10),
            if (!widget.requirePhoto) ...[
              const Text(
                'Optional for missing items. Recommended for damaged or wrong items.',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 10),
            ],
            GestureDetector(
              onTap: _showImageSourceSheet,
              child: _image == null
                  ? _ImagePickerPlaceholder()
                  : _ImagePreview(
                      image: _image!,
                      onRemove: () => setState(() => _image = null),
                      onReplace: _showImageSourceSheet,
                    ),
            ),
            const SizedBox(height: 26),
            _sectionLabel('Location'),
            const SizedBox(height: 10),
            _StyledField(
              controller: _locationController,
              hint: 'Enter street address or landmark',
              prefixIcon: Icons.location_on_outlined,
              maxLines: 1,
            ),
            const SizedBox(height: 20),
            _sectionLabel('Related ID'),
            const SizedBox(height: 10),
            _StyledField(
              controller: _referenceController,
              hint: _moduleKey == 'real_estate'
                  ? 'Property ID optional'
                  : ['mart', 'ecommerce', 'medical'].contains(_moduleKey)
                      ? 'Order ID optional'
                      : ['services', 'hotel', 'restaurant'].contains(_moduleKey)
                          ? 'Booking ID optional'
                          : 'Reference ID optional',
              prefixIcon: Icons.tag_outlined,
              maxLines: 1,
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 20),
            _sectionLabel('Contact'),
            const SizedBox(height: 10),
            _StyledField(
              controller: _nameController,
              hint: 'Your name optional',
              prefixIcon: Icons.person_outline,
              maxLines: 1,
            ),
            const SizedBox(height: 10),
            _StyledField(
              controller: _phoneController,
              hint: 'Phone number optional',
              prefixIcon: Icons.phone_outlined,
              maxLines: 1,
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 20),
            _sectionLabel('Description'),
            const SizedBox(height: 10),
            _StyledField(
              controller: _descriptionController,
              hint: 'Describe the issue in detail...',
              maxLines: 4,
            ),
            const SizedBox(height: 34),
            SizedBox(
              width: double.infinity,
              height: 54,
              child: FilledButton(
                onPressed: _isSubmitting ? null : _submit,
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.primary,
                  disabledBackgroundColor:
                      AppTheme.primary.withValues(alpha: 0.5),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                  elevation: 0,
                ),
                child: _isSubmitting
                    ? const AppSkeletonBox(width: 22, height: 22, radius: 11)
                    : const Text(
                        'Submit Complaint',
                        style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: Colors.white),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionLabel(String text) {
    return Text(
      text,
      style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
    );
  }

  String _moduleLabel(String moduleKey) {
    return _modules
        .firstWhere((module) => module.$1 == moduleKey,
            orElse: () => _modules.first)
        .$2;
  }
}

class ComplaintSubmitException implements Exception {
  const ComplaintSubmitException(this.message);

  final String message;

  @override
  String toString() => message;
}

class _LockedRouteChip extends StatelessWidget {
  const _LockedRouteChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: AppTheme.primary.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.route_outlined, color: AppTheme.primary, size: 16),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              color: AppTheme.primary,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Image Picker Placeholder ──────────────────────────────────────────────────

class _ImagePickerPlaceholder extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _DashedBorderPainter(
          color: AppTheme.primary.withValues(alpha: 0.4), radius: 16),
      child: Container(
        height: 160,
        decoration: BoxDecoration(
          color: AppTheme.primary.withValues(alpha: 0.04),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 58,
              height: 58,
              decoration: BoxDecoration(
                color: AppTheme.primary.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.camera_alt_outlined,
                  color: AppTheme.primary, size: 28),
            ),
            const SizedBox(height: 12),
            const Text('Tap to add photo',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
            const SizedBox(height: 3),
            const Text('Camera or Gallery', style: TextStyle(fontSize: 12)),
          ],
        ),
      ),
    );
  }
}

// ── Image Preview ─────────────────────────────────────────────────────────────

class _ImagePreview extends StatelessWidget {
  const _ImagePreview({
    required this.image,
    required this.onRemove,
    required this.onReplace,
  });

  final File image;
  final VoidCallback onRemove;
  final VoidCallback onReplace;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: Stack(
        children: [
          Image.file(
            image,
            height: 210,
            width: double.infinity,
            fit: BoxFit.cover,
          ),
          Positioned(
            top: 10,
            right: 10,
            child: _Chip(
              icon: Icons.close_rounded,
              label: 'Remove',
              onTap: onRemove,
            ),
          ),
          Positioned(
            bottom: 10,
            right: 10,
            child: _Chip(
              icon: Icons.edit_outlined,
              label: 'Change',
              onTap: onReplace,
            ),
          ),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.58),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: Colors.white, size: 14),
            const SizedBox(width: 4),
            Text(label,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}

class _SegmentWrap extends StatelessWidget {
  const _SegmentWrap({
    required this.values,
    required this.selected,
    required this.onSelected,
  });

  final List<(String, String)> values;
  final String selected;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final item in values)
          ChoiceChip(
            label: Text(item.$2),
            selected: selected == item.$1,
            onSelected: (_) => onSelected(item.$1),
            selectedColor: AppTheme.primary.withValues(alpha: 0.14),
            labelStyle: TextStyle(
              color: selected == item.$1
                  ? AppTheme.primary
                  : Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.w800,
            ),
            side: BorderSide(
              color: selected == item.$1
                  ? AppTheme.primary
                  : Theme.of(context).dividerColor,
            ),
            backgroundColor: Theme.of(context).colorScheme.surface,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(999),
            ),
          ),
      ],
    );
  }
}

// ── Styled Text Field ─────────────────────────────────────────────────────────

class _StyledField extends StatelessWidget {
  const _StyledField({
    required this.controller,
    required this.hint,
    this.prefixIcon,
    this.maxLines = 1,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String hint;
  final IconData? prefixIcon;
  final int maxLines;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      style: const TextStyle(fontSize: 14),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(fontSize: 14),
        prefixIcon: prefixIcon != null
            ? Icon(prefixIcon, color: AppTheme.primary, size: 22)
            : null,
        filled: true,
        fillColor: Theme.of(context).colorScheme.surface,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Theme.of(context).dividerColor),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: Theme.of(context).dividerColor),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppTheme.primary, width: 1.5),
        ),
      ),
    );
  }
}

// ── Dashed Border Painter ─────────────────────────────────────────────────────

class _DashedBorderPainter extends CustomPainter {
  const _DashedBorderPainter({required this.color, required this.radius});

  final Color color;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 1.6
      ..style = PaintingStyle.stroke;

    final path = Path()
      ..addRRect(RRect.fromRectAndRadius(
        Rect.fromLTWH(0, 0, size.width, size.height),
        Radius.circular(radius),
      ));

    const dash = 8.0;
    const gap = 5.0;

    for (final metric in path.computeMetrics()) {
      var distance = 0.0;
      while (distance < metric.length) {
        canvas.drawPath(metric.extractPath(distance, distance + dash), paint);
        distance += dash + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
