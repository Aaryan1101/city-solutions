import 'dart:convert';
import 'dart:io';

class ServicesApiClient {
  ServicesApiClient({String? baseUrl})
      : baseUrl = (baseUrl ?? ServicesApiConfig.baseUrl)
            .replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<ServicesHomeData> fetchHome() async {
    final responses = await Future.wait([
      _get(_withZone('/config')),
      _get(_withZone('/categories')),
      _get(_withZone('/services')),
    ]);
    return ServicesHomeData(
      config: ServicesConfig.fromJson(responses[0]),
      categories:
          _dataList(responses[1]).map(ServiceCategory.fromJson).toList(),
      services: _dataList(responses[2]).map(CityService.fromJson).toList(),
    );
  }

  Future<List<ServiceBooking>> fetchBookings({required String guestId}) async {
    final response =
        await _get('/bookings?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(ServiceBooking.fromJson).toList();
  }

  Future<ServiceBooking> fetchBooking({
    required int bookingId,
    required String guestId,
  }) async {
    final response = await _get(
      '/bookings/$bookingId?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    final data = response['data'];
    if (data is Map) {
      return ServiceBooking.fromJson(Map<String, dynamic>.from(data));
    }
    throw ServicesApiException('Booking not found');
  }

  Future<List<ServiceSlot>> fetchSlots({
    required int serviceId,
    required String date,
  }) async {
    final response = await _get(
        '/services/$serviceId/slots?date=${Uri.encodeQueryComponent(date)}');
    return _dataList(response).map(ServiceSlot.fromJson).toList();
  }

  Future<void> cancelBooking({
    required int bookingId,
    required String guestId,
    String note = '',
  }) async {
    await _post('/bookings/$bookingId/cancel', {
      'guest_id': guestId,
      'note': note,
    });
  }

  Future<void> rescheduleBooking({
    required int bookingId,
    required String guestId,
    required String preferredDate,
    required String preferredTime,
    int? slotId,
  }) async {
    await _post('/bookings/$bookingId/reschedule', {
      'guest_id': guestId,
      'preferred_date': preferredDate,
      'preferred_time': preferredTime,
      if (slotId != null && slotId > 0) 'slot_id': slotId,
    });
  }

  Future<Map<String, dynamic>> placeBooking({
    required int serviceId,
    required String guestId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String address,
    required String preferredDate,
    required String preferredTime,
    int? slotId,
    List<int> addonIds = const [],
    bool checklistAccepted = false,
    required String paymentMethod,
    String paymentReference = '',
    String paymentNote = '',
    required String note,
  }) {
    return _post('/bookings', {
      'service_id': serviceId,
      if (ServicesZoneSelection.currentZoneId != null &&
          ServicesZoneSelection.currentZoneId! > 0)
        'zone_id': ServicesZoneSelection.currentZoneId,
      'guest_id': guestId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'address': address,
      'preferred_date': preferredDate,
      'preferred_time': preferredTime,
      if (slotId != null && slotId > 0) 'slot_id': slotId,
      if (addonIds.isNotEmpty) 'addon_ids': addonIds,
      if (checklistAccepted) 'checklist_accepted': true,
      'payment_method': paymentMethod,
      'payment_reference': paymentReference,
      'payment_note': paymentNote,
      'note': note,
    });
  }

  Future<List<ServiceSupportThread>> fetchSupportThreads({
    required String guestId,
  }) async {
    final response =
        await _get('/support?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(ServiceSupportThread.fromJson).toList();
  }

  Future<ServiceSupportThreadDetails> createSupportThread({
    required String guestId,
    required String subject,
    required String message,
    int? bookingId,
    String senderName = 'Customer',
  }) async {
    final response = await _post('/support', {
      'guest_id': guestId,
      'subject': subject,
      'message': message,
      if (bookingId != null && bookingId > 0) 'booking_id': bookingId,
      'sender_name': senderName,
    });
    return ServiceSupportThreadDetails.fromJson(response);
  }

  Future<ServiceSupportThreadDetails> fetchSupportThread({
    required int threadId,
    required String guestId,
  }) async {
    final response = await _get(
      '/support/$threadId?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    return ServiceSupportThreadDetails.fromJson(response);
  }

  Future<ServiceSupportThreadDetails> replySupportThread({
    required int threadId,
    required String guestId,
    required String message,
    String senderName = 'Customer',
  }) async {
    final response = await _post('/support/$threadId/reply', {
      'guest_id': guestId,
      'message': message,
      'sender_name': senderName,
    });
    return ServiceSupportThreadDetails.fromJson(response);
  }

  Future<Map<String, dynamic>> _get(String path) async {
    final request = await _client.getUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(
      String path, Map<String, dynamic> body) async {
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
      throw ServicesApiException(
          decoded is Map ? decoded['message']?.toString() ?? raw : raw);
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
    final zoneId = ServicesZoneSelection.currentZoneId;
    if (zoneId == null || zoneId <= 0) return path;
    final separator = path.contains('?') ? '&' : '?';
    return '$path${separator}zone_id=$zoneId';
  }
}

class ServicesZoneSelection {
  static int? currentZoneId;
}

class ServicesApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'SERVICES_API_BASE_URL',
    defaultValue:
        'https://snow-grouse-381496.hostingersite.com/api/v1/services',
  );
}

class ServicesApiException implements Exception {
  ServicesApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class ServicesHomeData {
  const ServicesHomeData({
    required this.config,
    required this.categories,
    required this.services,
  });

  final ServicesConfig config;
  final List<ServiceCategory> categories;
  final List<CityService> services;
}

class ServicesConfig {
  const ServicesConfig({
    required this.appName,
    required this.currencySymbol,
    required this.maintenanceMode,
    required this.maintenanceMessage,
    required this.latestAppVersion,
    required this.forceUpdateVersion,
    required this.paymentMethods,
    required this.cmsPages,
  });

  final String appName;
  final String currencySymbol;
  final bool maintenanceMode;
  final String maintenanceMessage;
  final String latestAppVersion;
  final String forceUpdateVersion;
  final List<ServicePaymentMethod> paymentMethods;
  final List<ServiceCmsPage> cmsPages;

  factory ServicesConfig.fromJson(Map<String, dynamic> json) {
    return ServicesConfig(
      appName: json['app_name']?.toString() ?? 'City Services',
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      maintenanceMode: json['maintenance_mode'] == true,
      maintenanceMessage: json['maintenance_message']?.toString() ?? '',
      latestAppVersion: json['latest_app_version']?.toString() ?? '',
      forceUpdateVersion: json['force_update_version']?.toString() ?? '',
      paymentMethods: (json['booking_payments'] is List)
          ? (json['booking_payments'] as List)
              .whereType<Map>()
              .map((item) => ServicePaymentMethod.fromJson(
                  Map<String, dynamic>.from(item)))
              .toList()
          : const <ServicePaymentMethod>[],
      cmsPages: (json['cms_pages'] is List)
          ? (json['cms_pages'] as List)
              .whereType<Map>()
              .map((item) =>
                  ServiceCmsPage.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const <ServiceCmsPage>[],
    );
  }
}

class ServiceCmsPage {
  const ServiceCmsPage({
    required this.slug,
    required this.title,
    required this.content,
  });

  final String slug;
  final String title;
  final String content;

  factory ServiceCmsPage.fromJson(Map<String, dynamic> json) {
    return ServiceCmsPage(
      slug: json['slug']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
    );
  }
}

class ServicePaymentMethod {
  const ServicePaymentMethod({
    required this.id,
    required this.title,
    required this.description,
  });

  final String id;
  final String title;
  final String description;

  factory ServicePaymentMethod.fromJson(Map<String, dynamic> json) {
    return ServicePaymentMethod(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
    );
  }
}

class ServiceCategory {
  const ServiceCategory({required this.id, required this.name});

  final int id;
  final String name;

  factory ServiceCategory.fromJson(Map<String, dynamic> json) {
    return ServiceCategory(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
    );
  }
}

class CityService {
  const CityService({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.durationMinutes,
    required this.warrantyDays,
    required this.checklist,
    required this.price,
    required this.discountPrice,
    required this.providerName,
    required this.addons,
  });

  final int id;
  final int categoryId;
  final String name;
  final String description;
  final int durationMinutes;
  final int warrantyDays;
  final List<String> checklist;
  final double price;
  final double? discountPrice;
  final String providerName;
  final List<ServiceAddon> addons;

  double get sellingPrice => discountPrice ?? price;

  factory CityService.fromJson(Map<String, dynamic> json) {
    return CityService(
      id: _int(json['id']),
      categoryId: _int(json['category_id']),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      durationMinutes: _int(json['duration_minutes']),
      warrantyDays: _int(json['warranty_days']),
      checklist:
          (json['checklist'] is List ? json['checklist'] as List : const [])
              .map((item) => item.toString())
              .where((item) => item.trim().isNotEmpty)
              .toList(),
      price: _double(json['price']),
      discountPrice: json['discount_price'] == null
          ? null
          : _double(json['discount_price']),
      providerName: json['provider_name']?.toString() ?? '',
      addons: (json['addons'] is List ? json['addons'] as List : const [])
          .whereType<Map>()
          .map((addon) =>
              ServiceAddon.fromJson(Map<String, dynamic>.from(addon)))
          .toList(),
    );
  }
}

class ServiceAddon {
  const ServiceAddon({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
  });

  final int id;
  final String name;
  final String description;
  final double price;

  factory ServiceAddon.fromJson(Map<String, dynamic> json) {
    return ServiceAddon(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      price: _double(json['price']),
    );
  }
}

class ServiceSlot {
  const ServiceSlot({
    required this.id,
    required this.providerName,
    required this.startTime,
    required this.endTime,
    required this.availableCount,
    required this.isAvailable,
  });

  final int id;
  final String providerName;
  final String startTime;
  final String endTime;
  final int availableCount;
  final bool isAvailable;

  String get label => '$startTime - $endTime';

  factory ServiceSlot.fromJson(Map<String, dynamic> json) {
    return ServiceSlot(
      id: _int(json['id']),
      providerName: json['provider_name']?.toString() ?? '',
      startTime: json['start_time']?.toString() ?? '',
      endTime: json['end_time']?.toString() ?? '',
      availableCount: _int(json['available_count']),
      isAvailable: json['is_available'] == true ||
          json['is_available']?.toString() == '1',
    );
  }
}

class ServiceBooking {
  const ServiceBooking({
    required this.id,
    required this.serviceId,
    required this.bookingNumber,
    required this.serviceName,
    required this.serviceDescription,
    required this.amount,
    required this.status,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.providerName,
    required this.customerName,
    required this.customerPhone,
    required this.customerEmail,
    required this.address,
    required this.preferredDate,
    required this.preferredTime,
    required this.addonTotal,
    required this.addons,
    required this.note,
    required this.adminNote,
    required this.rescheduleCount,
    required this.warrantyDays,
    required this.warrantyUntil,
    required this.createdAt,
  });

  final int id;
  final int serviceId;
  final String bookingNumber;
  final String serviceName;
  final String serviceDescription;
  final double amount;
  final String status;
  final String paymentMethod;
  final String paymentStatus;
  final String providerName;
  final String customerName;
  final String customerPhone;
  final String customerEmail;
  final String address;
  final String preferredDate;
  final String preferredTime;
  final double addonTotal;
  final List<ServiceAddon> addons;
  final String note;
  final String adminNote;
  final int rescheduleCount;
  final int warrantyDays;
  final String warrantyUntil;
  final String createdAt;

  factory ServiceBooking.fromJson(Map<String, dynamic> json) {
    return ServiceBooking(
      bookingNumber: json['booking_number']?.toString() ?? '',
      id: _int(json['id']),
      serviceId: _int(json['service_id']),
      serviceName: json['service_name']?.toString() ?? '',
      serviceDescription: json['service_description']?.toString() ?? '',
      amount: _double(json['amount']),
      status: json['booking_status']?.toString() ?? 'pending',
      paymentMethod: json['payment_method']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
      providerName: json['provider_name']?.toString() ?? '',
      customerName: json['customer_name']?.toString() ?? '',
      customerPhone: json['customer_phone']?.toString() ?? '',
      customerEmail: json['customer_email']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      preferredDate: json['preferred_date']?.toString() ?? '',
      preferredTime: json['preferred_time']?.toString() ?? '',
      addonTotal: _double(json['addon_total']),
      addons: _bookingAddons(json['addons_json']),
      note: json['note']?.toString() ?? '',
      adminNote: json['admin_note']?.toString() ?? '',
      rescheduleCount: _int(json['reschedule_count']),
      warrantyDays: _int(json['warranty_days']),
      warrantyUntil: json['warranty_until']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class ServiceSupportThread {
  const ServiceSupportThread({
    required this.id,
    required this.subject,
    required this.status,
    required this.bookingId,
    required this.unreadCount,
    required this.createdAt,
  });

  final int id;
  final String subject;
  final String status;
  final int bookingId;
  final int unreadCount;
  final String createdAt;

  factory ServiceSupportThread.fromJson(Map<String, dynamic> json) {
    return ServiceSupportThread(
      id: _int(json['id']),
      subject: json['subject']?.toString() ?? '',
      status: json['status']?.toString() ?? 'open',
      bookingId: _int(json['booking_id']),
      unreadCount: _int(json['unread_count']),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class ServiceSupportMessage {
  const ServiceSupportMessage({
    required this.id,
    required this.senderType,
    required this.senderName,
    required this.message,
    required this.createdAt,
  });

  final int id;
  final String senderType;
  final String senderName;
  final String message;
  final String createdAt;

  factory ServiceSupportMessage.fromJson(Map<String, dynamic> json) {
    return ServiceSupportMessage(
      id: _int(json['id']),
      senderType: json['sender_type']?.toString() ?? '',
      senderName: json['sender_name']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class ServiceSupportThreadDetails {
  const ServiceSupportThreadDetails({
    required this.thread,
    required this.messages,
  });

  final ServiceSupportThread thread;
  final List<ServiceSupportMessage> messages;

  factory ServiceSupportThreadDetails.fromJson(Map<String, dynamic> json) {
    final threadData = json['data'];
    final messages = json['messages'];
    return ServiceSupportThreadDetails(
      thread: ServiceSupportThread.fromJson(threadData is Map
          ? Map<String, dynamic>.from(threadData)
          : const <String, dynamic>{}),
      messages: messages is List
          ? messages
              .whereType<Map>()
              .map((item) => ServiceSupportMessage.fromJson(
                  Map<String, dynamic>.from(item)))
              .toList()
          : const <ServiceSupportMessage>[],
    );
  }
}

List<ServiceAddon> _bookingAddons(Object? value) {
  if (value == null) return const [];
  Object? decoded;
  try {
    decoded =
        value is String ? jsonDecode(value.isEmpty ? '[]' : value) : value;
  } catch (_) {
    return const [];
  }
  if (decoded is! List) return const [];
  return decoded.whereType<Map>().map((item) {
    final map = Map<String, dynamic>.from(item);
    return ServiceAddon(
      id: _int(map['id']),
      name: map['name']?.toString() ?? '',
      description: '',
      price: _double(map['price']),
    );
  }).toList();
}

int _int(Object? value) => int.tryParse(value?.toString() ?? '') ?? 0;
double _double(Object? value) => double.tryParse(value?.toString() ?? '') ?? 0;
