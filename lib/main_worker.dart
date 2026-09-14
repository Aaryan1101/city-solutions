import 'dart:async';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';

import 'core/app_theme.dart';
import 'core/firebase_service.dart';
import 'core/theme_mode_controller.dart';
import 'worker/worker_api_client.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppThemeModeController.instance.load();
  await CityFirebaseService.instance.initialize();
  runApp(const CityWorkerApp());
}

class CityWorkerApp extends StatelessWidget {
  const CityWorkerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<ThemeMode>(
      valueListenable: AppThemeModeController.instance.themeMode,
      builder: (context, themeMode, _) => MaterialApp(
        title: 'City Worker',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        themeMode: themeMode,
        home: const WorkerRootScreen(),
      ),
    );
  }
}

class WorkerRootScreen extends StatefulWidget {
  const WorkerRootScreen({super.key});

  @override
  State<WorkerRootScreen> createState() => _WorkerRootScreenState();
}

class _WorkerRootScreenState extends State<WorkerRootScreen> {
  final _client = WorkerApiClient();
  WorkerSession? _session;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _restore();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
          body: AppSkeletonPage(layout: AppSkeletonLayout.list));
    }
    final session = _session;
    if (session == null || session.token.isEmpty) {
      return WorkerLoginScreen(
        client: _client,
        onLoggedIn: (value) => setState(() => _session = value),
      );
    }
    return WorkerDashboardScreen(
      client: _client,
      session: session,
      onLogout: () async {
        await _client.logout();
        if (mounted) setState(() => _session = null);
      },
    );
  }

  Future<void> _restore() async {
    final session = await _client.savedSession();
    if (session != null) {
      unawaited(CityFirebaseService.instance.syncWorker(
        workerId: session.worker.id,
        role: session.worker.role,
        bearerToken: session.token,
      ));
    }
    if (mounted) {
      setState(() {
        _session = session;
        _loading = false;
      });
    }
  }
}

class WorkerLoginScreen extends StatefulWidget {
  const WorkerLoginScreen(
      {super.key, required this.client, required this.onLoggedIn});

  final WorkerApiClient client;
  final ValueChanged<WorkerSession> onLoggedIn;

  @override
  State<WorkerLoginScreen> createState() => _WorkerLoginScreenState();
}

class _WorkerLoginScreenState extends State<WorkerLoginScreen> {
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 48),
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: AppTheme.primary,
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(Icons.delivery_dining_rounded,
                  color: Colors.white, size: 34),
            ),
            const SizedBox(height: 22),
            const Text('City Worker',
                style: TextStyle(
                    color: AppTheme.ink,
                    fontSize: 32,
                    fontWeight: FontWeight.w900)),
            const SizedBox(height: 8),
            const Text('Delivery, service and driver assignments in one app.',
                style: TextStyle(
                    color: AppTheme.muted, fontWeight: FontWeight.w700)),
            const SizedBox(height: 28),
            TextField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                  labelText: 'Phone', prefixIcon: Icon(Icons.call_outlined)),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _passwordController,
              obscureText: true,
              decoration: const InputDecoration(
                  labelText: 'Password', prefixIcon: Icon(Icons.lock_outline)),
            ),
            const SizedBox(height: 22),
            SizedBox(
              height: 52,
              child: FilledButton(
                onPressed: _loading ? null : _login,
                child: Text(_loading ? 'Signing in...' : 'Sign in'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _login() async {
    setState(() => _loading = true);
    try {
      final session = await widget.client
          .login(_phoneController.text.trim(), _passwordController.text.trim());
      unawaited(CityFirebaseService.instance.syncWorker(
        workerId: session.worker.id,
        role: session.worker.role,
        bearerToken: session.token,
      ));
      widget.onLoggedIn(session);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppErrorState.userMessage(error))));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class WorkerDashboardScreen extends StatefulWidget {
  const WorkerDashboardScreen(
      {super.key,
      required this.client,
      required this.session,
      required this.onLogout});

  final WorkerApiClient client;
  final WorkerSession session;
  final VoidCallback onLogout;

  @override
  State<WorkerDashboardScreen> createState() => _WorkerDashboardScreenState();
}

class _WorkerDashboardScreenState extends State<WorkerDashboardScreen> {
  List<WorkerAssignment> _assignments = const [];
  bool _loading = true;
  bool _onlineUpdating = false;
  bool _online = false;
  Timer? _refreshTimer;
  Timer? _locationTimer;
  String? _error;

  @override
  void initState() {
    super.initState();
    _online = widget.session.worker.availabilityStatus == 'online';
    _load();
    _refreshTimer = Timer.periodic(
      const Duration(seconds: 12),
      (_) => _load(silent: true),
    );
    _locationTimer = Timer.periodic(
      const Duration(seconds: 20),
      (_) {
        if (_online) _pushCurrentLocation();
      },
    );
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _locationTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Assignments'),
        actions: [
          IconButton(
              tooltip: 'Logout',
              onPressed: widget.onLogout,
              icon: const Icon(Icons.logout_rounded)),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(18, 10, 18, 28),
          children: [
            _WorkerHeader(
                worker: widget.session.worker, count: _assignments.length),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _onlineUpdating
                        ? null
                        : () => _setAvailability('online'),
                    icon: const Icon(Icons.radio_button_checked_rounded),
                    label: const Text('Go online'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _onlineUpdating
                        ? null
                        : () => _setAvailability('offline'),
                    icon: const Icon(Icons.pause_circle_outline_rounded),
                    label: const Text('Go offline'),
                  ),
                ),
              ],
            ),
            if (widget.session.worker.role == 'taxi_driver' &&
                !widget.session.worker.kycReady) ...[
              const SizedBox(height: 12),
              Card(
                color: Theme.of(context).colorScheme.errorContainer,
                child: const Padding(
                  padding: EdgeInsets.all(14),
                  child: Text(
                    'Taxi offers are paused. Ask dispatch to upload valid licence, RC and insurance documents.',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ],
            const SizedBox(height: 16),
            if (_loading)
              const AppSkeletonList(cardCount: 5)
            else if (_error != null)
              AppErrorState(detail: _error, onRetry: _load)
            else if (_assignments.isEmpty)
              const AppErrorState(
                  title: 'No active assignments',
                  detail:
                      'Assigned orders, service jobs and cab rides will appear here.',
                  icon: Icons.assignment_turned_in_outlined)
            else
              for (final assignment in _assignments) ...[
                _AssignmentCard(
                  assignment: assignment,
                  onStatus: (status) => _update(assignment, status),
                ),
                const SizedBox(height: 12),
              ],
          ],
        ),
      ),
    );
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final assignments = await widget.client.assignments(widget.session.token);
      if (mounted) setState(() => _assignments = assignments);
    } catch (error) {
      if (mounted && !silent) {
        setState(() => _error = AppErrorState.userMessage(error));
      }
    } finally {
      if (mounted && !silent) setState(() => _loading = false);
    }
  }

  Future<void> _update(WorkerAssignment assignment, String status) async {
    try {
      var otp = '';
      if (assignment.type == 'taxi_ride' && status == 'started') {
        otp = await _askOtp() ?? '';
        if (otp.isEmpty) return;
      }
      await widget.client.updateStatus(
        widget.session.token,
        assignment,
        status,
        otpCode: otp,
      );
      await _load();
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Status updated')));
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppErrorState.userMessage(error))));
      }
    }
  }

  Future<void> _setAvailability(String status) async {
    setState(() => _onlineUpdating = true);
    try {
      var latitude = 0.0;
      var longitude = 0.0;
      if (status == 'online') {
        final serviceEnabled = await Geolocator.isLocationServiceEnabled();
        if (serviceEnabled) {
          var permission = await Geolocator.checkPermission();
          if (permission == LocationPermission.denied) {
            permission = await Geolocator.requestPermission();
          }
          if (permission != LocationPermission.denied &&
              permission != LocationPermission.deniedForever) {
            final position = await Geolocator.getCurrentPosition(
              locationSettings: const LocationSettings(
                accuracy: LocationAccuracy.high,
                timeLimit: Duration(seconds: 10),
              ),
            );
            latitude = position.latitude;
            longitude = position.longitude;
          }
        }
      }
      await widget.client.updateLocation(
        widget.session.token,
        latitude: latitude,
        longitude: longitude,
        availabilityStatus: status,
      );
      _online = status == 'online';
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(
                status == 'online' ? 'You are online' : 'You are offline')));
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(AppErrorState.userMessage(error))));
      }
    } finally {
      if (mounted) setState(() => _onlineUpdating = false);
    }
  }

  Future<void> _pushCurrentLocation() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 8),
        ),
      );
      await widget.client.updateLocation(
        widget.session.token,
        latitude: position.latitude,
        longitude: position.longitude,
        availabilityStatus: 'online',
      );
    } catch (_) {
      // A transient GPS miss should not interrupt the driver's active screen.
    }
  }

  Future<String?> _askOtp() async {
    final controller = TextEditingController();
    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Enter ride OTP'),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          maxLength: 6,
          decoration: const InputDecoration(labelText: 'Customer OTP'),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () =>
                  Navigator.of(context).pop(controller.text.trim()),
              child: const Text('Start')),
        ],
      ),
    );
    controller.dispose();
    return result;
  }
}

class _WorkerHeader extends StatelessWidget {
  const _WorkerHeader({required this.worker, required this.count});
  final WorkerProfile worker;
  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
            colors: [Color(0xFF0D6B5F), Color(0xFF2F6BF0)]),
      ),
      child: Row(
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .18),
                borderRadius: BorderRadius.circular(18)),
            child: const Icon(Icons.badge_outlined, color: Colors.white),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(worker.name,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w900)),
                Text('${worker.role.replaceAll('_', ' ')} • $count active',
                    style: const TextStyle(
                        color: Color(0xFFE2F2FF), fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AssignmentCard extends StatelessWidget {
  const _AssignmentCard({required this.assignment, required this.onStatus});
  final WorkerAssignment assignment;
  final ValueChanged<String> onStatus;

  @override
  Widget build(BuildContext context) {
    final statuses = switch (assignment.type) {
      'order' => ['processing', 'out_for_delivery', 'delivered'],
      'service_booking' => [
          'confirmed',
          'on_the_way',
          'in_progress',
          'completed'
        ],
      'taxi_ride' => assignment.isOffer
          ? ['accepted', 'declined']
          : switch (assignment.status) {
              'accepted' => ['arrived', 'cancelled'],
              'arrived' => ['started', 'cancelled'],
              'started' => ['completed'],
              _ => <String>[],
            },
      _ => <String>[],
    };
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                  child: Text(assignment.number,
                      style: TextStyle(
                          color: Theme.of(context).colorScheme.onSurface,
                          fontSize: 17,
                          fontWeight: FontWeight.w900))),
              Text('₹${assignment.amount.toStringAsFixed(0)}',
                  style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface,
                      fontWeight: FontWeight.w900)),
            ],
          ),
          const SizedBox(height: 6),
          Text(assignment.title,
              style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          Text('${assignment.customerName} • ${assignment.customerPhone}',
              style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Text(assignment.address,
              style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurfaceVariant)),
          if (assignment.dropAddress.isNotEmpty)
            Text('Drop: ${assignment.dropAddress}',
                style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurfaceVariant)),
          if (assignment.type == 'taxi_ride') ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              children: [
                OutlinedButton.icon(
                  onPressed: () => _openMap(
                    assignment.pickupLatitude,
                    assignment.pickupLongitude,
                  ),
                  icon: const Icon(Icons.navigation_outlined),
                  label: const Text('Pickup map'),
                ),
                if (!assignment.isOffer)
                  OutlinedButton.icon(
                    onPressed: () => _openMap(
                      assignment.dropLatitude,
                      assignment.dropLongitude,
                    ),
                    icon: const Icon(Icons.flag_outlined),
                    label: const Text('Drop map'),
                  ),
                OutlinedButton.icon(
                  onPressed: () => _call(assignment.customerPhone),
                  icon: const Icon(Icons.call_outlined),
                  label: const Text('Call'),
                ),
              ],
            ),
          ],
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              Chip(label: Text(assignment.status)),
              Chip(
                  label: Text(
                      '${assignment.paymentMethod} / ${assignment.paymentStatus}')),
              for (final status in statuses)
                OutlinedButton(
                  onPressed: assignment.status == status
                      ? null
                      : () => onStatus(status),
                  child: Text(status.replaceAll('_', ' ')),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _openMap(double latitude, double longitude) async {
    if (latitude == 0 || longitude == 0) return;
    final uri = Uri.parse(
      'https://www.google.com/maps/dir/?api=1&destination=$latitude,$longitude',
    );
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _call(String phone) async {
    if (phone.trim().isEmpty) return;
    await launchUrl(Uri(scheme: 'tel', path: phone.trim()));
  }
}
