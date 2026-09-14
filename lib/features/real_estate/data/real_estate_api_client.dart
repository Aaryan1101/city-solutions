import 'dart:convert';
import 'dart:io';

import '../domain/real_estate_models.dart';

class RealEstateApiClient {
  RealEstateApiClient({String? baseUrl})
      : baseUrl = (baseUrl ?? RealEstateApiConfig.baseUrl)
            .replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<RealEstateHomeData> fetchHome() async {
    final response = await _get(_withZone('/home'));
    return RealEstateHomeData.fromJson(response);
  }

  Future<RealEstateDetails> fetchProperty(int propertyId) async {
    final response = await _get(_withZone('/properties/$propertyId'));
    return RealEstateDetails.fromJson(response);
  }

  Future<RealEstateProjectDetails> fetchProject(int projectId) async {
    final response = await _get(_withZone('/projects/$projectId'));
    return RealEstateProjectDetails.fromJson(response);
  }

  Future<RealEstateAgentProfile> fetchAgent(int agentId) async {
    final response = await _get(_withZone('/agents/$agentId'));
    return RealEstateAgentProfile.fromJson(response);
  }

  Future<List<RealEstateProperty>> searchProperties({
    String query = '',
    String listingPurpose = '',
    String propertyCategory = '',
    int bedrooms = 0,
    double minPrice = 0,
    double maxPrice = 0,
    double minArea = 0,
    String furnishing = '',
    bool verifiedOnly = false,
  }) async {
    final params = <String, String>{
      if (query.trim().isNotEmpty) 'query': query.trim(),
      if (listingPurpose.trim().isNotEmpty)
        'listing_purpose': listingPurpose.trim(),
      if (propertyCategory.trim().isNotEmpty)
        'property_category': propertyCategory.trim(),
      if (bedrooms > 0) 'bedrooms': bedrooms.toString(),
      if (minPrice > 0) 'min_price': minPrice.toStringAsFixed(0),
      if (maxPrice > 0) 'max_price': maxPrice.toStringAsFixed(0),
      if (minArea > 0) 'min_area': minArea.toStringAsFixed(0),
      if (furnishing.trim().isNotEmpty) 'furnishing': furnishing.trim(),
      if (verifiedOnly) 'verified_only': '1',
      if ((RealEstateZoneSelection.currentZoneId ?? 0) > 0)
        'zone_id': RealEstateZoneSelection.currentZoneId.toString(),
    };
    final queryString = params.entries
        .map((entry) =>
            '${Uri.encodeQueryComponent(entry.key)}=${Uri.encodeQueryComponent(entry.value)}')
        .join('&');
    final response =
        await _get('/properties${queryString.isEmpty ? '' : '?$queryString'}');
    return _dataList(response).map(RealEstateProperty.fromJson).toList();
  }

  Future<List<RealEstateProperty>> fetchFavorites(String guestId) async {
    final response =
        await _get('/favorites?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(RealEstateProperty.fromJson).toList();
  }

  Future<bool> toggleFavorite({
    required String guestId,
    required int propertyId,
  }) async {
    final response = await _post('/favorites/toggle', {
      'guest_id': guestId,
      'property_id': propertyId,
    });
    return response['wishlisted'] == true || response['wishlisted'] == 1;
  }

  Future<String> submitInquiry({
    required int propertyId,
    required String guestId,
    required String name,
    required String phone,
    required String email,
    required String message,
  }) async {
    final response = await _post('/inquiries', {
      'property_id': propertyId,
      'guest_id': guestId,
      'customer_name': name,
      'customer_phone': phone,
      'customer_email': email,
      'message': message,
    });
    return response['inquiry_number']?.toString() ?? '';
  }

  Future<String> requestSiteVisit({
    required int propertyId,
    required String guestId,
    required String name,
    required String phone,
    required String email,
    required String address,
    required String requestedDate,
    required String requestedTime,
    required String note,
  }) async {
    final response = await _post('/site-visits', {
      'property_id': propertyId,
      'guest_id': guestId,
      'customer_name': name,
      'customer_phone': phone,
      'customer_email': email,
      'address': address,
      'requested_date': requestedDate,
      'requested_time': requestedTime,
      'note': note,
    });
    return response['visit_number']?.toString() ?? '';
  }

  Future<String> reportProperty({
    required int propertyId,
    required String guestId,
    required String name,
    required String phone,
    required String message,
  }) async {
    final response = await _post('/complaints', {
      'property_id': propertyId,
      'guest_id': guestId,
      'customer_name': name,
      'customer_phone': phone,
      'message': message,
    });
    return response['complaint_number']?.toString() ?? '';
  }

  Future<List<RealEstateSiteVisit>> fetchSiteVisits(String guestId) async {
    final response = await _get(
      '/site-visits?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    return _dataList(response).map(RealEstateSiteVisit.fromJson).toList();
  }

  Future<List<RealEstateInquiry>> fetchInquiries(String guestId) async {
    final response = await _get(
      '/inquiries?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    return _dataList(response).map(RealEstateInquiry.fromJson).toList();
  }

  Future<List<RealEstateSavedSearch>> fetchSavedSearches(String guestId) async {
    final response = await _get(
      '/saved-searches?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    return _dataList(response).map(RealEstateSavedSearch.fromJson).toList();
  }

  Future<void> saveSearch({
    required String guestId,
    required String name,
    required Map<String, dynamic> filters,
    bool notify = false,
  }) async {
    await _post('/saved-searches', {
      'guest_id': guestId,
      'name': name,
      'filters': filters,
      'notify': notify,
    });
  }

  Future<Map<String, dynamic>> _get(String path) async {
    final request = await _client.getUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, dynamic> body,
  ) async {
    final request = await _client.postUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.write(jsonEncode(body));
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw RealEstateApiException(
        decoded is Map ? decoded['message']?.toString() ?? raw : raw,
      );
    }
    return decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : {'data': decoded};
  }

  List<Map<String, dynamic>> _dataList(Map<String, dynamic> response) {
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  String _withZone(String path) {
    final zoneId = RealEstateZoneSelection.currentZoneId;
    if (zoneId == null || zoneId <= 0) return path;
    final separator = path.contains('?') ? '&' : '?';
    return '$path${separator}zone_id=$zoneId';
  }
}

class RealEstateZoneSelection {
  static int? currentZoneId;
}

class RealEstateApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'REAL_ESTATE_API_BASE_URL',
    defaultValue:
        'https://snow-grouse-381496.hostingersite.com/api/v1/real-estate',
  );
}

class RealEstateApiException implements Exception {
  RealEstateApiException(this.message);
  final String message;

  @override
  String toString() => message;
}
