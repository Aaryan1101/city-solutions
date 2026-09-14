import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../core/app_theme.dart';
import 'zone_api_client.dart';
import 'zone_models.dart';
import 'zone_store.dart';

class LocationPickerScreen extends StatefulWidget {
  const LocationPickerScreen({
    super.key,
    this.title = 'Select Location',
    this.confirmLabel = 'Confirm Address',
    this.initialLocation,
    this.requireServiceZone = true,
    this.persistAsDeliveryAddress = true,
  });

  final String title;
  final String confirmLabel;
  final SelectedLocation? initialLocation;
  final bool requireServiceZone;
  final bool persistAsDeliveryAddress;

  @override
  State<LocationPickerScreen> createState() => _LocationPickerScreenState();
}

class _LocationPickerScreenState extends State<LocationPickerScreen> {
  static const _fallback = LatLng(26.8467, 80.9462);

  final _api = ZoneApiClient();
  GoogleMapController? _mapController;
  LatLng _selected = _fallback;
  bool _loadingLocation = true;
  bool _saving = false;
  String _address = 'Move the map or tap a location';
  String? _error;

  @override
  void initState() {
    super.initState();
    final initial =
        widget.initialLocation ?? ZoneStore.instance.selectedLocation.value;
    _selected = LatLng(
      initial?.latitude ?? _fallback.latitude,
      initial?.longitude ?? _fallback.longitude,
    );
    _address = initial?.address ?? _address;
    if (initial == null) {
      _loadCurrentLocation();
    } else {
      _loadingLocation = false;
    }
  }

  @override
  void dispose() {
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          IconButton(
            tooltip: 'Search address',
            onPressed: _searchAddress,
            icon: const Icon(Icons.search_rounded),
          ),
        ],
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: _selected, zoom: 15),
            myLocationButtonEnabled: false,
            myLocationEnabled: true,
            zoomControlsEnabled: false,
            onMapCreated: (controller) => _mapController = controller,
            onTap: _setSelected,
            markers: {
              Marker(
                markerId: const MarkerId('selected_location'),
                position: _selected,
                draggable: true,
                onDragEnd: _setSelected,
              ),
            },
          ),
          Positioned(
            right: 16,
            top: 16,
            child: FloatingActionButton.small(
              heroTag: 'current_location',
              onPressed: _loadCurrentLocation,
              child: _loadingLocation
                  ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                  : const Icon(Icons.my_location_rounded),
            ),
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 16,
            child: _LocationSheet(
              address: _address,
              resolvingAddress: _address == 'Resolving address...',
              error: _error,
              saving: _saving,
              confirmLabel: widget.confirmLabel,
              onConfirm: _confirm,
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _loadCurrentLocation() async {
    setState(() {
      _loadingLocation = true;
      _error = null;
    });
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() => _error = 'Turn on device location to use current GPS.');
        return;
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        setState(() => _error = 'Location permission is required.');
        return;
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 12),
        ),
      );
      await _setSelected(LatLng(position.latitude, position.longitude));
    } catch (error) {
      setState(() => _error = AppErrorState.userMessage(error,
          fallback: 'Location could not be resolved. Please try again.'));
    } finally {
      if (mounted) setState(() => _loadingLocation = false);
    }
  }

  Future<void> _setSelected(LatLng position) async {
    setState(() {
      _selected = position;
      _address = 'Resolving address...';
      _error = null;
    });
    await _mapController?.animateCamera(CameraUpdate.newLatLng(position));
    try {
      final geocode = await _api.reverseGeocode(
        latitude: position.latitude,
        longitude: position.longitude,
      );
      if (!mounted) return;
      setState(() => _address = geocode.address);
    } catch (_) {
      if (!mounted) return;
      setState(() => _address = 'Selected location');
    }
  }

  Future<void> _searchAddress() async {
    final controller = TextEditingController();
    var searching = false;
    var results = const <PlaceSearchResult>[];
    String? searchError;
    final selected = await showModalBottomSheet<PlaceSearchResult>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setSheetState) => Padding(
          padding: EdgeInsets.fromLTRB(
            16,
            16,
            16,
            16 + MediaQuery.viewInsetsOf(context).bottom,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: controller,
                autofocus: true,
                textInputAction: TextInputAction.search,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search_rounded),
                  hintText: 'Search area, landmark or address',
                ),
                onSubmitted: (query) async {
                  setSheetState(() {
                    searching = true;
                    searchError = null;
                  });
                  try {
                    final found = await _api.searchAddress(query);
                    setSheetState(() => results = found);
                  } catch (error) {
                    setSheetState(() => searchError =
                        'Address search is unavailable. You can still place the pin on the map.');
                  } finally {
                    setSheetState(() => searching = false);
                  }
                },
              ),
              if (searching) ...[
                const SizedBox(height: 14),
                const LinearProgressIndicator(),
              ],
              if (searchError != null) ...[
                const SizedBox(height: 12),
                Text(searchError!,
                    style:
                        TextStyle(color: Theme.of(context).colorScheme.error)),
              ],
              if (results.isNotEmpty)
                Flexible(
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: results.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) => ListTile(
                      leading: const Icon(Icons.location_on_outlined),
                      title: Text(results[index].address),
                      onTap: () => Navigator.pop(context, results[index]),
                    ),
                  ),
                ),
              if (!searching && results.isEmpty && searchError == null) ...[
                const SizedBox(height: 12),
                const Text(
                    'Enter at least three characters, then press search.'),
              ],
            ],
          ),
        ),
      ),
    );
    controller.dispose();
    if (selected == null || !mounted) return;
    await _setSelected(LatLng(selected.latitude, selected.longitude));
  }

  Future<void> _confirm() async {
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      var geocode = const ReverseGeocodeResult(
        address: 'Selected location',
        pincode: '',
        city: '',
      );
      try {
        geocode = await _api.reverseGeocode(
          latitude: _selected.latitude,
          longitude: _selected.longitude,
        );
      } catch (_) {
        // Google geocoding is only used for address text/pincode hints. Zone
        // matching still works from latitude/longitude through our backend.
      }
      final zone = await _api.resolveZone(
        latitude: _selected.latitude,
        longitude: _selected.longitude,
        pincode: geocode.pincode,
        city: geocode.city,
      );
      if (zone == null && widget.requireServiceZone) {
        setState(() {
          _address = geocode.address;
          _error = 'This address is outside active service zones.';
        });
        return;
      }
      final fallbackZone = widget.initialLocation?.zone ??
          ZoneStore.instance.selectedLocation.value?.zone;
      if (zone == null && fallbackZone == null) {
        setState(() =>
            _error = 'Select a service area before choosing this location.');
        return;
      }
      final location = SelectedLocation(
        address: geocode.address,
        latitude: _selected.latitude,
        longitude: _selected.longitude,
        zone: zone ?? fallbackZone!,
      );
      if (widget.persistAsDeliveryAddress) {
        await ZoneStore.instance.saveLocation(location);
      }
      if (!mounted) return;
      Navigator.of(context).pop(location);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = AppErrorState.userMessage(error,
          fallback: 'Location could not be resolved. Please try again.'));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _LocationSheet extends StatelessWidget {
  const _LocationSheet({
    required this.address,
    required this.resolvingAddress,
    required this.error,
    required this.saving,
    required this.confirmLabel,
    required this.onConfirm,
  });

  final String address;
  final bool resolvingAddress;
  final String? error;
  final bool saving;
  final String confirmLabel;
  final VoidCallback onConfirm;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return DecoratedBox(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.18),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Delivery address',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.location_on_outlined,
                    color: AppTheme.primary, size: 22),
                const SizedBox(width: 8),
                Expanded(
                  child: resolvingAddress
                      ? const Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            AppSkeletonBox(height: 13, radius: 7),
                            SizedBox(height: 8),
                            FractionallySizedBox(
                              widthFactor: 0.72,
                              child: AppSkeletonBox(height: 11, radius: 7),
                            ),
                          ],
                        )
                      : Text(
                          address,
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                ),
              ],
            ),
            if (error != null) ...[
              const SizedBox(height: 10),
              Text(
                error!,
                style: const TextStyle(
                  color: Color(0xFFE53935),
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                onPressed: saving ? null : onConfirm,
                icon: saving
                    ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                    : const Icon(Icons.check_circle_outline),
                label: Text(saving ? 'Checking location...' : confirmLabel),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
