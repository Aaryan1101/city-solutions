class MartHomeData {
  const MartHomeData({
    required this.config,
    required this.banners,
    required this.categories,
    required this.brands,
    required this.vendors,
    required this.featuredProducts,
    required this.flashDealProducts,
    required this.clearanceProducts,
    required this.topRatedProducts,
    required this.bestSellingProducts,
    required this.latestProducts,
  });

  final MartConfig config;
  final List<MartBanner> banners;
  final List<MartCategory> categories;
  final List<MartBrand> brands;
  final List<MartVendor> vendors;
  final List<MartProduct> featuredProducts;
  final List<MartProduct> flashDealProducts;
  final List<MartProduct> clearanceProducts;
  final List<MartProduct> topRatedProducts;
  final List<MartProduct> bestSellingProducts;
  final List<MartProduct> latestProducts;
}

class MartConfig {
  const MartConfig({
    required this.appName,
    required this.currencySymbol,
    required this.minimumOrderAmount,
    required this.deliveryCharge,
    required this.codEnabled,
    required this.paymentMethods,
    required this.shippingMethods,
    required this.appLocale,
    required this.supportedLocales,
    required this.cmsPages,
    required this.maintenanceMode,
    required this.maintenanceMessage,
    required this.latestAppVersion,
    required this.forceUpdateVersion,
    required this.supportPollIntervalSeconds,
  });

  final String appName;
  final String currencySymbol;
  final double minimumOrderAmount;
  final double deliveryCharge;
  final bool codEnabled;
  final List<MartPaymentMethod> paymentMethods;
  final List<MartShippingMethod> shippingMethods;
  final String appLocale;
  final List<String> supportedLocales;
  final List<MartCmsPage> cmsPages;
  final bool maintenanceMode;
  final String maintenanceMessage;
  final String latestAppVersion;
  final String forceUpdateVersion;
  final int supportPollIntervalSeconds;

  factory MartConfig.fromJson(Map<String, dynamic> json) {
    return MartConfig(
      appName: json['app_name']?.toString() ?? 'City Mart',
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      minimumOrderAmount: _double(json['minimum_order_amount']),
      deliveryCharge: _double(json['delivery_charge']),
      codEnabled: json['cod_enabled'] == true,
      paymentMethods: (json['payment_methods'] is List)
          ? (json['payment_methods'] as List)
              .whereType<Map>()
              .map((item) =>
                  MartPaymentMethod.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      shippingMethods: (json['shipping_methods'] is List)
          ? (json['shipping_methods'] as List)
              .whereType<Map>()
              .map((item) =>
                  MartShippingMethod.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      appLocale: json['app_locale']?.toString() ?? 'en',
      supportedLocales: (json['supported_locales'] is List)
          ? (json['supported_locales'] as List)
              .map((item) => item.toString())
              .toList()
          : const ['en', 'hi'],
      cmsPages: (json['cms_pages'] is List)
          ? (json['cms_pages'] as List)
              .whereType<Map>()
              .map((item) =>
                  MartCmsPage.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      maintenanceMode: json['maintenance_mode'] == true,
      maintenanceMessage: json['maintenance_message']?.toString() ?? '',
      latestAppVersion: json['latest_app_version']?.toString() ?? '',
      forceUpdateVersion: json['force_update_version']?.toString() ?? '',
      supportPollIntervalSeconds: (json['support_chat'] is Map)
          ? _int((json['support_chat'] as Map)['poll_interval_seconds'])
          : 15,
    );
  }
}

class MartShippingMethod {
  const MartShippingMethod({
    required this.id,
    required this.name,
    required this.description,
    required this.cost,
    required this.expectedDays,
  });

  final int id;
  final String name;
  final String description;
  final double cost;
  final String expectedDays;

  factory MartShippingMethod.fromJson(Map<String, dynamic> json) {
    return MartShippingMethod(
      id: _int(json['id']),
      name: json['name']?.toString() ?? 'Standard Delivery',
      description: json['description']?.toString() ?? '',
      cost: _double(json['cost']),
      expectedDays: json['expected_days']?.toString() ?? '',
    );
  }
}

class MartCmsPage {
  const MartCmsPage({
    required this.slug,
    required this.title,
    required this.content,
  });

  final String slug;
  final String title;
  final String content;

  factory MartCmsPage.fromJson(Map<String, dynamic> json) {
    return MartCmsPage(
      slug: json['slug']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
    );
  }
}

class MartPaymentMethod {
  const MartPaymentMethod({
    required this.id,
    required this.title,
    required this.description,
    required this.requiresReference,
    required this.gateway,
    required this.publicKey,
    required this.instructions,
  });

  final String id;
  final String title;
  final String description;
  final bool requiresReference;
  final String gateway;
  final String publicKey;
  final String instructions;

  factory MartPaymentMethod.fromJson(Map<String, dynamic> json) {
    return MartPaymentMethod(
      id: json['id']?.toString() ?? 'cash_on_delivery',
      title: json['title']?.toString() ?? 'Cash on Delivery',
      description: json['description']?.toString() ?? '',
      requiresReference: json['requires_reference'] == true,
      gateway: json['gateway']?.toString() ?? '',
      publicKey: json['public_key']?.toString() ?? '',
      instructions: json['instructions']?.toString() ?? '',
    );
  }
}

class MartBanner {
  const MartBanner({
    required this.id,
    required this.title,
    required this.imageUrl,
    required this.linkType,
    required this.linkValue,
  });

  final int id;
  final String title;
  final String? imageUrl;
  final String linkType;
  final String linkValue;

  factory MartBanner.fromJson(Map<String, dynamic> json) {
    return MartBanner(
      id: _int(json['id']),
      title: json['title']?.toString() ?? '',
      imageUrl: json['image_full_url']?.toString(),
      linkType: json['link_type']?.toString() ?? 'none',
      linkValue: json['link_value']?.toString() ?? '',
    );
  }
}

class MartCategory {
  const MartCategory({
    required this.id,
    required this.name,
    required this.imageUrl,
    required this.subcategories,
  });

  final int id;
  final String name;
  final String? imageUrl;
  final List<MartSubcategory> subcategories;

  factory MartCategory.fromJson(Map<String, dynamic> json) {
    return MartCategory(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      imageUrl: json['image_full_url']?.toString(),
      subcategories: (json['subcategories'] is List)
          ? (json['subcategories'] as List)
              .whereType<Map>()
              .map((item) =>
                  MartSubcategory.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class MartSubcategory {
  const MartSubcategory({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.imageUrl,
  });

  final int id;
  final int categoryId;
  final String name;
  final String? imageUrl;

  factory MartSubcategory.fromJson(Map<String, dynamic> json) {
    return MartSubcategory(
      id: _int(json['id']),
      categoryId: _int(json['category_id']),
      name: json['name']?.toString() ?? '',
      imageUrl: json['image_full_url']?.toString(),
    );
  }
}

class MartBrand {
  const MartBrand({
    required this.id,
    required this.name,
    required this.imageUrl,
  });

  final int id;
  final String name;
  final String? imageUrl;

  factory MartBrand.fromJson(Map<String, dynamic> json) {
    return MartBrand(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      imageUrl: json['image_full_url']?.toString(),
    );
  }
}

class MartProduct {
  const MartProduct({
    required this.id,
    required this.vendorId,
    required this.vendorName,
    required this.brandId,
    required this.brandName,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.unit,
    required this.price,
    required this.discountPrice,
    required this.stock,
    required this.thumbnailUrl,
    required this.imageUrls,
    required this.variants,
    required this.taxPercent,
    required this.barcode,
    required this.seoTitle,
    required this.seoDescription,
    required this.attributes,
    required this.colors,
    required this.isDigital,
    required this.digitalFileUrl,
    required this.freshnessNote,
    required this.expiryDate,
    required this.shelfLife,
    required this.warrantyNote,
    required this.returnPolicy,
    required this.isFeatured,
  });

  final int id;
  final int vendorId;
  final String? vendorName;
  final int brandId;
  final String? brandName;
  final int categoryId;
  final String name;
  final String description;
  final String unit;
  final double price;
  final double? discountPrice;
  final int stock;
  final String? thumbnailUrl;
  final List<String> imageUrls;
  final List<MartProductVariant> variants;
  final double taxPercent;
  final String barcode;
  final String seoTitle;
  final String seoDescription;
  final List<String> attributes;
  final List<String> colors;
  final bool isDigital;
  final String digitalFileUrl;
  final String freshnessNote;
  final String expiryDate;
  final String shelfLife;
  final String warrantyNote;
  final String returnPolicy;
  final bool isFeatured;

  double get sellingPrice => discountPrice ?? price;
  bool get hasDiscount => discountPrice != null && discountPrice! < price;

  factory MartProduct.fromJson(Map<String, dynamic> json) {
    return MartProduct(
      id: _int(json['id']),
      vendorId: _int(json['vendor_id']),
      vendorName: json['vendor_name']?.toString(),
      brandId: _int(json['brand_id']),
      brandName: json['brand_name']?.toString(),
      categoryId: _int(json['category_id']),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      unit: json['unit']?.toString() ?? 'piece',
      price: _double(json['price']),
      discountPrice: json['discount_price'] == null
          ? null
          : _double(json['discount_price']),
      stock: _int(json['stock']),
      thumbnailUrl: json['thumbnail_full_url']?.toString(),
      imageUrls: (json['images'] is List)
          ? (json['images'] as List).map((image) => image.toString()).toList()
          : const [],
      variants: (json['variants'] is List)
          ? (json['variants'] as List)
              .whereType<Map>()
              .map((item) =>
                  MartProductVariant.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      taxPercent: _double(json['tax_percent']),
      barcode: json['barcode']?.toString() ?? '',
      seoTitle: json['seo_title']?.toString() ?? '',
      seoDescription: json['seo_description']?.toString() ?? '',
      attributes: (json['attributes'] is List)
          ? (json['attributes'] as List).map((item) => item.toString()).toList()
          : const [],
      colors: (json['colors'] is List)
          ? (json['colors'] as List).map((item) => item.toString()).toList()
          : const [],
      isDigital: json['is_digital'] == true || json['is_digital'] == 1,
      digitalFileUrl: json['digital_file_url']?.toString() ?? '',
      freshnessNote: json['freshness_note']?.toString() ?? '',
      expiryDate: json['expiry_date']?.toString() ?? '',
      shelfLife: json['shelf_life']?.toString() ?? '',
      warrantyNote: json['warranty_note']?.toString() ?? '',
      returnPolicy: json['return_policy']?.toString() ?? '',
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
    );
  }
}

class MartProductVariant {
  const MartProductVariant({
    required this.id,
    required this.name,
    required this.unit,
    required this.price,
    required this.discountPrice,
    required this.stock,
  });

  final int id;
  final String name;
  final String unit;
  final double price;
  final double? discountPrice;
  final int stock;

  double get sellingPrice => discountPrice ?? price;
  bool get hasDiscount => discountPrice != null && discountPrice! < price;

  factory MartProductVariant.fromJson(Map<String, dynamic> json) {
    return MartProductVariant(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      unit: json['unit']?.toString() ?? 'piece',
      price: _double(json['price']),
      discountPrice: json['discount_price'] == null
          ? null
          : _double(json['discount_price']),
      stock: _int(json['stock']),
    );
  }
}

class MartReviewSummary {
  const MartReviewSummary({
    required this.totalReviews,
    required this.averageRating,
  });

  final int totalReviews;
  final double averageRating;

  factory MartReviewSummary.fromJson(Map<String, dynamic> json) {
    return MartReviewSummary(
      totalReviews: _int(json['total_reviews']),
      averageRating: _double(json['average_rating']),
    );
  }
}

class MartProductReview {
  const MartProductReview({
    required this.id,
    required this.customerName,
    required this.rating,
    required this.comment,
    required this.reply,
    required this.createdAt,
  });

  final int id;
  final String customerName;
  final int rating;
  final String comment;
  final String reply;
  final String createdAt;

  factory MartProductReview.fromJson(Map<String, dynamic> json) {
    return MartProductReview(
      id: _int(json['id']),
      customerName: json['customer_name']?.toString() ?? 'Customer',
      rating: _int(json['rating']),
      comment: json['comment']?.toString() ?? '',
      reply: json['reply']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartProductReviewsData {
  const MartProductReviewsData({
    required this.summary,
    required this.reviews,
  });

  final MartReviewSummary summary;
  final List<MartProductReview> reviews;

  factory MartProductReviewsData.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'];
    final data = json['data'];
    return MartProductReviewsData(
      summary: MartReviewSummary.fromJson(
        summary is Map ? Map<String, dynamic>.from(summary) : const {},
      ),
      reviews: data is List
          ? data
              .whereType<Map>()
              .map((item) =>
                  MartProductReview.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class MartVendor {
  const MartVendor({
    required this.id,
    required this.shopName,
    required this.ownerName,
    required this.phone,
    required this.email,
    required this.address,
    required this.city,
  });

  final int id;
  final String shopName;
  final String ownerName;
  final String phone;
  final String email;
  final String address;
  final String city;

  factory MartVendor.fromJson(Map<String, dynamic> json) {
    return MartVendor(
      id: _int(json['id']),
      shopName: json['shop_name']?.toString() ?? 'Store',
      ownerName: json['owner_name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
    );
  }
}

class MartCartData {
  const MartCartData({
    required this.items,
    required this.subtotal,
    required this.couponCode,
    required this.couponTitle,
    required this.couponDiscount,
    required this.taxTotal,
    required this.deliveryCharge,
    required this.total,
    required this.itemsCount,
    required this.minimumOrderAmount,
    required this.minimumOrderRemaining,
  });

  final List<MartCartItem> items;
  final double subtotal;
  final String couponCode;
  final String couponTitle;
  final double couponDiscount;
  final double taxTotal;
  final double deliveryCharge;
  final double total;
  final int itemsCount;
  final double minimumOrderAmount;
  final double minimumOrderRemaining;

  factory MartCartData.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final items = data is List
        ? data
            .whereType<Map>()
            .map((item) =>
                MartCartItem.fromJson(Map<String, dynamic>.from(item)))
            .toList()
        : <MartCartItem>[];
    final summary = json['summary'];
    final summaryMap = summary is Map ? summary : const <String, dynamic>{};
    final subtotal = _double(summaryMap['subtotal']);
    final deliveryCharge = _double(summaryMap['delivery_charge']);
    final total = _double(summaryMap['total']);
    return MartCartData(
      items: items,
      subtotal: subtotal,
      couponCode: summaryMap['coupon_code']?.toString() ?? '',
      couponTitle: summaryMap['coupon_title']?.toString() ?? '',
      couponDiscount: _double(summaryMap['coupon_discount']),
      taxTotal: _double(summaryMap['tax_total']),
      deliveryCharge: deliveryCharge,
      total: total > 0
          ? total
          : subtotal + deliveryCharge + _double(summaryMap['tax_total']),
      itemsCount: _int(summaryMap['items_count']),
      minimumOrderAmount: _double(summaryMap['minimum_order_amount']),
      minimumOrderRemaining: _double(summaryMap['minimum_order_remaining']),
    );
  }
}

class MartCartItem {
  const MartCartItem({
    required this.id,
    required this.productId,
    required this.variantId,
    required this.name,
    required this.unit,
    required this.thumbnailUrl,
    required this.quantity,
    required this.price,
  });

  final int id;
  final int productId;
  final int variantId;
  final String name;
  final String unit;
  final String? thumbnailUrl;
  final int quantity;
  final double price;

  double get total => price * quantity;

  factory MartCartItem.fromJson(Map<String, dynamic> json) {
    return MartCartItem(
      id: _int(json['id']),
      productId: _int(json['product_id']),
      variantId: _int(json['variant_id']),
      name: json['name']?.toString() ?? '',
      unit: json['unit']?.toString() ?? '',
      thumbnailUrl: json['thumbnail_full_url']?.toString() ??
          json['thumbnail']?.toString(),
      quantity: _int(json['quantity']),
      price: _double(json['price']),
    );
  }
}

class MartOrderResult {
  const MartOrderResult({
    required this.orderId,
    required this.orderNumber,
    required this.message,
  });

  final int orderId;
  final String orderNumber;
  final String message;

  factory MartOrderResult.fromJson(Map<String, dynamic> json) {
    return MartOrderResult(
      orderId: _int(json['order_id']),
      orderNumber: json['order_number']?.toString() ?? '',
      message: json['message']?.toString() ?? 'Order placed',
    );
  }
}

class MartOrder {
  const MartOrder({
    required this.id,
    required this.orderNumber,
    required this.customerName,
    required this.customerPhone,
    required this.address,
    required this.orderAmount,
    required this.couponCode,
    required this.couponDiscount,
    required this.taxTotal,
    required this.shippingMethodName,
    required this.shippingCost,
    required this.expectedDelivery,
    required this.trackingProvider,
    required this.trackingNumber,
    required this.trackingUrl,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.orderStatus,
    required this.fulfillmentStatus,
    required this.refundStatus,
    required this.deliveryManName,
    required this.deliveryManPhone,
    required this.deliveryVehicle,
    required this.orderNote,
    required this.createdAt,
  });

  final int id;
  final String orderNumber;
  final String customerName;
  final String customerPhone;
  final String address;
  final double orderAmount;
  final String couponCode;
  final double couponDiscount;
  final double taxTotal;
  final String shippingMethodName;
  final double shippingCost;
  final String expectedDelivery;
  final String trackingProvider;
  final String trackingNumber;
  final String trackingUrl;
  final String paymentMethod;
  final String paymentStatus;
  final String orderStatus;
  final String fulfillmentStatus;
  final String refundStatus;
  final String deliveryManName;
  final String deliveryManPhone;
  final String deliveryVehicle;
  final String orderNote;
  final String createdAt;

  bool get canCancel =>
      fulfillmentStatus == 'pending' || fulfillmentStatus == 'confirmed';

  factory MartOrder.fromJson(Map<String, dynamic> json) {
    return MartOrder(
      id: _int(json['id']),
      orderNumber: json['order_number']?.toString() ?? '',
      customerName: json['customer_name']?.toString() ?? '',
      customerPhone: json['customer_phone']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      orderAmount: _double(json['order_amount']),
      couponCode: json['coupon_code']?.toString() ?? '',
      couponDiscount: _double(json['coupon_discount']),
      taxTotal: _double(json['tax_total']),
      shippingMethodName: json['shipping_method_name']?.toString() ?? '',
      shippingCost: _double(json['shipping_cost']),
      expectedDelivery: json['expected_delivery']?.toString() ?? '',
      trackingProvider: json['tracking_provider']?.toString() ?? '',
      trackingNumber: json['tracking_number']?.toString() ?? '',
      trackingUrl: json['tracking_url']?.toString() ?? '',
      paymentMethod: json['payment_method']?.toString() ?? '',
      paymentStatus: json['payment_status']?.toString() ?? '',
      orderStatus: json['display_status']?.toString() ??
          json['order_status']?.toString() ??
          'pending',
      fulfillmentStatus: json['order_status']?.toString() ?? 'pending',
      refundStatus: json['refund_status']?.toString() ?? '',
      deliveryManName: json['delivery_man_name']?.toString() ?? '',
      deliveryManPhone: json['delivery_man_phone']?.toString() ?? '',
      deliveryVehicle: [
        json['vehicle_type']?.toString() ?? '',
        json['vehicle_number']?.toString() ?? '',
      ].where((part) => part.trim().isNotEmpty).join(' '),
      orderNote: json['order_note']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartOrderDetails {
  const MartOrderDetails({
    required this.order,
    required this.items,
    required this.history,
    required this.refunds,
  });

  final MartOrder order;
  final List<MartOrderItem> items;
  final List<MartOrderHistoryItem> history;
  final List<MartRefundRequest> refunds;

  factory MartOrderDetails.fromJson(Map<String, dynamic> json) {
    final items = json['items'];
    final history = json['history'];
    final refunds = json['refunds'];
    return MartOrderDetails(
      order: MartOrder.fromJson(Map<String, dynamic>.from(json['data'] as Map)),
      items: items is List
          ? items
              .whereType<Map>()
              .map((item) =>
                  MartOrderItem.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      history: history is List
          ? history
              .whereType<Map>()
              .map((item) => MartOrderHistoryItem.fromJson(
                  Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      refunds: refunds is List
          ? refunds
              .whereType<Map>()
              .map((item) =>
                  MartRefundRequest.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class MartOrderHistoryItem {
  const MartOrderHistoryItem({
    required this.orderItemId,
    required this.status,
    required this.actorType,
    required this.actorName,
    required this.note,
    required this.createdAt,
  });

  final int orderItemId;
  final String status;
  final String actorType;
  final String actorName;
  final String note;
  final String createdAt;

  factory MartOrderHistoryItem.fromJson(Map<String, dynamic> json) {
    return MartOrderHistoryItem(
      orderItemId: _int(json['order_item_id']),
      status: json['status']?.toString() ?? 'pending',
      actorType: json['actor_type']?.toString() ?? '',
      actorName: json['actor_name']?.toString() ?? '',
      note: json['note']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartRefundRequest {
  const MartRefundRequest({
    required this.id,
    required this.orderId,
    required this.orderItemId,
    required this.orderNumber,
    required this.productName,
    required this.amount,
    required this.reason,
    required this.note,
    required this.adminNote,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  final int id;
  final int orderId;
  final int orderItemId;
  final String orderNumber;
  final String productName;
  final double amount;
  final String reason;
  final String note;
  final String adminNote;
  final String status;
  final String createdAt;
  final String updatedAt;

  factory MartRefundRequest.fromJson(Map<String, dynamic> json) {
    return MartRefundRequest(
      id: _int(json['id']),
      orderId: _int(json['order_id']),
      orderItemId: _int(json['order_item_id']),
      orderNumber: json['order_number']?.toString() ?? '',
      productName: json['product_name']?.toString() ?? '',
      amount: _double(json['amount']),
      reason: json['reason']?.toString() ?? '',
      note: json['note']?.toString() ?? '',
      adminNote: json['admin_note']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      createdAt: json['created_at']?.toString() ?? '',
      updatedAt: json['updated_at']?.toString() ?? '',
    );
  }
}

class MartWalletEntry {
  const MartWalletEntry({
    required this.id,
    required this.direction,
    required this.amount,
    required this.entryType,
    required this.description,
    required this.createdAt,
  });

  final int id;
  final String direction;
  final double amount;
  final String entryType;
  final String description;
  final String createdAt;

  factory MartWalletEntry.fromJson(Map<String, dynamic> json) {
    return MartWalletEntry(
      id: _int(json['id']),
      direction: json['direction']?.toString() ?? '',
      amount: _double(json['amount']),
      entryType: json['entry_type']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartSupportThread {
  const MartSupportThread({
    required this.id,
    required this.subject,
    required this.status,
    required this.createdAt,
    required this.unreadCount,
  });

  final int id;
  final String subject;
  final String status;
  final String createdAt;
  final int unreadCount;

  factory MartSupportThread.fromJson(Map<String, dynamic> json) {
    return MartSupportThread(
      id: _int(json['id']),
      subject: json['subject']?.toString() ?? '',
      status: json['status']?.toString() ?? 'open',
      createdAt: json['created_at']?.toString() ?? '',
      unreadCount: _int(json['unread_count']),
    );
  }
}

class MartSupportThreadDetails {
  const MartSupportThreadDetails({
    required this.thread,
    required this.messages,
  });

  final MartSupportThread thread;
  final List<MartSupportMessage> messages;

  factory MartSupportThreadDetails.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final messages = json['messages'];
    return MartSupportThreadDetails(
      thread: MartSupportThread.fromJson(
        data is Map ? Map<String, dynamic>.from(data) : const {},
      ),
      messages: messages is List
          ? messages
              .whereType<Map>()
              .map((item) =>
                  MartSupportMessage.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class MartSupportMessage {
  const MartSupportMessage({
    required this.id,
    required this.senderType,
    required this.senderName,
    required this.message,
    required this.attachmentUrl,
    required this.createdAt,
  });

  final int id;
  final String senderType;
  final String senderName;
  final String message;
  final String attachmentUrl;
  final String createdAt;

  factory MartSupportMessage.fromJson(Map<String, dynamic> json) {
    return MartSupportMessage(
      id: _int(json['id']),
      senderType: json['sender_type']?.toString() ?? '',
      senderName: json['sender_name']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      attachmentUrl: json['attachment_url']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartWalletData {
  const MartWalletData({
    required this.balance,
    required this.ledger,
    required this.withdrawals,
  });

  final double balance;
  final List<MartWalletEntry> ledger;
  final List<MartWalletWithdrawal> withdrawals;

  factory MartWalletData.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final ledger = json['ledger'];
    final withdrawals = json['withdrawals'];
    return MartWalletData(
      balance: data is Map ? _double(data['balance']) : 0,
      ledger: ledger is List
          ? ledger
              .whereType<Map>()
              .map((item) =>
                  MartWalletEntry.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      withdrawals: withdrawals is List
          ? withdrawals
              .whereType<Map>()
              .map((item) => MartWalletWithdrawal.fromJson(
                  Map<String, dynamic>.from(item)))
              .toList()
          : const [],
    );
  }
}

class MartWalletWithdrawal {
  const MartWalletWithdrawal({
    required this.id,
    required this.amount,
    required this.bankDetails,
    required this.note,
    required this.status,
    required this.adminNote,
    required this.createdAt,
  });

  final int id;
  final double amount;
  final String bankDetails;
  final String note;
  final String status;
  final String adminNote;
  final String createdAt;

  factory MartWalletWithdrawal.fromJson(Map<String, dynamic> json) {
    return MartWalletWithdrawal(
      id: _int(json['id']),
      amount: _double(json['amount']),
      bankDetails: json['bank_details']?.toString() ?? '',
      note: json['note']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      adminNote: json['admin_note']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartOrderItem {
  const MartOrderItem({
    required this.id,
    required this.productName,
    required this.variantName,
    required this.quantity,
    required this.price,
    required this.total,
    required this.status,
    required this.digitalFileUrl,
  });

  final int id;
  final String productName;
  final String variantName;
  final int quantity;
  final double price;
  final double total;
  final String status;
  final String digitalFileUrl;

  factory MartOrderItem.fromJson(Map<String, dynamic> json) {
    return MartOrderItem(
      id: _int(json['id']),
      productName: json['product_name']?.toString() ?? '',
      variantName: json['variant_name']?.toString() ?? '',
      quantity: _int(json['quantity']),
      price: _double(json['price']),
      total: _double(json['total']),
      status: json['status']?.toString() ?? 'pending',
      digitalFileUrl: json['digital_file_url']?.toString() ?? '',
    );
  }
}

class MartNotification {
  const MartNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.orderId,
    required this.readAt,
    required this.createdAt,
  });

  final int id;
  final String title;
  final String message;
  final int orderId;
  final String readAt;
  final String createdAt;

  bool get isRead => readAt.isNotEmpty;

  factory MartNotification.fromJson(Map<String, dynamic> json) {
    return MartNotification(
      id: _int(json['id']),
      title: json['title']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      orderId: _int(json['order_id']),
      readAt: json['read_at']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MartCustomerSession {
  const MartCustomerSession({
    required this.customerId,
    required this.guestId,
    required this.token,
    required this.name,
    required this.phone,
    required this.email,
  });

  final int customerId;
  final String guestId;
  final String token;
  final String name;
  final String phone;
  final String email;

  bool get isLoggedIn => customerId > 0 && token.isNotEmpty;

  factory MartCustomerSession.guest() {
    return const MartCustomerSession(
      customerId: 0,
      guestId: 'city-solutions-guest',
      token: '',
      name: '',
      phone: '',
      email: '',
    );
  }

  factory MartCustomerSession.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final map = data is Map ? Map<String, dynamic>.from(data) : json;
    return MartCustomerSession(
      customerId: _int(map['id'] ?? json['customer_id']),
      guestId: json['guest_id']?.toString() ?? '',
      token: json['token']?.toString() ?? map['token']?.toString() ?? '',
      name: map['name']?.toString() ?? '',
      phone: map['phone']?.toString() ?? '',
      email: map['email']?.toString() ?? '',
    );
  }

  Map<String, Object?> toJson() {
    return {
      'customer_id': customerId,
      'guest_id': guestId,
      'token': token,
      'name': name,
      'phone': phone,
      'email': email,
    };
  }
}

class MartAddress {
  const MartAddress({
    required this.id,
    required this.customerId,
    required this.label,
    required this.contactName,
    required this.contactPhone,
    required this.address,
    required this.city,
    required this.state,
    required this.pincode,
    required this.isDefault,
  });

  final int id;
  final int customerId;
  final String label;
  final String contactName;
  final String contactPhone;
  final String address;
  final String city;
  final String state;
  final String pincode;
  final bool isDefault;

  String get fullAddress {
    return [address, city, state, pincode]
        .where((part) => part.trim().isNotEmpty)
        .join(', ');
  }

  factory MartAddress.fromJson(Map<String, dynamic> json) {
    return MartAddress(
      id: _int(json['id']),
      customerId: _int(json['customer_id']),
      label: json['label']?.toString() ?? 'Home',
      contactName: json['contact_name']?.toString() ?? '',
      contactPhone: json['contact_phone']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      state: json['state']?.toString() ?? '',
      pincode: json['pincode']?.toString() ?? '',
      isDefault: json['is_default'] == true || json['is_default'] == 1,
    );
  }
}

int _int(Object? value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _double(Object? value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
