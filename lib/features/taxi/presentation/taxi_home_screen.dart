import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/app_greeting.dart';
import '../../../core/app_theme.dart';
import '../../mart/data/mart_session_store.dart';
import '../../mart/domain/mart_models.dart';
import '../../zone/location_picker_screen.dart';
import '../../zone/zone_models.dart';
import '../../zone/zone_store.dart';
import '../data/taxi_api_client.dart';
import 'taxi_ride_details_screen.dart';

const _taxiBg = Color(0xFF0B0B0C);
const _taxiPanel = Color(0xFF171717);
const _taxiPanelSoft = Color(0xFF202020);
const _taxiLine = Color(0xFF2B2B2B);
const _taxiText = Color(0xFFF7F7F7);
const _taxiMuted = Color(0xFF9A9A9A);
const _taxiAccent = Color(0xFFC8FF4D);

class TaxiHomeScreen extends StatefulWidget {
  const TaxiHomeScreen({super.key});

  @override
  State<TaxiHomeScreen> createState() => _TaxiHomeScreenState();
}

class _TaxiHomeScreenState extends State<TaxiHomeScreen> {
  final _client = TaxiApiClient();
  final _noteController = TextEditingController();

  TaxiConfig? _config;
  TaxiVehicleType? _selectedType;
  TaxiQuote? _quote;
  List<TaxiRide> _rides = const [];
  MartCustomerSession _session = MartCustomerSession.guest();
  SelectedLocation? _pickup;
  SelectedLocation? _drop;
  String _paymentMethod = 'cash';
  bool _loading = true;
  bool _quoting = false;
  bool _booking = false;
  String? _error;
  late final Future<String> _accountNameFuture;
  bool get _routeReady => _pickup != null && _drop != null;
  TaxiEstimate? get _estimate {
    final type = _selectedType;
    if (type == null) return null;
    for (final option in _quote?.options ?? const <TaxiEstimate>[]) {
      if (option.vehicleTypeId == type.id) return option;
    }
    return null;
  }

  @override
  void initState() {
    super.initState();
    _accountNameFuture = _taxiAccountName();
    _pickup = ZoneStore.instance.selectedLocation.value;
    _load();
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final canRequestRide = _session.isLoggedIn &&
        _routeReady &&
        _estimate?.available == true &&
        _selectedType != null &&
        !_booking &&
        !_quoting;

    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _taxiBg),
      body: _loading
          ? const AppSkeletonPage(layout: AppSkeletonLayout.detail)
          : _error != null
              ? AppErrorState(detail: _error, onRetry: _load)
              : RefreshIndicator(
                  onRefresh: _load,
                  color: _taxiAccent,
                  backgroundColor: adaptiveSurface(context, _taxiPanel),
                  child: SafeArea(
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
                      children: [
                        _TaxiTopBar(
                          accountNameFuture: _accountNameFuture,
                          onRefresh: _load,
                        ),
                        const SizedBox(height: 12),
                        _TaxiMapHero(
                          pickup: _pickup,
                          drop: _drop,
                          encodedPolyline: _quote?.encodedPolyline ?? '',
                          onPickupTap: () => _editLocation(
                            _TaxiLocationField.pickup,
                          ),
                        ),
                        const SizedBox(height: 10),
                        _InlineRouteInputs(
                          pickup: _pickup?.address ?? '',
                          drop: _drop?.address ?? '',
                          onPickupTap: () => _editLocation(
                            _TaxiLocationField.pickup,
                          ),
                          onDropTap: () => _editLocation(
                            _TaxiLocationField.drop,
                          ),
                        ),
                        if (_quoting) ...[
                          const SizedBox(height: 14),
                          const LinearProgressIndicator(color: _taxiAccent),
                        ],
                        const SizedBox(height: 16),
                        _VehicleSelector(
                          types: _config?.vehicleTypes ?? const [],
                          selected: _selectedType,
                          estimates: _quote?.options ?? const [],
                          currency: _config?.currencySymbol ?? '₹',
                          routeReady: _routeReady,
                          onSelected: (type) {
                            setState(() => _selectedType = type);
                          },
                        ),
                        const SizedBox(height: 14),
                        _routeReady
                            ? _TaxiRequestCard(
                                noteController: _noteController,
                                canRequestRide: canRequestRide,
                                booking: _booking,
                                estimate: _estimate,
                                currency: _config?.currencySymbol ?? '₹',
                                loggedIn: _session.isLoggedIn,
                                methods: _config?.paymentMethods ?? const [],
                                selectedPayment: _paymentMethod,
                                onPaymentChanged: (value) =>
                                    setState(() => _paymentMethod = value),
                                onBook: _bookRide,
                              )
                            : const Column(
                                children: [
                                  _RouteLockedCta(),
                                  SizedBox(height: 12),
                                  _TaxiPromoHint(),
                                ],
                              ),
                        const SizedBox(height: 24),
                        _RecentRides(
                          rides: _rides,
                          currency: _config?.currencySymbol ?? '₹',
                          onTap: _openRide,
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _session = await MartSessionStore().load();
      final config =
          await _client.config(zoneId: ZoneStore.instance.selectedZoneId);
      final rides = _session.isLoggedIn
          ? await _client.rides(authToken: _session.token)
          : const <TaxiRide>[];
      if (!mounted) return;
      setState(() {
        _config = config;
        _selectedType = _selectedType ??
            (config.vehicleTypes.isNotEmpty ? config.vehicleTypes.first : null);
        if (!config.paymentMethods.any((item) => item.id == _paymentMethod) &&
            config.paymentMethods.isNotEmpty) {
          _paymentMethod = config.paymentMethods.first.id;
        }
        _rides = rides;
      });
      if (_routeReady) {
        await _refreshQuote();
      }
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = AppErrorState.userMessage(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _refreshQuote() async {
    if (!_routeReady) {
      if (mounted) setState(() => _quote = null);
      return;
    }
    setState(() {
      _quoting = true;
      _quote = null;
    });
    try {
      final quote = await _client.quote(
        pickupLatitude: _pickup!.latitude,
        pickupLongitude: _pickup!.longitude,
        pickupAddress: _pickup!.address,
        dropLatitude: _drop!.latitude,
        dropLongitude: _drop!.longitude,
        dropAddress: _drop!.address,
        authToken: _session.token,
      );
      if (!mounted) return;
      setState(() {
        _quote = quote;
        final currentAvailable = quote.options.any((item) =>
            item.vehicleTypeId == _selectedType?.id && item.available);
        if (!currentAvailable) {
          final available = quote.options.where((item) => item.available);
          final first = available.isEmpty ? null : available.first;
          if (first != null) {
            final matching = _config?.vehicleTypes
                    .where((item) => item.id == first.vehicleTypeId)
                    .toList() ??
                const <TaxiVehicleType>[];
            _selectedType = matching.isEmpty ? null : matching.first;
          }
        }
      });
    } catch (error) {
      if (mounted) _snack(AppErrorState.userMessage(error));
    } finally {
      if (mounted) setState(() => _quoting = false);
    }
  }

  Future<void> _bookRide() async {
    final type = _selectedType;
    if (type == null) {
      _snack('No cab type available from admin.');
      return;
    }
    if (!_session.isLoggedIn) {
      _snack('Sign in from My Account before requesting a ride.');
      return;
    }
    if (!_routeReady || _quote == null || _estimate == null) {
      _snack('Pickup and drop are required.');
      return;
    }
    setState(() => _booking = true);
    try {
      final response = await _client.book({
        'quote_token': _quote!.token,
        'vehicle_type_id': type.id,
        'payment_method': _paymentMethod,
        'customer_note': _noteController.text.trim(),
      }, authToken: _session.token);
      final rideId = int.tryParse(response['ride_id']?.toString() ?? '') ?? 0;
      setState(() {
        _drop = null;
        _quote = null;
      });
      _noteController.clear();
      await _load();
      if (rideId > 0 && mounted) {
        await _openRide(TaxiRide(
          id: rideId,
          rideNumber: response['ride_number']?.toString() ?? '',
          pickupAddress: _pickup?.address ?? '',
          dropAddress: '',
          status: 'requested',
          amount: _estimate?.estimatedFare ?? 0,
          vehicleTypeName: type.name,
          driverName: '',
          driverPhone: '',
          vehicleName: '',
          vehicleNumber: '',
          pickupLatitude: _pickup?.latitude ?? 0,
          pickupLongitude: _pickup?.longitude ?? 0,
          dropLatitude: 0,
          dropLongitude: 0,
          driverLatitude: 0,
          driverLongitude: 0,
          otpCode: response['otp_code']?.toString() ?? '',
          routePolyline: '',
          paymentMethod: _paymentMethod,
          paymentStatus: _paymentMethod == 'wallet' ? 'paid' : 'unpaid',
          customerRating: 0,
          createdAt: '',
        ));
      }
    } catch (error) {
      _snack(AppErrorState.userMessage(error));
    } finally {
      if (mounted) setState(() => _booking = false);
    }
  }

  Future<void> _editLocation(_TaxiLocationField field) async {
    final isPickup = field == _TaxiLocationField.pickup;
    final result = await Navigator.of(context).push<SelectedLocation>(
      MaterialPageRoute(
        builder: (_) => LocationPickerScreen(
          title: isPickup ? 'Choose pickup' : 'Where to?',
          confirmLabel: isPickup ? 'Confirm Pickup' : 'Confirm Destination',
          initialLocation: isPickup ? _pickup : _drop ?? _pickup,
          requireServiceZone: isPickup,
          persistAsDeliveryAddress: false,
        ),
      ),
    );
    if (result == null || !mounted) return;
    setState(() {
      if (isPickup) {
        _pickup = result;
      } else {
        _drop = result;
      }
      _quote = null;
    });
    await _refreshQuote();
  }

  Future<void> _openRide(TaxiRide ride) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => TaxiRideDetailsScreen(
          rideId: ride.id,
          authToken: _session.token,
          client: _client,
          currency: _config?.currencySymbol ?? '₹',
        ),
      ),
    );
    if (mounted) await _load();
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }
}

enum _TaxiLocationField { pickup, drop }

Future<String> _taxiAccountName() async {
  final session = await MartSessionStore().load();
  return session.name.trim().isEmpty ? 'Customer' : session.name.trim();
}

class _TaxiTopBar extends StatelessWidget {
  const _TaxiTopBar({
    required this.accountNameFuture,
    required this.onRefresh,
  });

  final Future<String> accountNameFuture;
  final VoidCallback onRefresh;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Row(
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: colors.card,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: colors.line),
          ),
          clipBehavior: Clip.antiAlias,
          child: Icon(Icons.person_rounded, color: colors.muted, size: 19),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: FutureBuilder<String>(
            future: accountNameFuture,
            builder: (context, snapshot) => Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  appGreeting(),
                  style: TextStyle(
                    color: colors.muted,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Text(
                  snapshot.data ?? 'Customer',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.text,
                    fontSize: 14,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
        ),
        _RoundDarkButton(
          tooltip: 'Refresh',
          icon: Icons.refresh_rounded,
          onTap: onRefresh,
        ),
        const SizedBox(width: 8),
        _RoundDarkButton(
          tooltip: 'Menu',
          icon: Icons.menu_rounded,
          onTap: () {},
        ),
      ],
    );
  }
}

class _RoundDarkButton extends StatelessWidget {
  const _RoundDarkButton({
    required this.tooltip,
    required this.icon,
    required this.onTap,
  });

  final String tooltip;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Material(
      color: colors.card,
      borderRadius: BorderRadius.circular(999),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Tooltip(
          message: tooltip,
          child: SizedBox(
            width: 44,
            height: 44,
            child: Icon(icon, color: colors.text, size: 21),
          ),
        ),
      ),
    );
  }
}

class _TaxiMapHero extends StatelessWidget {
  const _TaxiMapHero({
    required this.pickup,
    required this.drop,
    required this.encodedPolyline,
    required this.onPickupTap,
  });

  final SelectedLocation? pickup;
  final SelectedLocation? drop;
  final String encodedPolyline;
  final VoidCallback onPickupTap;

  @override
  Widget build(BuildContext context) {
    final center = pickup ?? drop;
    final centerPoint = LatLng(
      center?.latitude ?? 26.8467,
      center?.longitude ?? 80.9462,
    );
    final markers = <Marker>{
      if (pickup != null)
        Marker(
          markerId: const MarkerId('taxi_pickup'),
          position: LatLng(pickup!.latitude, pickup!.longitude),
          infoWindow: const InfoWindow(title: 'Pickup'),
          icon:
              BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        ),
      if (drop != null)
        Marker(
          markerId: const MarkerId('taxi_drop'),
          position: LatLng(drop!.latitude, drop!.longitude),
          infoWindow: const InfoWindow(title: 'Destination'),
        ),
    };
    final routePoints = _decodePolyline(encodedPolyline);
    return Container(
      height: 214,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _taxiPanel),
        borderRadius: BorderRadius.circular(2),
        border: Border.all(color: _taxiLine),
      ),
      child: Stack(
        children: [
          Positioned.fill(
            child: GoogleMap(
              key: ValueKey(
                '${pickup?.latitude},${pickup?.longitude}:${drop?.latitude},${drop?.longitude}:$encodedPolyline',
              ),
              initialCameraPosition:
                  CameraPosition(target: centerPoint, zoom: 13),
              markers: markers,
              polylines: routePoints.isEmpty
                  ? const <Polyline>{}
                  : {
                      Polyline(
                        polylineId: const PolylineId('taxi_route'),
                        points: routePoints,
                        color: _taxiAccent,
                        width: 6,
                      ),
                    },
              myLocationEnabled: true,
              myLocationButtonEnabled: false,
              zoomControlsEnabled: false,
              mapToolbarEnabled: false,
              liteModeEnabled: false,
            ),
          ),
          Positioned(
            right: 14,
            bottom: 14,
            child: _RoundDarkButton(
              tooltip: 'Use current location',
              icon: Icons.my_location_rounded,
              onTap: onPickupTap,
            ),
          ),
        ],
      ),
    );
  }
}

List<LatLng> _decodePolyline(String encoded) {
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

class _InlineRouteInputs extends StatelessWidget {
  const _InlineRouteInputs({
    required this.pickup,
    required this.drop,
    required this.onPickupTap,
    required this.onDropTap,
  });

  final String pickup;
  final String drop;
  final VoidCallback onPickupTap;
  final VoidCallback onDropTap;

  @override
  Widget build(BuildContext context) {
    final text = adaptiveText(context, _taxiText);
    final line = adaptiveLine(context, _taxiLine);
    return Column(
      children: [
        _TaxiInputPill(
          dotColor: _taxiAccent,
          text: pickup.trim().isEmpty ? 'Current location' : pickup.trim(),
          placeholder: false,
          onTap: onPickupTap,
        ),
        Container(
          height: 14,
          alignment: Alignment.centerLeft,
          padding: const EdgeInsets.only(left: 25),
          child: Container(width: 2, color: line),
        ),
        _TaxiInputPill(
          dotColor: text,
          text: drop.trim().isEmpty ? 'Where to?' : drop.trim(),
          placeholder: drop.trim().isEmpty,
          onTap: onDropTap,
        ),
      ],
    );
  }
}

class _TaxiInputPill extends StatelessWidget {
  const _TaxiInputPill({
    required this.dotColor,
    required this.text,
    required this.placeholder,
    required this.onTap,
  });

  final Color dotColor;
  final String text;
  final bool placeholder;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Material(
      color: colors.surface,
      borderRadius: BorderRadius.circular(13),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(13),
        child: Container(
          constraints: const BoxConstraints(minHeight: 48),
          padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: colors.line),
          ),
          child: Row(
            children: [
              Container(
                width: 8,
                height: 8,
                decoration:
                    BoxDecoration(color: dotColor, shape: BoxShape.circle),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  text,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: placeholder ? colors.muted : colors.text,
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RouteLockedCta extends StatelessWidget {
  const _RouteLockedCta();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Container(
      height: 58,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.location_on_outlined, color: colors.muted, size: 18),
          const SizedBox(width: 8),
          Text(
            'Enter a destination to see price',
            style: TextStyle(
              color: colors.muted,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _TaxiPromoHint extends StatelessWidget {
  const _TaxiPromoHint();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          const Icon(Icons.sell_outlined, color: _taxiAccent, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Have a promo code? Add it after selecting your destination.',
              style: TextStyle(
                color: colors.muted,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                height: 1.3,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _VehicleSelector extends StatelessWidget {
  const _VehicleSelector(
      {required this.types,
      required this.selected,
      required this.estimates,
      required this.currency,
      required this.routeReady,
      required this.onSelected});

  final List<TaxiVehicleType> types;
  final TaxiVehicleType? selected;
  final List<TaxiEstimate> estimates;
  final String currency;
  final bool routeReady;
  final ValueChanged<TaxiVehicleType> onSelected;

  @override
  Widget build(BuildContext context) {
    final muted = adaptiveMuted(context, _taxiMuted);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'CHOOSE RIDE',
          style: TextStyle(
            color: muted,
            fontSize: 11,
            fontWeight: FontWeight.w900,
            letterSpacing: .6,
          ),
        ),
        const SizedBox(height: 10),
        if (types.isEmpty)
          const _RestaurantStyleEmptyTaxi()
        else
          for (final type in types) ...[
            Builder(builder: (context) {
              TaxiEstimate? estimate;
              for (final item in estimates) {
                if (item.vehicleTypeId == type.id) estimate = item;
              }
              return _RideOptionTile(
                type: type,
                selected: selected?.id == type.id,
                routeReady: routeReady,
                estimate: estimate,
                currency: currency,
                onTap: estimate?.available == false
                    ? () {}
                    : () => onSelected(type),
              );
            }),
            if (type != types.last) const SizedBox(height: 10),
          ],
      ],
    );
  }
}

class _RideOptionTile extends StatelessWidget {
  const _RideOptionTile({
    required this.type,
    required this.selected,
    required this.routeReady,
    required this.estimate,
    required this.currency,
    required this.onTap,
  });

  final TaxiVehicleType type;
  final bool selected;
  final bool routeReady;
  final TaxiEstimate? estimate;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    final bg = selected ? _taxiAccent : colors.surface;
    final fg = selected ? Colors.black : colors.text;
    final sub = selected ? Colors.black.withValues(alpha: .62) : colors.muted;
    return Material(
      color: bg,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          constraints: const BoxConstraints(minHeight: 64),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: selected ? _taxiAccent : colors.line),
          ),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: selected ? Colors.black : colors.card,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  type.seats >= 6
                      ? Icons.groups_rounded
                      : type.seats <= 2
                          ? Icons.two_wheeler_rounded
                          : Icons.local_taxi_rounded,
                  color: selected ? _taxiAccent : colors.text,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(type.name,
                        style: TextStyle(
                            color: fg,
                            fontSize: 13,
                            fontWeight: FontWeight.w900)),
                    const SizedBox(height: 2),
                    Text(
                      '${type.seats} seats • ${routeReady && estimate != null ? estimate!.available ? '${estimate!.pickupEtaMinutes ?? '--'} min pickup' : 'No drivers nearby' : 'Add drop-off'}',
                      style: TextStyle(
                        color: sub,
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.location_on_outlined, color: sub, size: 14),
                  const SizedBox(width: 4),
                  Text(
                    routeReady && estimate != null
                        ? '$currency${estimate!.estimatedFare.toStringAsFixed(0)}'
                        : 'Add drop-off',
                    style: TextStyle(
                      color: sub,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RestaurantStyleEmptyTaxi extends StatelessWidget {
  const _RestaurantStyleEmptyTaxi();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: colors.line),
      ),
      child: Text(
        'No active vehicle types. Add taxi vehicle types from admin.',
        style: TextStyle(color: colors.muted, fontWeight: FontWeight.w800),
      ),
    );
  }
}

class _TaxiRequestCard extends StatelessWidget {
  const _TaxiRequestCard({
    required this.noteController,
    required this.canRequestRide,
    required this.booking,
    required this.estimate,
    required this.currency,
    required this.loggedIn,
    required this.methods,
    required this.selectedPayment,
    required this.onPaymentChanged,
    required this.onBook,
  });

  final TextEditingController noteController;
  final bool canRequestRide;
  final bool booking;
  final TaxiEstimate? estimate;
  final String currency;
  final bool loggedIn;
  final List<TaxiPaymentMethod> methods;
  final String selectedPayment;
  final ValueChanged<String> onPaymentChanged;
  final VoidCallback onBook;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Column(
        children: [
          Row(
            children: [
              const Icon(Icons.receipt_long_outlined,
                  color: _taxiAccent, size: 20),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  estimate == null
                      ? 'Select a ride to see fare'
                      : 'Estimated fare $currency${estimate!.estimatedFare.toStringAsFixed(0)}',
                  style: TextStyle(
                    color: colors.text,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (!loggedIn) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colors.card,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                'Sign in from My Account to request and track a ride.',
                style:
                    TextStyle(color: colors.text, fontWeight: FontWeight.w800),
              ),
            ),
            const SizedBox(height: 10),
          ],
          if (methods.isNotEmpty) ...[
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: methods
                  .map((method) => ChoiceChip(
                        label: Text(method.label),
                        selected: selectedPayment == method.id,
                        onSelected: (_) => onPaymentChanged(method.id),
                      ))
                  .toList(),
            ),
            const SizedBox(height: 10),
          ],
          TextField(
            controller: noteController,
            style: TextStyle(color: colors.text),
            decoration: const InputDecoration(
              prefixIcon: Icon(Icons.sell_outlined),
              labelText: 'Promo code or ride note optional',
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 54,
            width: double.infinity,
            child: FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: _taxiAccent,
                foregroundColor: Colors.black,
                disabledBackgroundColor: colors.card,
                disabledForegroundColor: colors.muted,
              ),
              onPressed: canRequestRide ? onBook : null,
              icon: booking
                  ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                  : const Icon(Icons.local_taxi_rounded),
              label: Text(booking ? 'Requesting ride...' : 'Request Ride'),
            ),
          ),
        ],
      ),
    );
  }
}

class _RecentRides extends StatelessWidget {
  const _RecentRides({
    required this.rides,
    required this.currency,
    required this.onTap,
  });
  final List<TaxiRide> rides;
  final String currency;
  final ValueChanged<TaxiRide> onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    if (rides.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: colors.surface,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: colors.line),
        ),
        child: Column(
          children: [
            Icon(Icons.local_taxi_outlined, color: colors.muted, size: 42),
            const SizedBox(height: 12),
            Text(
              'No taxi rides yet',
              style: TextStyle(
                color: colors.text,
                fontSize: 16,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Your cab bookings will appear here.',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: colors.muted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Recent rides',
            style: TextStyle(
                color: colors.text, fontSize: 18, fontWeight: FontWeight.w900)),
        const SizedBox(height: 12),
        for (final ride in rides.take(5)) ...[
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: () => onTap(ride),
              borderRadius: BorderRadius.circular(24),
              child: _Panel(
                child: Row(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                          color: colors.card,
                          borderRadius: BorderRadius.circular(16)),
                      child: const Icon(Icons.local_taxi_rounded,
                          color: _taxiAccent),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(ride.rideNumber,
                              style: TextStyle(
                                  color: colors.text,
                                  fontWeight: FontWeight.w900)),
                          Text('${ride.pickupAddress} → ${ride.dropAddress}',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style:
                                  TextStyle(color: colors.muted, fontSize: 12)),
                          if (ride.driverName.isNotEmpty)
                            Text(
                              'Driver: ${ride.driverName}${ride.vehicleNumber.isNotEmpty ? ' • ${ride.vehicleNumber}' : ''}',
                              style:
                                  TextStyle(color: colors.muted, fontSize: 12),
                            ),
                          if (ride.driverLatitude != 0 &&
                              ride.driverLongitude != 0)
                            Text(
                              'Live: ${ride.driverLatitude.toStringAsFixed(4)}, ${ride.driverLongitude.toStringAsFixed(4)}',
                              style: const TextStyle(
                                  color: AppTheme.success,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800),
                            ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('$currency${ride.amount.toStringAsFixed(0)}',
                            style: TextStyle(
                                color: colors.text,
                                fontWeight: FontWeight.w900)),
                        Text(ride.status,
                            style: const TextStyle(
                                color: _taxiAccent,
                                fontSize: 12,
                                fontWeight: FontWeight.w900)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),
        ],
      ],
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({required this.child});
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _taxiBg,
      darkSurface: _taxiPanel,
      darkCard: _taxiPanelSoft,
      darkLine: _taxiLine,
      darkText: _taxiText,
      darkMuted: _taxiMuted,
    );
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: colors.line),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: .22),
              blurRadius: 18,
              offset: const Offset(0, 8))
        ],
      ),
      child: child,
    );
  }
}
