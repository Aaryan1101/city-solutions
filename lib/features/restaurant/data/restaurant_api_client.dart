import 'dart:convert';
import 'dart:io';

class RestaurantApiClient {
  RestaurantApiClient({String? baseUrl})
      : baseUrl = (baseUrl ?? RestaurantApiConfig.baseUrl)
            .replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<RestaurantHomeData> fetchHome({int? zoneId}) async {
    final response = await _get(_withZone('/home', zoneId));
    return RestaurantHomeData.fromJson(response);
  }

  Future<RestaurantDetails> fetchRestaurant(int restaurantId) async {
    final response = await _get('/$restaurantId');
    return RestaurantDetails.fromJson(response);
  }

  Future<List<RestaurantSlot>> fetchSlots({
    required int restaurantId,
    required String date,
    required int partySize,
  }) async {
    final response = await _get(
      '/$restaurantId/slots?date=${Uri.encodeQueryComponent(date)}&party_size=$partySize',
    );
    return _list(response['data']).map(RestaurantSlot.fromJson).toList();
  }

  Future<List<RestaurantBooking>> fetchBookings(String guestId) async {
    final response =
        await _get('/bookings?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _list(response['data']).map(RestaurantBooking.fromJson).toList();
  }

  Future<List<RestaurantFoodOrder>> fetchFoodOrders(String guestId) async {
    final response =
        await _get('/orders?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _list(response['data']).map(RestaurantFoodOrder.fromJson).toList();
  }

  Future<List<RestaurantWaitlistRequest>> fetchWaitlist(String guestId) async {
    final response =
        await _get('/waitlist?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _list(response['data'])
        .map(RestaurantWaitlistRequest.fromJson)
        .toList();
  }

  Future<Map<String, dynamic>> placeBooking({
    required int restaurantId,
    int? zoneId,
    required String guestId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String bookingDate,
    required String bookingTime,
    required int partySize,
    required String paymentMethod,
    String paymentReference = '',
    String paymentNote = '',
    String specialRequest = '',
  }) {
    return _post('/bookings', {
      'restaurant_id': restaurantId,
      if (zoneId != null && zoneId > 0) 'zone_id': zoneId,
      'guest_id': guestId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'booking_date': bookingDate,
      'booking_time': bookingTime,
      'party_size': partySize,
      'payment_method': paymentMethod,
      'payment_reference': paymentReference,
      'payment_note': paymentNote,
      'special_request': specialRequest,
    });
  }

  Future<Map<String, dynamic>> joinWaitlist({
    required int restaurantId,
    int? zoneId,
    required String guestId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String bookingDate,
    required String bookingTime,
    required int partySize,
    String specialRequest = '',
  }) {
    return _post('/waitlist', {
      'restaurant_id': restaurantId,
      if (zoneId != null && zoneId > 0) 'zone_id': zoneId,
      'guest_id': guestId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'booking_date': bookingDate,
      'booking_time': bookingTime,
      'party_size': partySize,
      'special_request': specialRequest,
    });
  }

  Future<Map<String, dynamic>> placeFoodOrder({
    required int restaurantId,
    int? zoneId,
    required String guestId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String deliveryAddress,
    required String paymentMethod,
    String note = '',
    required List<RestaurantFoodCartItem> items,
  }) {
    return _post('/orders', {
      'restaurant_id': restaurantId,
      if (zoneId != null && zoneId > 0) 'zone_id': zoneId,
      'guest_id': guestId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'delivery_address': deliveryAddress,
      'payment_method': paymentMethod,
      'note': note,
      'items': items
          .map((item) => {
                'food_item_id': item.item.id,
                'quantity': item.quantity,
              })
          .toList(),
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
      throw RestaurantApiException(
        decoded is Map ? decoded['message']?.toString() ?? raw : raw,
      );
    }
    return decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : {'data': decoded};
  }

  String _withZone(String path, int? zoneId) {
    if (zoneId == null || zoneId <= 0) return path;
    final separator = path.contains('?') ? '&' : '?';
    return '$path${separator}zone_id=$zoneId';
  }
}

class RestaurantApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'RESTAURANT_API_BASE_URL',
    defaultValue:
        'https://snow-grouse-381496.hostingersite.com/api/v1/restaurants',
  );
}

class RestaurantApiException implements Exception {
  RestaurantApiException(this.message);
  final String message;

  @override
  String toString() => message;
}

class RestaurantHomeData {
  const RestaurantHomeData({
    required this.config,
    required this.categories,
    required this.foodCategories,
    required this.featuredRestaurants,
    required this.popularRestaurants,
    required this.bookTonight,
    required this.featuredFoodItems,
    required this.popularFoodItems,
  });

  final RestaurantConfig config;
  final List<RestaurantCategory> categories;
  final List<RestaurantFoodCategory> foodCategories;
  final List<RestaurantSummary> featuredRestaurants;
  final List<RestaurantSummary> popularRestaurants;
  final List<RestaurantSummary> bookTonight;
  final List<RestaurantFoodItem> featuredFoodItems;
  final List<RestaurantFoodItem> popularFoodItems;

  factory RestaurantHomeData.fromJson(Map<String, dynamic> json) {
    return RestaurantHomeData(
      config: RestaurantConfig.fromJson(
        Map<String, dynamic>.from(json['config'] as Map? ?? {}),
      ),
      categories:
          _list(json['categories']).map(RestaurantCategory.fromJson).toList(),
      foodCategories: _list(json['food_categories'])
          .map(RestaurantFoodCategory.fromJson)
          .toList(),
      featuredRestaurants: _list(json['featured_restaurants'])
          .map(RestaurantSummary.fromJson)
          .toList(),
      popularRestaurants: _list(json['popular_restaurants'])
          .map(RestaurantSummary.fromJson)
          .toList(),
      bookTonight:
          _list(json['book_tonight']).map(RestaurantSummary.fromJson).toList(),
      featuredFoodItems: _list(json['featured_food_items'])
          .map(RestaurantFoodItem.fromJson)
          .toList(),
      popularFoodItems: _list(json['popular_food_items'])
          .map(RestaurantFoodItem.fromJson)
          .toList(),
    );
  }
}

class RestaurantConfig {
  const RestaurantConfig({
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
  final List<RestaurantPaymentMethod> paymentMethods;
  final List<RestaurantCmsPage> cmsPages;

  factory RestaurantConfig.fromJson(Map<String, dynamic> json) {
    return RestaurantConfig(
      appName: json['app_name']?.toString() ?? 'City Restaurants',
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      maintenanceMode: json['maintenance_mode'] == true,
      maintenanceMessage: json['maintenance_message']?.toString() ?? '',
      latestAppVersion: json['latest_app_version']?.toString() ?? '',
      forceUpdateVersion: json['force_update_version']?.toString() ?? '',
      paymentMethods: _list(json['booking_payments'])
          .map(RestaurantPaymentMethod.fromJson)
          .toList(),
      cmsPages:
          _list(json['cms_pages']).map(RestaurantCmsPage.fromJson).toList(),
    );
  }
}

class RestaurantCmsPage {
  const RestaurantCmsPage({
    required this.slug,
    required this.title,
    required this.content,
  });

  final String slug;
  final String title;
  final String content;

  factory RestaurantCmsPage.fromJson(Map<String, dynamic> json) {
    return RestaurantCmsPage(
      slug: json['slug']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
    );
  }
}

class RestaurantPaymentMethod {
  const RestaurantPaymentMethod({
    required this.id,
    required this.title,
    required this.description,
    required this.requiresReference,
    required this.gateway,
    required this.instructions,
  });

  final String id;
  final String title;
  final String description;
  final bool requiresReference;
  final String gateway;
  final String instructions;

  factory RestaurantPaymentMethod.fromJson(Map<String, dynamic> json) {
    return RestaurantPaymentMethod(
      id: json['id']?.toString() ?? 'pay_at_restaurant',
      title: json['title']?.toString() ?? 'Pay At Restaurant',
      description: json['description']?.toString() ?? '',
      requiresReference: json['requires_reference'] == true ||
          json['requires_reference']?.toString() == '1',
      gateway: json['gateway']?.toString() ?? '',
      instructions: json['instructions']?.toString() ?? '',
    );
  }
}

class RestaurantCategory {
  const RestaurantCategory({
    required this.id,
    required this.name,
    this.image,
  });

  final int id;
  final String name;
  final String? image;

  factory RestaurantCategory.fromJson(Map<String, dynamic> json) {
    return RestaurantCategory(
      id: _int(json['id']),
      name: json['name']?.toString() ?? 'Category',
      image: json['image']?.toString(),
    );
  }
}

class RestaurantFoodCategory {
  const RestaurantFoodCategory({
    required this.id,
    required this.name,
    required this.icon,
    this.image,
  });

  final int id;
  final String name;
  final String icon;
  final String? image;

  factory RestaurantFoodCategory.fromJson(Map<String, dynamic> json) {
    return RestaurantFoodCategory(
      id: _int(json['id']),
      name: json['name']?.toString() ?? 'Food',
      icon: json['icon']?.toString() ?? '',
      image: json['image']?.toString(),
    );
  }
}

class RestaurantSummary {
  const RestaurantSummary({
    required this.id,
    required this.name,
    required this.cuisine,
    required this.address,
    required this.area,
    required this.averageCost,
    required this.rating,
    required this.reviewCount,
    required this.deliveryTimeMinutes,
    required this.deliveryFee,
    this.thumbnail,
  });

  final int id;
  final String name;
  final String cuisine;
  final String address;
  final String area;
  final double averageCost;
  final double rating;
  final int reviewCount;
  final int deliveryTimeMinutes;
  final double deliveryFee;
  final String? thumbnail;

  factory RestaurantSummary.fromJson(Map<String, dynamic> json) {
    return RestaurantSummary(
      id: _int(json['id']),
      name: json['name']?.toString() ?? 'Restaurant',
      cuisine: json['cuisine']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      area: json['area']?.toString() ?? '',
      averageCost: _double(json['average_cost']),
      rating: _double(json['rating']),
      reviewCount: _int(json['review_count']),
      deliveryTimeMinutes: _int(json['delivery_time_minutes']),
      deliveryFee: _double(json['delivery_fee']),
      thumbnail: json['thumbnail']?.toString(),
    );
  }
}

class RestaurantFoodItem {
  const RestaurantFoodItem({
    required this.id,
    required this.restaurantId,
    required this.categoryId,
    required this.name,
    required this.restaurantName,
    required this.categoryName,
    required this.description,
    required this.price,
    required this.sellingPrice,
    required this.prepTimeMinutes,
    required this.isVeg,
    required this.stock,
    this.image,
  });

  final int id;
  final int restaurantId;
  final int categoryId;
  final String name;
  final String restaurantName;
  final String categoryName;
  final String description;
  final double price;
  final double sellingPrice;
  final int prepTimeMinutes;
  final bool isVeg;
  final int stock;
  final String? image;

  bool get hasDiscount => sellingPrice < price;

  factory RestaurantFoodItem.fromJson(Map<String, dynamic> json) {
    return RestaurantFoodItem(
      id: _int(json['id']),
      restaurantId: _int(json['restaurant_id']),
      categoryId: _int(json['category_id']),
      name: json['name']?.toString() ?? 'Dish',
      restaurantName: json['restaurant_name']?.toString() ?? 'Restaurant',
      categoryName: json['category_name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      price: _double(json['price']),
      sellingPrice: _double(json['selling_price']),
      prepTimeMinutes: _int(json['prep_time_minutes']),
      isVeg: _int(json['is_veg']) == 1,
      stock: _int(json['stock']),
      image: json['image']?.toString(),
    );
  }
}

class RestaurantFoodCartItem {
  const RestaurantFoodCartItem({required this.item, required this.quantity});

  final RestaurantFoodItem item;
  final int quantity;

  double get lineTotal => item.sellingPrice * quantity;
}

class RestaurantDetails {
  const RestaurantDetails({
    required this.restaurant,
    required this.tables,
    required this.reviews,
    required this.foodItems,
  });

  final RestaurantSummary restaurant;
  final List<RestaurantTable> tables;
  final List<Map<String, dynamic>> reviews;
  final List<RestaurantFoodItem> foodItems;

  factory RestaurantDetails.fromJson(Map<String, dynamic> json) {
    return RestaurantDetails(
      restaurant: RestaurantSummary.fromJson(
        Map<String, dynamic>.from(json['data'] as Map? ?? {}),
      ),
      tables: _list(json['tables']).map(RestaurantTable.fromJson).toList(),
      reviews: _list(json['reviews']),
      foodItems:
          _list(json['food_items']).map(RestaurantFoodItem.fromJson).toList(),
    );
  }
}

class RestaurantFoodOrder {
  const RestaurantFoodOrder({
    required this.id,
    required this.orderNumber,
    required this.restaurantName,
    required this.total,
    required this.status,
    required this.paymentStatus,
    required this.createdAt,
  });

  final int id;
  final String orderNumber;
  final String restaurantName;
  final double total;
  final String status;
  final String paymentStatus;
  final String createdAt;

  factory RestaurantFoodOrder.fromJson(Map<String, dynamic> json) {
    return RestaurantFoodOrder(
      id: _int(json['id']),
      orderNumber: json['order_number']?.toString() ?? '',
      restaurantName: json['restaurant_name']?.toString() ?? 'Restaurant',
      total: _double(json['total']),
      status: json['order_status']?.toString() ?? 'pending',
      paymentStatus: json['payment_status']?.toString() ?? 'unpaid',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class RestaurantTable {
  const RestaurantTable({
    required this.id,
    required this.name,
    required this.capacity,
    required this.count,
  });

  final int id;
  final String name;
  final int capacity;
  final int count;

  factory RestaurantTable.fromJson(Map<String, dynamic> json) {
    return RestaurantTable(
      id: _int(json['id']),
      name: json['table_name']?.toString() ?? 'Table',
      capacity: _int(json['capacity']),
      count: _int(json['table_count']),
    );
  }
}

class RestaurantSlot {
  const RestaurantSlot({
    required this.time,
    required this.tableId,
    required this.capacity,
  });

  final String time;
  final int tableId;
  final int capacity;

  factory RestaurantSlot.fromJson(Map<String, dynamic> json) {
    return RestaurantSlot(
      time: json['time']?.toString() ?? '',
      tableId: _int(json['table_id']),
      capacity: _int(json['capacity']),
    );
  }
}

class RestaurantBooking {
  const RestaurantBooking({
    required this.id,
    required this.bookingNumber,
    required this.restaurantName,
    required this.bookingDate,
    required this.bookingTime,
    required this.partySize,
    required this.status,
    required this.paymentMethod,
    required this.paymentStatus,
  });

  final int id;
  final String bookingNumber;
  final String restaurantName;
  final String bookingDate;
  final String bookingTime;
  final int partySize;
  final String status;
  final String paymentMethod;
  final String paymentStatus;

  factory RestaurantBooking.fromJson(Map<String, dynamic> json) {
    return RestaurantBooking(
      id: _int(json['id']),
      bookingNumber: json['booking_number']?.toString() ?? '',
      restaurantName: json['restaurant_name']?.toString() ?? 'Restaurant',
      bookingDate: json['booking_date']?.toString() ?? '',
      bookingTime: json['booking_time']?.toString() ?? '',
      partySize: _int(json['party_size']),
      status: json['booking_status']?.toString() ?? 'pending',
      paymentMethod: json['payment_method']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
    );
  }
}

class RestaurantWaitlistRequest {
  const RestaurantWaitlistRequest({
    required this.id,
    required this.restaurantName,
    required this.bookingDate,
    required this.bookingTime,
    required this.partySize,
    required this.status,
    required this.adminNote,
  });

  final int id;
  final String restaurantName;
  final String bookingDate;
  final String bookingTime;
  final int partySize;
  final String status;
  final String adminNote;

  factory RestaurantWaitlistRequest.fromJson(Map<String, dynamic> json) {
    return RestaurantWaitlistRequest(
      id: _int(json['id']),
      restaurantName: json['restaurant_name']?.toString() ?? 'Restaurant',
      bookingDate: json['booking_date']?.toString() ?? '',
      bookingTime: json['booking_time']?.toString() ?? '',
      partySize: _int(json['party_size']),
      status: json['status']?.toString() ?? 'pending',
      adminNote: json['admin_note']?.toString() ?? '',
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

int _int(Object? value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _double(Object? value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
