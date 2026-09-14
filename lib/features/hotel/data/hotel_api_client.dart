import 'dart:convert';
import 'dart:io';

class HotelApiClient {
  HotelApiClient({String? baseUrl})
      : baseUrl =
            (baseUrl ?? HotelApiConfig.baseUrl).replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<HotelHomeData> fetchHome() async {
    final response = await _get(_withZone('/home'));
    return HotelHomeData.fromJson(response);
  }

  Future<HotelConfig> fetchConfig() async {
    final response = await _get('/config');
    return HotelConfig.fromJson(response);
  }

  Future<HotelDetails> fetchHotel(int hotelId) async {
    final response = await _get(_withZone('/$hotelId'));
    return HotelDetails.fromJson(response);
  }

  Future<List<HotelBooking>> fetchBookings(String guestId) async {
    final response =
        await _get('/bookings?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(HotelBooking.fromJson).toList();
  }

  Future<HotelBooking> fetchBooking({
    required int bookingId,
    required String guestId,
  }) async {
    final response = await _get(
      '/bookings/$bookingId?guest_id=${Uri.encodeQueryComponent(guestId)}',
    );
    final data = response['data'];
    if (data is Map) {
      return HotelBooking.fromJson(Map<String, dynamic>.from(data));
    }
    throw HotelApiException('Booking not found');
  }

  Future<Map<String, dynamic>> placeBooking({
    required int roomId,
    int? zoneId,
    required String guestId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String checkIn,
    required String checkOut,
    required int rooms,
    required int adults,
    required int children,
    required String paymentMethod,
    String paymentReference = '',
    String paymentNote = '',
  }) {
    return _post('/bookings', {
      'room_id': roomId,
      if ((zoneId ?? HotelZoneSelection.currentZoneId) != null &&
          (zoneId ?? HotelZoneSelection.currentZoneId)! > 0)
        'zone_id': zoneId ?? HotelZoneSelection.currentZoneId,
      'guest_id': guestId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'check_in': checkIn,
      'check_out': checkOut,
      'rooms': rooms,
      'adults': adults,
      'children': children,
      'payment_method': paymentMethod,
      'payment_reference': paymentReference,
      'payment_note': paymentNote,
    });
  }

  Future<void> cancelBooking({
    required int bookingId,
    required String guestId,
    required String reason,
  }) async {
    await _post('/bookings/$bookingId/cancel', {
      'guest_id': guestId,
      'reason': reason,
    });
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
      throw HotelApiException(
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
    final zoneId = HotelZoneSelection.currentZoneId;
    if (zoneId == null || zoneId <= 0) return path;
    final separator = path.contains('?') ? '&' : '?';
    return '$path${separator}zone_id=$zoneId';
  }
}

class HotelZoneSelection {
  static int? currentZoneId;
}

class HotelApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'HOTEL_API_BASE_URL',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1/hotels',
  );
}

class HotelApiException implements Exception {
  HotelApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class HotelHomeData {
  const HotelHomeData({
    required this.config,
    required this.categories,
    required this.featuredHotels,
    required this.popularHotels,
  });

  final HotelConfig config;
  final List<HotelCategory> categories;
  final List<HotelSummary> featuredHotels;
  final List<HotelSummary> popularHotels;

  factory HotelHomeData.fromJson(Map<String, dynamic> json) {
    return HotelHomeData(
      config: HotelConfig.fromJson(
          Map<String, dynamic>.from(json['config'] as Map? ?? {})),
      categories:
          _list(json['categories']).map(HotelCategory.fromJson).toList(),
      featuredHotels:
          _list(json['featured_hotels']).map(HotelSummary.fromJson).toList(),
      popularHotels:
          _list(json['popular_hotels']).map(HotelSummary.fromJson).toList(),
    );
  }
}

class HotelConfig {
  const HotelConfig({
    required this.appName,
    required this.currencySymbol,
    required this.maintenanceMode,
    required this.maintenanceMessage,
    required this.latestAppVersion,
    required this.forceUpdateVersion,
    required this.paymentMethods,
    required this.cancellationPolicy,
    required this.cmsPages,
  });

  final String appName;
  final String currencySymbol;
  final bool maintenanceMode;
  final String maintenanceMessage;
  final String latestAppVersion;
  final String forceUpdateVersion;
  final List<HotelPaymentMethod> paymentMethods;
  final HotelCancellationPolicy cancellationPolicy;
  final List<HotelCmsPage> cmsPages;

  factory HotelConfig.fromJson(Map<String, dynamic> json) {
    return HotelConfig(
      appName: json['app_name']?.toString() ?? 'City Hotels',
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      maintenanceMode: json['maintenance_mode'] == true,
      maintenanceMessage: json['maintenance_message']?.toString() ?? '',
      latestAppVersion: json['latest_app_version']?.toString() ?? '',
      forceUpdateVersion: json['force_update_version']?.toString() ?? '',
      paymentMethods: _list(json['booking_payments'])
          .map(HotelPaymentMethod.fromJson)
          .toList(),
      cancellationPolicy: HotelCancellationPolicy.fromJson(
        Map<String, dynamic>.from(json['cancellation_policy'] as Map? ?? {}),
      ),
      cmsPages: _list(json['cms_pages']).map(HotelCmsPage.fromJson).toList(),
    );
  }
}

class HotelCancellationPolicy {
  const HotelCancellationPolicy({
    required this.freeHoursBeforeCheckIn,
    required this.lateRefundPercent,
  });

  final int freeHoursBeforeCheckIn;
  final double lateRefundPercent;

  factory HotelCancellationPolicy.fromJson(Map<String, dynamic> json) {
    return HotelCancellationPolicy(
      freeHoursBeforeCheckIn: _int(json['free_hours_before_check_in']),
      lateRefundPercent: _double(json['late_refund_percent']),
    );
  }
}

class HotelCmsPage {
  const HotelCmsPage({
    required this.slug,
    required this.title,
    required this.content,
  });

  final String slug;
  final String title;
  final String content;

  factory HotelCmsPage.fromJson(Map<String, dynamic> json) {
    return HotelCmsPage(
      slug: json['slug']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
    );
  }
}

class HotelPaymentMethod {
  const HotelPaymentMethod({
    required this.id,
    required this.title,
    required this.description,
    required this.requiresReference,
  });

  final String id;
  final String title;
  final String description;
  final bool requiresReference;

  factory HotelPaymentMethod.fromJson(Map<String, dynamic> json) {
    return HotelPaymentMethod(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      requiresReference: json['requires_reference'] == true,
    );
  }
}

class HotelCategory {
  const HotelCategory({required this.id, required this.name});
  final int id;
  final String name;
  factory HotelCategory.fromJson(Map<String, dynamic> json) {
    return HotelCategory(
        id: _int(json['id']), name: json['name']?.toString() ?? '');
  }
}

class HotelSummary {
  const HotelSummary({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.city,
    required this.area,
    required this.address,
    required this.description,
    required this.starRating,
    required this.rating,
    required this.reviewCount,
    required this.startingPrice,
    required this.thumbnail,
    required this.amenities,
  });

  final int id;
  final int categoryId;
  final String name;
  final String city;
  final String area;
  final String address;
  final String description;
  final double starRating;
  final double rating;
  final int reviewCount;
  final double startingPrice;
  final String thumbnail;
  final List<String> amenities;

  factory HotelSummary.fromJson(Map<String, dynamic> json) {
    return HotelSummary(
      id: _int(json['id']),
      categoryId: _int(json['category_id']),
      name: json['name']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      area: json['area']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      starRating: _double(json['star_rating']),
      rating: _double(json['rating']),
      reviewCount: _int(json['review_count']),
      startingPrice: _double(json['starting_price']),
      thumbnail: json['thumbnail']?.toString() ?? '',
      amenities: _strings(json['amenities']),
    );
  }
}

class HotelRoom {
  const HotelRoom({
    required this.id,
    required this.hotelId,
    required this.name,
    required this.description,
    required this.adults,
    required this.children,
    required this.totalRooms,
    required this.pricePerNight,
    required this.discountPrice,
    required this.taxPercent,
    required this.thumbnail,
    required this.amenities,
  });

  final int id;
  final int hotelId;
  final String name;
  final String description;
  final int adults;
  final int children;
  final int totalRooms;
  final double pricePerNight;
  final double? discountPrice;
  final double taxPercent;
  final String thumbnail;
  final List<String> amenities;

  double get sellingPrice => discountPrice ?? pricePerNight;

  factory HotelRoom.fromJson(Map<String, dynamic> json) {
    return HotelRoom(
      id: _int(json['id']),
      hotelId: _int(json['hotel_id']),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      adults: _int(json['capacity_adults']),
      children: _int(json['capacity_children']),
      totalRooms: _int(json['total_rooms']),
      pricePerNight: _double(json['price_per_night']),
      discountPrice: json['discount_price'] == null
          ? null
          : _double(json['discount_price']),
      taxPercent: _double(json['tax_percent']),
      thumbnail: json['thumbnail']?.toString() ?? '',
      amenities: _strings(json['amenities']),
    );
  }
}

class HotelDetails {
  const HotelDetails({
    required this.hotel,
    required this.rooms,
    required this.reviews,
  });

  final HotelSummary hotel;
  final List<HotelRoom> rooms;
  final List<HotelReview> reviews;

  factory HotelDetails.fromJson(Map<String, dynamic> json) {
    return HotelDetails(
      hotel: HotelSummary.fromJson(
          Map<String, dynamic>.from(json['data'] as Map? ?? {})),
      rooms: _list(json['rooms']).map(HotelRoom.fromJson).toList(),
      reviews: _list(json['reviews']).map(HotelReview.fromJson).toList(),
    );
  }
}

class HotelReview {
  const HotelReview({
    required this.customerName,
    required this.rating,
    required this.comment,
  });
  final String customerName;
  final int rating;
  final String comment;
  factory HotelReview.fromJson(Map<String, dynamic> json) {
    return HotelReview(
      customerName: json['customer_name']?.toString() ?? 'Customer',
      rating: _int(json['rating']),
      comment: json['comment']?.toString() ?? '',
    );
  }
}

class HotelBooking {
  const HotelBooking({
    required this.id,
    required this.bookingNumber,
    required this.hotelName,
    required this.roomName,
    required this.customerName,
    required this.customerPhone,
    required this.checkIn,
    required this.checkOut,
    required this.nights,
    required this.rooms,
    required this.adults,
    required this.children,
    required this.grandTotal,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.refundStatus,
    required this.refundAmount,
    required this.refundNote,
    required this.status,
    required this.createdAt,
  });

  final int id;
  final String bookingNumber;
  final String hotelName;
  final String roomName;
  final String customerName;
  final String customerPhone;
  final String checkIn;
  final String checkOut;
  final int nights;
  final int rooms;
  final int adults;
  final int children;
  final double grandTotal;
  final String paymentMethod;
  final String paymentStatus;
  final String refundStatus;
  final double refundAmount;
  final String refundNote;
  final String status;
  final String createdAt;

  factory HotelBooking.fromJson(Map<String, dynamic> json) {
    return HotelBooking(
      id: _int(json['id']),
      bookingNumber: json['booking_number']?.toString() ?? '',
      hotelName: json['hotel_name']?.toString() ?? '',
      roomName: json['room_name']?.toString() ?? '',
      customerName: json['customer_name']?.toString() ?? '',
      customerPhone: json['customer_phone']?.toString() ?? '',
      checkIn: json['check_in']?.toString() ?? '',
      checkOut: json['check_out']?.toString() ?? '',
      nights: _int(json['nights']),
      rooms: _int(json['rooms']),
      adults: _int(json['adults']),
      children: _int(json['children']),
      grandTotal: _double(json['grand_total']),
      paymentMethod: json['payment_method']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
      refundStatus: json['refund_status']?.toString() ?? 'none',
      refundAmount: _double(json['refund_amount']),
      refundNote: json['refund_note']?.toString() ?? '',
      status: json['booking_status']?.toString() ?? 'pending',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

List<Map<String, dynamic>> _list(Object? value) {
  if (value is! List) return const [];
  return value
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

List<String> _strings(Object? value) {
  if (value is! List) return const [];
  return value
      .map((item) => item.toString())
      .where((item) => item.isNotEmpty)
      .toList();
}

int _int(Object? value) => int.tryParse(value?.toString() ?? '') ?? 0;
double _double(Object? value) => double.tryParse(value?.toString() ?? '') ?? 0;
