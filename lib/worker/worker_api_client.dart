import 'dart:convert';
import 'dart:io';

import 'package:shared_preferences/shared_preferences.dart';

class WorkerApiClient {
  WorkerApiClient({String? baseUrl})
      : baseUrl =
            (baseUrl ?? WorkerApiConfig.baseUrl).replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<WorkerSession?> savedSession() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('city_worker_session');
    if (raw == null || raw.isEmpty) return null;
    final decoded = jsonDecode(raw);
    if (decoded is! Map) return null;
    return WorkerSession.fromJson(Map<String, dynamic>.from(decoded));
  }

  Future<void> saveSession(WorkerSession session) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('city_worker_session', jsonEncode(session.toJson()));
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('city_worker_session');
  }

  Future<WorkerSession> login(String phone, String password) async {
    final decoded =
        await _post('/login', {'phone': phone, 'password': password});
    final session = WorkerSession(
      token: decoded['token']?.toString() ?? '',
      worker: WorkerProfile.fromJson(
          Map<String, dynamic>.from(decoded['worker'] as Map)),
    );
    await saveSession(session);
    return session;
  }

  Future<List<WorkerAssignment>> assignments(String token) async {
    final decoded = await _get('/assignments', token);
    final data = decoded['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) =>
            WorkerAssignment.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<void> updateStatus(
    String token,
    WorkerAssignment assignment,
    String status, {
    String otpCode = '',
  }) async {
    await _post(
        '/assignments/status',
        {
          'type': assignment.type,
          'id': assignment.id,
          'status': status,
          if (otpCode.isNotEmpty) 'otp_code': otpCode,
        },
        token: token);
  }

  Future<void> updateLocation(
    String token, {
    required double latitude,
    required double longitude,
    required String availabilityStatus,
  }) async {
    await _post(
        '/location',
        {
          'latitude': latitude,
          'longitude': longitude,
          'availability_status': availabilityStatus,
        },
        token: token);
  }

  Future<Map<String, dynamic>> _get(String path, String token) async {
    final request = await _client.getUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> body,
      {String? token}) async {
    final request = await _client.postUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    if (token != null) {
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $token');
    }
    request.write(jsonEncode(body));
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw WorkerApiException(
          decoded is Map ? decoded['message']?.toString() ?? raw : raw);
    }
    return decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : <String, dynamic>{};
  }
}

class WorkerApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'CITY_WORKER_API_BASE_URL',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1/workers',
  );
}

class WorkerApiException implements Exception {
  WorkerApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class WorkerSession {
  const WorkerSession({required this.token, required this.worker});
  final String token;
  final WorkerProfile worker;

  Map<String, dynamic> toJson() => {'token': token, 'worker': worker.toJson()};
  factory WorkerSession.fromJson(Map<String, dynamic> json) => WorkerSession(
        token: json['token']?.toString() ?? '',
        worker: WorkerProfile.fromJson(
            Map<String, dynamic>.from(json['worker'] as Map? ?? const {})),
      );
}

class WorkerProfile {
  const WorkerProfile(
      {required this.id,
      required this.role,
      required this.name,
      required this.phone,
      required this.availabilityStatus,
      required this.kycReady});
  final int id;
  final String role;
  final String name;
  final String phone;
  final String availabilityStatus;
  final bool kycReady;

  Map<String, dynamic> toJson() => {
        'id': id,
        'role': role,
        'name': name,
        'phone': phone,
        'availability_status': availabilityStatus,
        'kyc_ready': kycReady,
      };
  factory WorkerProfile.fromJson(Map<String, dynamic> json) => WorkerProfile(
        id: int.tryParse(json['id'].toString()) ?? 0,
        role: json['role']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        phone: json['phone']?.toString() ?? '',
        availabilityStatus:
            json['availability_status']?.toString() ?? 'offline',
        kycReady: json['kyc_ready'] != false && json['kyc_ready'] != 0,
      );
}

class WorkerAssignment {
  const WorkerAssignment({
    required this.type,
    required this.id,
    required this.moduleKey,
    required this.number,
    required this.title,
    required this.customerName,
    required this.customerPhone,
    required this.address,
    required this.dropAddress,
    required this.amount,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.status,
    required this.isOffer,
    required this.pickupLatitude,
    required this.pickupLongitude,
    required this.dropLatitude,
    required this.dropLongitude,
    required this.createdAt,
  });

  final String type;
  final int id;
  final String moduleKey;
  final String number;
  final String title;
  final String customerName;
  final String customerPhone;
  final String address;
  final String dropAddress;
  final double amount;
  final String paymentMethod;
  final String paymentStatus;
  final String status;
  final bool isOffer;
  final double pickupLatitude;
  final double pickupLongitude;
  final double dropLatitude;
  final double dropLongitude;
  final String createdAt;

  factory WorkerAssignment.fromJson(Map<String, dynamic> json) =>
      WorkerAssignment(
        type: json['type']?.toString() ?? '',
        id: int.tryParse(json['id'].toString()) ?? 0,
        moduleKey: json['module_key']?.toString() ?? '',
        number: json['number']?.toString() ?? '',
        title: json['title']?.toString() ?? '',
        customerName: json['customer_name']?.toString() ?? '',
        customerPhone: json['customer_phone']?.toString() ?? '',
        address: (json['address'] ?? json['pickup_address'] ?? '').toString(),
        dropAddress: json['drop_address']?.toString() ?? '',
        amount: double.tryParse(json['amount'].toString()) ?? 0,
        paymentMethod: json['payment_method']?.toString() ?? '',
        paymentStatus: json['payment_status']?.toString() ?? '',
        status: json['status']?.toString() ?? '',
        isOffer: json['is_offer'] == true || json['is_offer'] == 1,
        pickupLatitude:
            double.tryParse((json['pickup_latitude'] ?? 0).toString()) ?? 0,
        pickupLongitude:
            double.tryParse((json['pickup_longitude'] ?? 0).toString()) ?? 0,
        dropLatitude:
            double.tryParse((json['drop_latitude'] ?? 0).toString()) ?? 0,
        dropLongitude:
            double.tryParse((json['drop_longitude'] ?? 0).toString()) ?? 0,
        createdAt: json['created_at']?.toString() ?? '',
      );
}
