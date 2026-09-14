import 'dart:convert';
import 'dart:io';

import '../domain/ecommerce_models.dart';

class EcommerceApiClient {
  EcommerceApiClient({
    String? baseUrl,
    HttpClient? httpClient,
  })  : baseUrl = (baseUrl ?? EcommerceApiConfig.baseUrl)
            .replaceAll(RegExp(r'/$'), ''),
        _httpClient = httpClient ??
            (HttpClient()..connectionTimeout = const Duration(seconds: 8));

  final String baseUrl;
  final HttpClient _httpClient;

  Future<EcommerceHomeData> fetchHome() async {
    final response = await _get(_withZone('/home'));
    return EcommerceHomeData(
      config: EcommerceConfig.fromJson(
          Map<String, dynamic>.from(response['config'] as Map? ?? response)),
      banners:
          _list(response['banners']).map(EcommerceBanner.fromJson).toList(),
      categories: _list(response['categories'])
          .map(EcommerceCategory.fromJson)
          .toList(),
      brands: _list(response['brands']).map(EcommerceBrand.fromJson).toList(),
      vendors:
          _list(response['vendors']).map(EcommerceVendor.fromJson).toList(),
      featuredProducts: _list(response['featured_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
      flashDealProducts: _list(response['flash_deal_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
      clearanceProducts: _list(response['clearance_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
      topRatedProducts: _list(response['top_rated_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
      bestSellingProducts: _list(response['best_selling_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
      latestProducts: _list(response['latest_products'])
          .map(EcommerceProduct.fromJson)
          .toList(),
    );
  }

  Future<EcommerceConfig> fetchConfig() async {
    final response = await _get('/config');
    return EcommerceConfig.fromJson(response);
  }

  Future<List<EcommerceProduct>> fetchProducts(
      {EcommerceProductQuery? query}) async {
    final response =
        await _get(_withZone('/products${query?.toQueryString() ?? ''}'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<EcommerceProduct> fetchProduct(int productId) async {
    final response = await _get(_withZone('/products/$productId'));
    final data = response['data'];
    return EcommerceProduct.fromJson(
      data is Map ? Map<String, dynamic>.from(data) : response,
    );
  }

  Future<List<EcommerceProduct>> fetchCategoryProducts(
    int categoryId, {
    EcommerceProductQuery? query,
  }) async {
    final response = await _get(_withZone(
        '/categories/$categoryId/products${query?.toQueryString() ?? ''}'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<List<EcommerceProduct>> fetchSubcategoryProducts(
    int subcategoryId, {
    EcommerceProductQuery? query,
  }) async {
    final response = await _get(_withZone(
        '/subcategories/$subcategoryId/products${query?.toQueryString() ?? ''}'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<List<EcommerceProduct>> fetchBrandProducts(
    int brandId, {
    EcommerceProductQuery? query,
  }) async {
    final response = await _get(
        _withZone('/brands/$brandId/products${query?.toQueryString() ?? ''}'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<EcommerceVendor> fetchVendor(int vendorId) async {
    final response = await _get(_withZone('/vendors/$vendorId'));
    final data = response['data'];
    return EcommerceVendor.fromJson(
      data is Map ? Map<String, dynamic>.from(data) : response,
    );
  }

  Future<List<EcommerceProduct>> fetchVendorProducts(
    int vendorId, {
    EcommerceProductQuery? query,
  }) async {
    final response = await _get(_withZone(
        '/vendors/$vendorId/products${query?.toQueryString() ?? ''}'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<List<EcommerceProduct>> searchProducts(
    String query, {
    EcommerceProductQuery? filters,
  }) async {
    final queryString = filters?.toQueryString(prefix: '&') ?? '';
    final response = await _get(_withZone(
        '/products/search?query=${Uri.encodeQueryComponent(query)}$queryString'));
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<Map<String, dynamic>> addToCart({
    required String guestId,
    required int productId,
    required int quantity,
    int? variantId,
  }) {
    return _post('/cart/add?guest_id=${Uri.encodeQueryComponent(guestId)}', {
      'product_id': productId,
      if (variantId != null && variantId > 0) 'variant_id': variantId,
      'quantity': quantity,
    });
  }

  Future<EcommerceCartData> fetchCart({required String guestId}) async {
    final response =
        await _get('/cart?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return EcommerceCartData.fromJson(response);
  }

  Future<EcommerceCartData> updateCart({
    required String guestId,
    required int cartId,
    required int quantity,
  }) async {
    final response = await _put(
        '/cart/update?guest_id=${Uri.encodeQueryComponent(guestId)}', {
      'cart_id': cartId,
      'quantity': quantity,
    });
    return EcommerceCartData.fromJson(response);
  }

  Future<EcommerceCartData> removeCartItem({
    required String guestId,
    required int cartId,
  }) async {
    final response = await _delete(
        '/cart/remove?guest_id=${Uri.encodeQueryComponent(guestId)}&cart_id=$cartId');
    return EcommerceCartData.fromJson(response);
  }

  Future<EcommerceCartData> applyCoupon({
    required String guestId,
    required String code,
  }) async {
    final response = await _post(
      '/cart/coupon?guest_id=${Uri.encodeQueryComponent(guestId)}',
      {'code': code},
    );
    return EcommerceCartData.fromJson(response);
  }

  Future<EcommerceCartData> removeCoupon({required String guestId}) async {
    final response = await _delete(
        '/cart/coupon?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return EcommerceCartData.fromJson(response);
  }

  Future<EcommerceOrderResult> placeOrder({
    required String guestId,
    int? zoneId,
    int? customerId,
    String? customerToken,
    int? addressId,
    required String customerName,
    required String customerPhone,
    required String customerEmail,
    required String address,
    required String orderNote,
    required String substitutionPreference,
    required String paymentMethod,
    int? shippingMethodId,
    String paymentReference = '',
    String paymentNote = '',
    String prescriptionReference = '',
    String prescriptionFileBase64 = '',
    bool ageConfirmed = false,
  }) async {
    final response = await _post('/orders/place', {
      'guest_id': guestId,
      if ((zoneId ?? EcommerceZoneSelection.currentZoneId) != null &&
          (zoneId ?? EcommerceZoneSelection.currentZoneId)! > 0)
        'zone_id': zoneId ?? EcommerceZoneSelection.currentZoneId,
      if (customerId != null && customerId > 0) 'customer_id': customerId,
      if (customerToken != null && customerToken.isNotEmpty)
        'customer_token': customerToken,
      if (addressId != null && addressId > 0) 'address_id': addressId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
      'address': address,
      'order_note': orderNote,
      'substitution_preference': substitutionPreference,
      'payment_method': paymentMethod,
      if (shippingMethodId != null) 'shipping_method_id': shippingMethodId,
      'payment_reference': paymentReference,
      'payment_note': paymentNote,
      if (prescriptionReference.isNotEmpty)
        'prescription_reference': prescriptionReference,
      if (prescriptionFileBase64.isNotEmpty)
        'prescription_file_base64': prescriptionFileBase64,
      if (ageConfirmed) 'age_confirmed': true,
    });
    return EcommerceOrderResult.fromJson(response);
  }

  Future<EcommerceCustomerSession> loginCustomer({
    required String phone,
    required String password,
  }) async {
    final response = await _post('/customers/login', {
      'phone': phone,
      'password': password,
    });
    return EcommerceCustomerSession.fromJson(response);
  }

  Future<EcommerceCustomerSession> registerCustomer({
    required String name,
    required String phone,
    required String email,
    required String password,
  }) async {
    final response = await _post('/customers/register', {
      'name': name,
      'phone': phone,
      'email': email,
      'password': password,
    });
    return EcommerceCustomerSession.fromJson(response);
  }

  Future<List<EcommerceAddress>> fetchAddresses({required String token}) async {
    final response = await _get('/customers/addresses', authToken: token);
    return _dataList(response).map(EcommerceAddress.fromJson).toList();
  }

  Future<List<EcommerceAddress>> saveAddress({
    required String token,
    required String label,
    required String contactName,
    required String contactPhone,
    required String address,
    required String city,
    required String state,
    required String pincode,
    required bool isDefault,
  }) async {
    final response = await _post(
        '/customers/addresses',
        {
          'label': label,
          'contact_name': contactName,
          'contact_phone': contactPhone,
          'address': address,
          'city': city,
          'state': state,
          'pincode': pincode,
          'is_default': isDefault,
        },
        authToken: token);
    return _dataList(response).map(EcommerceAddress.fromJson).toList();
  }

  Future<List<EcommerceAddress>> setDefaultAddress({
    required String token,
    required int addressId,
  }) async {
    final response = await _post(
      '/customers/addresses/$addressId/default',
      {},
      authToken: token,
    );
    return _dataList(response).map(EcommerceAddress.fromJson).toList();
  }

  Future<List<EcommerceAddress>> deleteAddress({
    required String token,
    required int addressId,
  }) async {
    final response =
        await _delete('/customers/addresses/$addressId', authToken: token);
    return _dataList(response).map(EcommerceAddress.fromJson).toList();
  }

  Future<EcommerceWalletData> fetchWallet({required String token}) async {
    final response = await _get('/customers/wallet', authToken: token);
    return EcommerceWalletData.fromJson(response);
  }

  Future<EcommerceWalletData> requestWalletWithdrawal({
    required String token,
    required double amount,
    required String bankDetails,
    required String note,
  }) async {
    final response = await _post(
      '/customers/wallet/withdrawals',
      {
        'amount': amount,
        'bank_details': bankDetails,
        'note': note,
      },
      authToken: token,
    );
    return EcommerceWalletData.fromJson(response);
  }

  Future<List<EcommerceOrder>> fetchOrders({required String guestId}) async {
    final response =
        await _get('/orders?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(EcommerceOrder.fromJson).toList();
  }

  Future<EcommerceOrderDetails> fetchOrderDetails({
    required int orderId,
    required String guestId,
  }) async {
    final response = await _get(
        '/orders/$orderId?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return EcommerceOrderDetails.fromJson(response);
  }

  Future<List<EcommerceRefundRequest>> fetchRefunds(
      {required String guestId}) async {
    final response =
        await _get('/refunds?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(EcommerceRefundRequest.fromJson).toList();
  }

  Future<String> cancelOrder({
    required int orderId,
    required String guestId,
    String? customerToken,
  }) async {
    final response = await _post('/orders/$orderId/cancel', {
      'guest_id': guestId,
      if (customerToken != null && customerToken.isNotEmpty)
        'customer_token': customerToken,
    });
    return response['message']?.toString() ?? 'Order cancelled';
  }

  Future<List<EcommerceNotification>> fetchNotifications({
    required String guestId,
  }) async {
    final response = await _get(
        '/notifications?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(EcommerceNotification.fromJson).toList();
  }

  Future<void> markNotificationsRead({required String guestId}) async {
    await _post('/notifications/read', {'guest_id': guestId});
  }

  Future<List<EcommerceSupportThread>> fetchSupportThreads({
    required String guestId,
  }) async {
    final response =
        await _get('/support?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(EcommerceSupportThread.fromJson).toList();
  }

  Future<EcommerceSupportThreadDetails> createSupportThread({
    required String guestId,
    required String senderName,
    required String subject,
    required String message,
    String attachmentUrl = '',
  }) async {
    final response = await _post('/support', {
      'guest_id': guestId,
      'sender_name': senderName,
      'subject': subject,
      'message': message,
      if (attachmentUrl.isNotEmpty) 'attachment_url': attachmentUrl,
    });
    return EcommerceSupportThreadDetails.fromJson(response);
  }

  Future<EcommerceSupportThreadDetails> fetchSupportThread({
    required String guestId,
    required int threadId,
  }) async {
    final response = await _get(
        '/support/$threadId?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return EcommerceSupportThreadDetails.fromJson(response);
  }

  Future<EcommerceSupportThreadDetails> replySupportThread({
    required String guestId,
    required int threadId,
    required String senderName,
    required String message,
    String attachmentUrl = '',
  }) async {
    final response = await _post('/support/$threadId/reply', {
      'guest_id': guestId,
      'sender_name': senderName,
      'message': message,
      if (attachmentUrl.isNotEmpty) 'attachment_url': attachmentUrl,
    });
    return EcommerceSupportThreadDetails.fromJson(response);
  }

  Future<List<EcommerceProduct>> fetchWishlist(
      {required String guestId}) async {
    final response =
        await _get('/wishlist?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return _dataList(response).map(EcommerceProduct.fromJson).toList();
  }

  Future<bool> isWishlisted({
    required String guestId,
    required int productId,
  }) async {
    final response = await _get(
        '/wishlist/$productId?guest_id=${Uri.encodeQueryComponent(guestId)}');
    return response['wishlisted'] == true;
  }

  Future<bool> toggleWishlist({
    required String guestId,
    required int productId,
  }) async {
    final response = await _post(
      '/wishlist/toggle?guest_id=${Uri.encodeQueryComponent(guestId)}',
      {'product_id': productId},
    );
    return response['wishlisted'] == true;
  }

  Future<EcommerceProductReviewsData> fetchProductReviews({
    required int productId,
  }) async {
    final response = await _get('/products/$productId/reviews');
    return EcommerceProductReviewsData.fromJson(response);
  }

  Future<void> saveProductReview({
    required int productId,
    required String guestId,
    required String customerName,
    required int rating,
    required String comment,
  }) async {
    await _post('/products/$productId/reviews', {
      'guest_id': guestId,
      'customer_name': customerName,
      'rating': rating,
      'comment': comment,
    });
  }

  Future<String> requestRefund({
    required int orderId,
    required int orderItemId,
    required String guestId,
    required String reason,
    required String note,
  }) async {
    final response = await _post('/orders/$orderId/refunds', {
      'guest_id': guestId,
      if (orderItemId > 0) 'order_item_id': orderItemId,
      'reason': reason,
      'note': note,
    });
    return response['message']?.toString() ?? 'Refund request submitted';
  }

  Future<Map<String, dynamic>> _get(String path, {String? authToken}) async {
    final request = await _httpClient.getUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    _setAuthHeader(request, authToken);
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, dynamic> body, {
    String? authToken,
  }) async {
    final request = await _httpClient.postUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    _setAuthHeader(request, authToken);
    request.write(jsonEncode(body));
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _put(
      String path, Map<String, dynamic> body) async {
    final request = await _httpClient.putUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    request.headers.set(HttpHeaders.contentTypeHeader, 'application/json');
    request.write(jsonEncode(body));
    final response = await request.close();
    return _decode(response);
  }

  Future<Map<String, dynamic>> _delete(String path, {String? authToken}) async {
    final request = await _httpClient.deleteUrl(Uri.parse('$baseUrl$path'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    _setAuthHeader(request, authToken);
    final response = await request.close();
    return _decode(response);
  }

  void _setAuthHeader(HttpClientRequest request, String? authToken) {
    if (authToken != null && authToken.trim().isNotEmpty) {
      request.headers.set(HttpHeaders.authorizationHeader, 'Bearer $authToken');
    }
  }

  String _withZone(String path) {
    final zoneId = EcommerceZoneSelection.currentZoneId;
    if (zoneId == null || zoneId <= 0) return path;
    final separator = path.contains('?') ? '&' : '?';
    return '$path${separator}zone_id=$zoneId';
  }

  Future<Map<String, dynamic>> _decode(HttpClientResponse response) async {
    final raw = await response.transform(utf8.decoder).join();
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    if (response.statusCode < 200 || response.statusCode >= 300) {
      if (decoded is Map) {
        final message = decoded['message']?.toString() ?? raw;
        final detail = decoded['error']?.toString();
        throw EcommerceApiException(
            detail == null || detail.isEmpty ? message : '$message: $detail');
      }
      throw EcommerceApiException(raw);
    }
    final normalized = _normalizeAssetUrls(decoded);
    return normalized is Map
        ? Map<String, dynamic>.from(normalized)
        : <String, dynamic>{'data': normalized};
  }

  List<Map<String, dynamic>> _dataList(Map<String, dynamic> response) {
    final data = response['data'];
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  List<Map<String, dynamic>> _list(Object? data) {
    if (data is! List) return const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Object? _normalizeAssetUrls(Object? value) {
    if (value is Map) {
      return value.map((key, item) => MapEntry(key, _normalizeAssetUrls(item)));
    }
    if (value is List) {
      return value.map(_normalizeAssetUrls).toList();
    }
    if (value is String &&
        (value.contains('127.0.0.1') || value.contains('localhost'))) {
      final uri = Uri.tryParse(value);
      final base = Uri.parse(baseUrl);
      if (uri != null && uri.hasAbsolutePath) {
        return base.replace(path: uri.path, query: '', fragment: '').toString();
      }
    }
    return value;
  }
}

class EcommerceZoneSelection {
  static int? currentZoneId;
}

class EcommerceApiConfig {
  static const String baseUrl = String.fromEnvironment(
    'ECOMMERCE_API_BASE_URL',
    defaultValue:
        'https://snow-grouse-381496.hostingersite.com/api/v1/ecommerce',
  );
}

class EcommerceApiException implements Exception {
  EcommerceApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class EcommerceProductQuery {
  const EcommerceProductQuery({
    this.color,
    this.attribute,
    this.minPrice,
    this.maxPrice,
    this.sort,
    this.digital,
  });

  final String? color;
  final String? attribute;
  final double? minPrice;
  final double? maxPrice;
  final String? sort;
  final bool? digital;

  EcommerceProductQuery copyWith({
    String? color,
    String? attribute,
    double? minPrice,
    double? maxPrice,
    String? sort,
    bool? digital,
    bool clearColor = false,
    bool clearAttribute = false,
    bool clearMinPrice = false,
    bool clearMaxPrice = false,
    bool clearDigital = false,
  }) {
    return EcommerceProductQuery(
      color: clearColor ? null : color ?? this.color,
      attribute: clearAttribute ? null : attribute ?? this.attribute,
      minPrice: clearMinPrice ? null : minPrice ?? this.minPrice,
      maxPrice: clearMaxPrice ? null : maxPrice ?? this.maxPrice,
      sort: sort ?? this.sort,
      digital: clearDigital ? null : digital ?? this.digital,
    );
  }

  String toQueryString({String prefix = '?'}) {
    final values = <String, String>{};
    if (color != null && color!.isNotEmpty) values['color'] = color!;
    if (attribute != null && attribute!.isNotEmpty) {
      values['attribute'] = attribute!;
    }
    if (minPrice != null) values['min_price'] = minPrice!.toString();
    if (maxPrice != null) values['max_price'] = maxPrice!.toString();
    if (sort != null && sort!.isNotEmpty) values['sort'] = sort!;
    if (digital != null) values['digital'] = digital!.toString();
    if (values.isEmpty) return '';
    return '$prefix${values.entries.map((entry) => '${Uri.encodeQueryComponent(entry.key)}=${Uri.encodeQueryComponent(entry.value)}').join('&')}';
  }
}
