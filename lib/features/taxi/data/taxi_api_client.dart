import 'dart:convert';
import 'dart:io';

class TaxiApiClient {
  TaxiApiClient({String? baseUrl})
      : baseUrl =
            (baseUrl ?? TaxiApiConfig.baseUrl).replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 10);

  final String baseUrl;
  final HttpClient _client;

  Future<TaxiConfig> config({int? zoneId}) async {
    final decoded = await _get(_withZone('/config', zoneId));
    return TaxiConfig.fromJson(decoded);
  }

  Future<TaxiQuote> quote({
    required double pickupLatitude,
    required double pickupLongitude,
    required String pickupAddress,
    required double dropLatitude,
    required double dropLongitude,
    required String dropAddress,
    String authToken = '',
  }) async {
    final decoded = await _post(
      '/quote',
      {
        'pickup_latitude': pickupLatitude,
        'pickup_longitude': pickupLongitude,
        'pickup_address': pickupAddress,
        'drop_latitude': dropLatitude,
        'drop_longitude': dropLongitude,
        'drop_address': dropAddress,
      },
      authToken: authToken,
    );
    return TaxiQuote.fromJson(
      Map<String, dynamic>.from(decoded['data'] as Map? ?? const {}),
    );
  }

  Future<Map<String, dynamic>> book(
    Map<String, dynamic> payload, {
    required String authToken,
  }) =>
      _post('/rides', payload, authToken: authToken);

  Future<List<TaxiRide>> rides({required String authToken}) async {
    final decoded = await _get('/rides', authToken: authToken);
    final data = decoded['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => TaxiRide.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<TaxiRideDetails> ride(
    int id, {
    required String authToken,
  }) async {
    final decoded = await _get('/rides/$id', authToken: authToken);
    return TaxiRideDetails.fromJson(decoded);
  }

  Future<void> cancel(
    int id, {
    required String authToken,
    required String reason,
  }) async {
    await _post('/rides/$id/cancel', {'reason': reason}, authToken: authToken);
  }

  Future<void> rate(
    int id, {
    required String authToken,
    required int rating,
    String review = '',
  }) async {
    await _post('/rides/$id/rate', {'rating': rating, 'review': review},
        authToken: authToken);
  }

  Future<Map<String, dynamic>> _get(
    String path, {
    String authToken = '',
  }) async {
    final request = await _client.getUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    if (authToken.isNotEmpty) {
      request.headers.set(
        HttpHeaders.authorizationHeader,
        'Bearer $authToken',
      );
    }
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, dynamic> body, {
    String authToken = '',
  }) async {
    final request = await _client.postUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    if (authToken.isNotEmpty) {
      request.headers.set(
        HttpHeaders.authorizationHeader,
        'Bearer $authToken',
      );
    }
    request.write(jsonEncode(body));
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    Object? decoded;
    try {
      decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    } on FormatException {
      throw TaxiApiException('The taxi server returned an invalid response.');
    }
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw TaxiApiException(
        decoded is Map ? decoded['message']?.toString() ?? raw : raw,
      );
    }
    return decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : <String, dynamic>{};
  }

  String _withZone(String path, int? zoneId) {
    if (zoneId == null || zoneId <= 0) return path;
    return '$path${path.contains('?') ? '&' : '?'}zone_id=$zoneId';
  }
}

class TaxiApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'CITY_TAXI_API_BASE_URL',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1/taxi',
  );
}

class TaxiApiException implements Exception {
  TaxiApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class TaxiConfig {
  const TaxiConfig({
    required this.vehicleTypes,
    required this.currencySymbol,
    required this.paymentMethods,
    required this.supportPhone,
  });

  final List<TaxiVehicleType> vehicleTypes;
  final String currencySymbol;
  final List<TaxiPaymentMethod> paymentMethods;
  final String supportPhone;

  factory TaxiConfig.fromJson(Map<String, dynamic> json) {
    final rawVehicles = json['vehicle_types'];
    final rawPayments = json['payment_methods'];
    return TaxiConfig(
      vehicleTypes: rawVehicles is List
          ? rawVehicles
              .whereType<Map>()
              .map((item) =>
                  TaxiVehicleType.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      paymentMethods: rawPayments is List
          ? rawPayments
              .whereType<Map>()
              .map((item) =>
                  TaxiPaymentMethod.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      supportPhone: json['support_phone']?.toString() ?? '',
    );
  }
}

class TaxiPaymentMethod {
  const TaxiPaymentMethod({required this.id, required this.label});
  final String id;
  final String label;
  factory TaxiPaymentMethod.fromJson(Map<String, dynamic> json) =>
      TaxiPaymentMethod(
        id: json['id']?.toString() ?? 'cash',
        label: json['label']?.toString() ?? 'Cash',
      );
}

class TaxiVehicleType {
  const TaxiVehicleType({
    required this.id,
    required this.name,
    required this.seats,
    required this.minimumFare,
    required this.perKmFare,
    required this.icon,
  });

  final int id;
  final String name;
  final int seats;
  final double minimumFare;
  final double perKmFare;
  final String icon;

  factory TaxiVehicleType.fromJson(Map<String, dynamic> json) =>
      TaxiVehicleType(
        id: _int(json['id']),
        name: json['name']?.toString() ?? 'Cab',
        seats: _int(json['seats'], fallback: 4),
        minimumFare: _double(json['minimum_fare']),
        perKmFare: _double(json['per_km_fare']),
        icon: json['icon']?.toString() ?? 'local_taxi',
      );
}

class TaxiQuote {
  const TaxiQuote({
    required this.token,
    required this.expiresAt,
    required this.distanceKm,
    required this.durationMinutes,
    required this.encodedPolyline,
    required this.routeSource,
    required this.options,
  });

  final String token;
  final String expiresAt;
  final double distanceKm;
  final int durationMinutes;
  final String encodedPolyline;
  final String routeSource;
  final List<TaxiEstimate> options;

  factory TaxiQuote.fromJson(Map<String, dynamic> json) {
    final route = Map<String, dynamic>.from(json['route'] as Map? ?? const {});
    final rawOptions = json['vehicle_options'];
    return TaxiQuote(
      token: json['quote_token']?.toString() ?? '',
      expiresAt: json['expires_at']?.toString() ?? '',
      distanceKm: _double(route['distance_km']),
      durationMinutes: _int(route['duration_minutes']),
      encodedPolyline: route['encoded_polyline']?.toString() ?? '',
      routeSource: route['source']?.toString() ?? 'unknown',
      options: rawOptions is List
          ? rawOptions
              .whereType<Map>()
              .map((item) =>
                  TaxiEstimate.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class TaxiEstimate {
  const TaxiEstimate({
    required this.vehicleTypeId,
    required this.vehicleTypeName,
    required this.distanceKm,
    required this.durationMinutes,
    required this.estimatedFare,
    required this.available,
    required this.availableDrivers,
    required this.pickupEtaMinutes,
    required this.cancellationFee,
  });

  final int vehicleTypeId;
  final String vehicleTypeName;
  final double distanceKm;
  final int durationMinutes;
  final double estimatedFare;
  final bool available;
  final int availableDrivers;
  final int? pickupEtaMinutes;
  final double cancellationFee;

  factory TaxiEstimate.fromJson(Map<String, dynamic> json) => TaxiEstimate(
        vehicleTypeId: _int(json['vehicle_type_id']),
        vehicleTypeName: json['vehicle_type_name']?.toString() ?? 'Cab',
        distanceKm: _double(json['distance_km']),
        durationMinutes: _int(json['duration_minutes']),
        estimatedFare: _double(json['estimated_fare']),
        available: json['available'] == true || json['available'] == 1,
        availableDrivers: _int(json['available_drivers']),
        pickupEtaMinutes: json['pickup_eta_minutes'] == null
            ? null
            : _int(json['pickup_eta_minutes']),
        cancellationFee: _double(json['cancellation_fee']),
      );
}

class TaxiRide {
  const TaxiRide({
    required this.id,
    required this.rideNumber,
    required this.pickupAddress,
    required this.dropAddress,
    required this.status,
    required this.amount,
    required this.vehicleTypeName,
    required this.driverName,
    required this.driverPhone,
    required this.vehicleName,
    required this.vehicleNumber,
    required this.pickupLatitude,
    required this.pickupLongitude,
    required this.dropLatitude,
    required this.dropLongitude,
    required this.driverLatitude,
    required this.driverLongitude,
    required this.otpCode,
    required this.routePolyline,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.customerRating,
    required this.createdAt,
  });

  final int id;
  final String rideNumber;
  final String pickupAddress;
  final String dropAddress;
  final String status;
  final double amount;
  final String vehicleTypeName;
  final String driverName;
  final String driverPhone;
  final String vehicleName;
  final String vehicleNumber;
  final double pickupLatitude;
  final double pickupLongitude;
  final double dropLatitude;
  final double dropLongitude;
  final double driverLatitude;
  final double driverLongitude;
  final String otpCode;
  final String routePolyline;
  final String paymentMethod;
  final String paymentStatus;
  final int customerRating;
  final String createdAt;

  bool get canCancel =>
      const ['requested', 'accepted', 'arrived'].contains(status);
  bool get isActive =>
      !const ['completed', 'cancelled', 'rejected'].contains(status);

  factory TaxiRide.fromJson(Map<String, dynamic> json) => TaxiRide(
        id: _int(json['id']),
        rideNumber: json['ride_number']?.toString() ?? '',
        pickupAddress: json['pickup_address']?.toString() ?? '',
        dropAddress: json['drop_address']?.toString() ?? '',
        status: json['ride_status']?.toString() ?? 'requested',
        amount: _double(json['final_fare'] ?? json['estimated_fare']),
        vehicleTypeName: json['vehicle_type_name']?.toString() ?? 'Cab',
        driverName: json['driver_name']?.toString() ?? '',
        driverPhone: json['driver_phone']?.toString() ?? '',
        vehicleName: json['vehicle_name']?.toString() ?? '',
        vehicleNumber: json['vehicle_number']?.toString() ?? '',
        pickupLatitude: _double(json['pickup_latitude']),
        pickupLongitude: _double(json['pickup_longitude']),
        dropLatitude: _double(json['drop_latitude']),
        dropLongitude: _double(json['drop_longitude']),
        driverLatitude: _double(json['driver_latitude']),
        driverLongitude: _double(json['driver_longitude']),
        otpCode: json['otp_code']?.toString() ?? '',
        routePolyline: json['route_polyline']?.toString() ?? '',
        paymentMethod: json['payment_method']?.toString() ?? '',
        paymentStatus: json['payment_status']?.toString() ?? '',
        customerRating: _int(json['customer_rating']),
        createdAt: json['created_at']?.toString() ?? '',
      );
}

class TaxiRideDetails {
  const TaxiRideDetails({required this.ride, required this.history});
  final TaxiRide ride;
  final List<TaxiRideHistory> history;

  factory TaxiRideDetails.fromJson(Map<String, dynamic> json) {
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    final rawHistory = json['history'];
    return TaxiRideDetails(
      ride: TaxiRide.fromJson(data),
      history: rawHistory is List
          ? rawHistory
              .whereType<Map>()
              .map((item) =>
                  TaxiRideHistory.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class TaxiRideHistory {
  const TaxiRideHistory({
    required this.status,
    required this.note,
    required this.actorName,
    required this.createdAt,
  });
  final String status;
  final String note;
  final String actorName;
  final String createdAt;
  factory TaxiRideHistory.fromJson(Map<String, dynamic> json) =>
      TaxiRideHistory(
        status: json['status']?.toString() ?? '',
        note: json['note']?.toString() ?? '',
        actorName: json['actor_name']?.toString() ?? '',
        createdAt: json['created_at']?.toString() ?? '',
      );
}

int _int(Object? value, {int fallback = 0}) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? fallback;
}

double _double(Object? value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
