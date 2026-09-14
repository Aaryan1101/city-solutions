import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/app_theme.dart';
import '../data/taxi_api_client.dart';

class TaxiRideDetailsScreen extends StatefulWidget {
  const TaxiRideDetailsScreen({
    super.key,
    required this.rideId,
    required this.authToken,
    required this.client,
    required this.currency,
  });

  final int rideId;
  final String authToken;
  final TaxiApiClient client;
  final String currency;

  @override
  State<TaxiRideDetailsScreen> createState() => _TaxiRideDetailsScreenState();
}

class _TaxiRideDetailsScreenState extends State<TaxiRideDetailsScreen> {
  TaxiRideDetails? _details;
  Timer? _poller;
  bool _loading = true;
  bool _acting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
    _poller =
        Timer.periodic(const Duration(seconds: 8), (_) => _load(silent: true));
  }

  @override
  void dispose() {
    _poller?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ride = _details?.ride;
    return Scaffold(
      appBar: AppBar(title: const Text('Your ride')),
      body: _loading
          ? const AppSkeletonPage(layout: AppSkeletonLayout.detail)
          : _error != null
              ? AppErrorState(detail: _error, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
                    children: [
                      if (ride != null) ...[
                        _RideMap(ride: ride),
                        const SizedBox(height: 16),
                        _StatusCard(ride: ride, currency: widget.currency),
                        const SizedBox(height: 12),
                        _RouteCard(ride: ride),
                        if (ride.driverName.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          _DriverCard(
                            ride: ride,
                            onCall: _callDriver,
                          ),
                        ],
                        if (_details!.history.isNotEmpty) ...[
                          const SizedBox(height: 18),
                          Text('Trip timeline',
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(fontWeight: FontWeight.w900)),
                          const SizedBox(height: 10),
                          _HistoryCard(history: _details!.history),
                        ],
                        if (ride.canCancel) ...[
                          const SizedBox(height: 16),
                          OutlinedButton.icon(
                            onPressed: _acting ? null : _cancelRide,
                            icon: const Icon(Icons.cancel_outlined),
                            label: const Text('Cancel ride'),
                          ),
                        ],
                        if (ride.status == 'completed' &&
                            ride.customerRating == 0) ...[
                          const SizedBox(height: 16),
                          FilledButton.icon(
                            onPressed: _acting ? null : _rateRide,
                            icon: const Icon(Icons.star_outline_rounded),
                            label: const Text('Rate this ride'),
                          ),
                        ],
                      ],
                    ],
                  ),
                ),
    );
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent && mounted) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final details = await widget.client.ride(
        widget.rideId,
        authToken: widget.authToken,
      );
      if (!mounted) return;
      setState(() {
        _details = details;
        _error = null;
      });
      if (!details.ride.isActive) _poller?.cancel();
    } catch (error) {
      if (!mounted || silent) return;
      setState(() => _error = AppErrorState.userMessage(error));
    } finally {
      if (!silent && mounted) setState(() => _loading = false);
    }
  }

  Future<void> _callDriver() async {
    final phone = _details?.ride.driverPhone.trim() ?? '';
    if (phone.isEmpty) return;
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  Future<void> _cancelRide() async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel this ride?'),
        content: TextField(
          controller: controller,
          maxLength: 250,
          decoration: const InputDecoration(
            labelText: 'Reason',
            hintText: 'Tell us why you are cancelling',
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Keep ride')),
          FilledButton(
            onPressed: () => Navigator.pop(context, controller.text.trim()),
            child: const Text('Cancel ride'),
          ),
        ],
      ),
    );
    controller.dispose();
    if (reason == null) return;
    setState(() => _acting = true);
    try {
      await widget.client.cancel(
        widget.rideId,
        authToken: widget.authToken,
        reason: reason,
      );
      await _load(silent: true);
    } catch (error) {
      _snack(AppErrorState.userMessage(error));
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _rateRide() async {
    var rating = 5;
    final review = TextEditingController();
    final submitted = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('How was your ride?'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(
                  5,
                  (index) => IconButton(
                    onPressed: () => setDialogState(() => rating = index + 1),
                    icon: Icon(index < rating
                        ? Icons.star_rounded
                        : Icons.star_outline_rounded),
                    color: Colors.amber,
                  ),
                ),
              ),
              TextField(
                controller: review,
                maxLength: 500,
                decoration: const InputDecoration(labelText: 'Review optional'),
              ),
            ],
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Later')),
            FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Submit')),
          ],
        ),
      ),
    );
    if (submitted != true) {
      review.dispose();
      return;
    }
    setState(() => _acting = true);
    try {
      await widget.client.rate(
        widget.rideId,
        authToken: widget.authToken,
        rating: rating,
        review: review.text.trim(),
      );
      await _load(silent: true);
    } catch (error) {
      _snack(AppErrorState.userMessage(error));
    } finally {
      review.dispose();
      if (mounted) setState(() => _acting = false);
    }
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }
}

class _RideMap extends StatelessWidget {
  const _RideMap({required this.ride});
  final TaxiRide ride;

  @override
  Widget build(BuildContext context) {
    final pickup = LatLng(ride.pickupLatitude, ride.pickupLongitude);
    final validDriver = ride.driverLatitude != 0 && ride.driverLongitude != 0;
    final routePoints = _decodeRidePolyline(ride.routePolyline);
    return SizedBox(
      height: 260,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: GoogleMap(
          initialCameraPosition: CameraPosition(target: pickup, zoom: 13),
          myLocationButtonEnabled: false,
          zoomControlsEnabled: false,
          mapToolbarEnabled: false,
          markers: {
            Marker(
              markerId: const MarkerId('pickup'),
              position: pickup,
              infoWindow: const InfoWindow(title: 'Pickup'),
              icon: BitmapDescriptor.defaultMarkerWithHue(
                  BitmapDescriptor.hueGreen),
            ),
            Marker(
              markerId: const MarkerId('drop'),
              position: LatLng(ride.dropLatitude, ride.dropLongitude),
              infoWindow: const InfoWindow(title: 'Destination'),
            ),
            if (validDriver)
              Marker(
                markerId: const MarkerId('driver'),
                position: LatLng(ride.driverLatitude, ride.driverLongitude),
                infoWindow: const InfoWindow(title: 'Your driver'),
                icon: BitmapDescriptor.defaultMarkerWithHue(
                    BitmapDescriptor.hueAzure),
              ),
          },
          polylines: routePoints.isEmpty
              ? const <Polyline>{}
              : {
                  Polyline(
                    polylineId: const PolylineId('ride_route'),
                    points: routePoints,
                    color: AppTheme.primary,
                    width: 6,
                  ),
                },
        ),
      ),
    );
  }
}

List<LatLng> _decodeRidePolyline(String encoded) {
  if (encoded.isEmpty) return const [];
  final points = <LatLng>[];
  var index = 0;
  var latitude = 0;
  var longitude = 0;
  while (index < encoded.length) {
    var result = 0;
    var shift = 0;
    int byte;
    do {
      byte = encoded.codeUnitAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20 && index < encoded.length);
    latitude += (result & 1) != 0 ? ~(result >> 1) : result >> 1;
    result = 0;
    shift = 0;
    do {
      byte = encoded.codeUnitAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20 && index < encoded.length);
    longitude += (result & 1) != 0 ? ~(result >> 1) : result >> 1;
    points.add(LatLng(latitude / 1e5, longitude / 1e5));
  }
  return points;
}

class _StatusCard extends StatelessWidget {
  const _StatusCard({required this.ride, required this.currency});
  final TaxiRide ride;
  final String currency;

  @override
  Widget build(BuildContext context) {
    final waiting = ride.status == 'requested';
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(waiting ? Icons.radar_rounded : Icons.local_taxi_rounded,
                color: AppTheme.primary, size: 30),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    waiting
                        ? 'Finding a nearby driver'
                        : ride.status.replaceAll('_', ' ').toUpperCase(),
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                  Text('${ride.vehicleTypeName} · ${ride.rideNumber}'),
                ],
              ),
            ),
            Text('$currency${ride.amount.toStringAsFixed(0)}',
                style: const TextStyle(fontWeight: FontWeight.w900)),
          ],
        ),
      ),
    );
  }
}

class _RouteCard extends StatelessWidget {
  const _RouteCard({required this.ride});
  final TaxiRide ride;

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              _AddressRow(
                  icon: Icons.radio_button_checked_rounded,
                  color: Colors.green,
                  label: 'Pickup',
                  value: ride.pickupAddress),
              const Divider(height: 24),
              _AddressRow(
                  icon: Icons.location_on_rounded,
                  color: Colors.redAccent,
                  label: 'Destination',
                  value: ride.dropAddress),
            ],
          ),
        ),
      );
}

class _AddressRow extends StatelessWidget {
  const _AddressRow({
    required this.icon,
    required this.color,
    required this.label,
    required this.value,
  });
  final IconData icon;
  final Color color;
  final String label;
  final String value;
  @override
  Widget build(BuildContext context) => Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: Theme.of(context).textTheme.labelMedium),
                Text(value,
                    style: const TextStyle(fontWeight: FontWeight.w800)),
              ],
            ),
          ),
        ],
      );
}

class _DriverCard extends StatelessWidget {
  const _DriverCard({required this.ride, required this.onCall});
  final TaxiRide ride;
  final VoidCallback onCall;
  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              const CircleAvatar(child: Icon(Icons.person_rounded)),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(ride.driverName,
                        style: const TextStyle(fontWeight: FontWeight.w900)),
                    Text([ride.vehicleName, ride.vehicleNumber]
                        .where((item) => item.isNotEmpty)
                        .join(' · ')),
                    if (ride.otpCode.isNotEmpty &&
                        const ['accepted', 'arrived'].contains(ride.status))
                      Text('Start OTP: ${ride.otpCode}',
                          style: const TextStyle(
                              color: AppTheme.primary,
                              fontWeight: FontWeight.w900)),
                  ],
                ),
              ),
              IconButton(
                  tooltip: 'Call driver',
                  onPressed: onCall,
                  icon: const Icon(Icons.call_rounded)),
            ],
          ),
        ),
      );
}

class _HistoryCard extends StatelessWidget {
  const _HistoryCard({required this.history});
  final List<TaxiRideHistory> history;
  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: history
                .map((item) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.check_circle_rounded,
                          color: AppTheme.primary),
                      title: Text(item.status.replaceAll('_', ' '),
                          style: const TextStyle(fontWeight: FontWeight.w900)),
                      subtitle: Text([
                        if (item.note.isNotEmpty) item.note,
                        if (item.actorName.isNotEmpty) item.actorName,
                        item.createdAt,
                      ].join('\n')),
                    ))
                .toList(),
          ),
        ),
      );
}
