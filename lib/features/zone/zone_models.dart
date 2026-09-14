class CityZone {
  const CityZone({
    required this.id,
    required this.name,
    required this.city,
    required this.state,
    required this.pincode,
    required this.latitude,
    required this.longitude,
    required this.radiusKm,
  });

  final int id;
  final String name;
  final String city;
  final String state;
  final String pincode;
  final double latitude;
  final double longitude;
  final double radiusKm;

  String get label {
    final parts = [city, state].where((part) => part.trim().isNotEmpty);
    final suffix = parts.join(', ');
    return suffix.isEmpty ? name : '$name - $suffix';
  }

  factory CityZone.fromJson(Map<String, dynamic> json) {
    return CityZone(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      state: json['state']?.toString() ?? '',
      pincode: json['pincode']?.toString() ?? '',
      latitude: _double(json['latitude']),
      longitude: _double(json['longitude']),
      radiusKm: _double(json['radius_km']),
    );
  }
}

class SelectedLocation {
  const SelectedLocation({
    required this.address,
    required this.latitude,
    required this.longitude,
    required this.zone,
  });

  final String address;
  final double latitude;
  final double longitude;
  final CityZone zone;

  String get shortAddress {
    final parts = address.split(',');
    if (parts.length <= 2) return address;
    return '${parts.first.trim()}, ${parts[1].trim()}';
  }
}

int _int(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _double(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
