import 'dart:convert';
import 'dart:io';

import 'zone_models.dart';

class ZoneApiClient {
  ZoneApiClient({String? baseUrl})
      : baseUrl =
            (baseUrl ?? ZoneApiConfig.baseUrl).replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<List<CityZone>> fetchZones() async {
    final request = await _client.getUrl(Uri.parse('$baseUrl/zones'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    final response = await request.close();
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ZoneApiException(
        decoded is Map ? decoded['message']?.toString() ?? raw : raw,
      );
    }
    final data = decoded is Map ? decoded['data'] : null;
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => CityZone.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<CityZone?> resolveZone({
    required double latitude,
    required double longitude,
    String pincode = '',
    String city = '',
  }) async {
    final request = await _client.postUrl(Uri.parse('$baseUrl/zones/resolve'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.write(jsonEncode({
      'latitude': latitude,
      'longitude': longitude,
      'pincode': pincode,
      'city': city,
    }));
    final response = await request.close();
    final decoded = await _decode(response);
    final data = decoded['data'];
    final zone = data is Map ? data['zone'] : null;
    if (zone is Map) {
      return CityZone.fromJson(Map<String, dynamic>.from(zone));
    }
    return null;
  }

  Future<ReverseGeocodeResult> reverseGeocode({
    required double latitude,
    required double longitude,
  }) async {
    final request =
        await _client.postUrl(Uri.parse('$baseUrl/zones/reverse-geocode'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.write(jsonEncode({
      'latitude': latitude,
      'longitude': longitude,
    }));
    final response = await request.close();
    final decoded = await _decode(response);
    final data = decoded['data'];
    if (data is! Map) throw ZoneApiException('Address was not found.');
    return ReverseGeocodeResult(
      address: data['address']?.toString() ?? 'Selected location',
      pincode: data['pincode']?.toString() ?? '',
      city: data['city']?.toString() ?? '',
    );
  }

  Future<List<PlaceSearchResult>> searchAddress(String query) async {
    final clean = query.trim();
    if (clean.length < 3) return const [];
    final request = await _client.postUrl(Uri.parse('$baseUrl/zones/search'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.write(jsonEncode({'query': clean}));
    final response = await request.close();
    final decoded = await _decode(response);
    final results = decoded['data'];
    if (results is! List) return const [];
    return results
        .whereType<Map>()
        .map((raw) {
          final item = Map<String, dynamic>.from(raw);
          return PlaceSearchResult(
            address: item['address']?.toString() ?? clean,
            latitude: double.tryParse(item['latitude']?.toString() ?? '') ?? 0,
            longitude:
                double.tryParse(item['longitude']?.toString() ?? '') ?? 0,
          );
        })
        .where((item) => item.latitude != 0 && item.longitude != 0)
        .toList();
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ZoneApiException(
        decoded is Map ? decoded['message']?.toString() ?? raw : raw,
      );
    }
    return decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : <String, dynamic>{};
  }
}

class ZoneApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'CITY_API_BASE_URL',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1',
  );
}

class ReverseGeocodeResult {
  const ReverseGeocodeResult({
    required this.address,
    required this.pincode,
    required this.city,
  });

  final String address;
  final String pincode;
  final String city;
}

class PlaceSearchResult {
  const PlaceSearchResult({
    required this.address,
    required this.latitude,
    required this.longitude,
  });
  final String address;
  final double latitude;
  final double longitude;
}

class ZoneApiException implements Exception {
  ZoneApiException(this.message);
  final String message;
  @override
  String toString() => message;
}
