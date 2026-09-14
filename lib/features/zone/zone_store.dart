import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'zone_models.dart';

class ZoneStore {
  ZoneStore._();

  static final ZoneStore instance = ZoneStore._();

  static const _idKey = 'city_selected_zone_id';
  static const _nameKey = 'city_selected_zone_name';
  static const _addressKey = 'city_selected_address';
  static const _latitudeKey = 'city_selected_latitude';
  static const _longitudeKey = 'city_selected_longitude';

  final ValueNotifier<CityZone?> selectedZone = ValueNotifier<CityZone?>(null);
  final ValueNotifier<SelectedLocation?> selectedLocation =
      ValueNotifier<SelectedLocation?>(null);
  bool _loaded = false;

  int? get selectedZoneId {
    final id = selectedZone.value?.id ?? 0;
    return id > 0 ? id : null;
  }

  Future<void> load() async {
    if (_loaded) return;
    _loaded = true;
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt(_idKey) ?? 0;
    final name = prefs.getString(_nameKey) ?? '';
    if (id > 0 && name.trim().isNotEmpty) {
      selectedZone.value = CityZone(
        id: id,
        name: name,
        city: '',
        state: '',
        pincode: '',
        latitude: 0,
        longitude: 0,
        radiusKm: 0,
      );
      final address = prefs.getString(_addressKey) ?? '';
      final latitude = prefs.getDouble(_latitudeKey) ?? 0;
      final longitude = prefs.getDouble(_longitudeKey) ?? 0;
      if (address.trim().isNotEmpty && latitude != 0 && longitude != 0) {
        selectedLocation.value = SelectedLocation(
          address: address,
          latitude: latitude,
          longitude: longitude,
          zone: selectedZone.value!,
        );
      }
    }
  }

  Future<void> saveLocation(SelectedLocation location) async {
    selectedZone.value = location.zone;
    selectedLocation.value = location;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_idKey, location.zone.id);
    await prefs.setString(_nameKey, location.zone.name);
    await prefs.setString(_addressKey, location.address);
    await prefs.setDouble(_latitudeKey, location.latitude);
    await prefs.setDouble(_longitudeKey, location.longitude);
  }

  Future<void> clear() async {
    selectedZone.value = null;
    selectedLocation.value = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_idKey);
    await prefs.remove(_nameKey);
    await prefs.remove(_addressKey);
    await prefs.remove(_latitudeKey);
    await prefs.remove(_longitudeKey);
  }
}
