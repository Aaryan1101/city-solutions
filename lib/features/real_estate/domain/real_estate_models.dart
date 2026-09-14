class RealEstateHomeData {
  const RealEstateHomeData({
    required this.config,
    required this.featuredProperties,
    required this.latestProperties,
    required this.projects,
    required this.amenities,
  });

  final RealEstateConfig config;
  final List<RealEstateProperty> featuredProperties;
  final List<RealEstateProperty> latestProperties;
  final List<RealEstateProject> projects;
  final List<RealEstateAmenity> amenities;

  factory RealEstateHomeData.fromJson(Map<String, dynamic> json) {
    return RealEstateHomeData(
      config: RealEstateConfig.fromJson(
        Map<String, dynamic>.from(json['config'] as Map? ?? {}),
      ),
      featuredProperties: _list(json['featured_properties'])
          .map(RealEstateProperty.fromJson)
          .toList(),
      latestProperties: _list(json['latest_properties'])
          .map(RealEstateProperty.fromJson)
          .toList(),
      projects:
          _list(json['projects']).map(RealEstateProject.fromJson).toList(),
      amenities:
          _list(json['amenities']).map(RealEstateAmenity.fromJson).toList(),
    );
  }
}

class RealEstateConfig {
  const RealEstateConfig({
    required this.appName,
    required this.currencySymbol,
    required this.maintenanceMode,
    required this.maintenanceMessage,
    required this.latestAppVersion,
    required this.forceUpdateVersion,
  });

  final String appName;
  final String currencySymbol;
  final bool maintenanceMode;
  final String maintenanceMessage;
  final String latestAppVersion;
  final String forceUpdateVersion;

  factory RealEstateConfig.fromJson(Map<String, dynamic> json) {
    return RealEstateConfig(
      appName: json['app_name']?.toString() ?? 'City Real Estate',
      currencySymbol: json['currency_symbol']?.toString() ?? '₹',
      maintenanceMode: json['maintenance_mode'] == true,
      maintenanceMessage: json['maintenance_message']?.toString() ?? '',
      latestAppVersion: json['latest_app_version']?.toString() ?? '',
      forceUpdateVersion: json['force_update_version']?.toString() ?? '',
    );
  }
}

class RealEstateDetails {
  const RealEstateDetails({
    required this.property,
    required this.images,
    required this.amenities,
    required this.floorPlans,
    required this.similarProperties,
  });

  final RealEstateProperty property;
  final List<String> images;
  final List<RealEstateAmenity> amenities;
  final List<RealEstateFloorPlan> floorPlans;
  final List<RealEstateProperty> similarProperties;

  factory RealEstateDetails.fromJson(Map<String, dynamic> json) {
    return RealEstateDetails(
      property: RealEstateProperty.fromJson(
        Map<String, dynamic>.from(json['data'] as Map? ?? {}),
      ),
      images: _stringList(json['images']),
      amenities:
          _list(json['amenities']).map(RealEstateAmenity.fromJson).toList(),
      floorPlans:
          _list(json['floor_plans']).map(RealEstateFloorPlan.fromJson).toList(),
      similarProperties: _list(json['similar_properties'])
          .map(RealEstateProperty.fromJson)
          .toList(),
    );
  }
}

class RealEstateAgentProfile {
  const RealEstateAgentProfile({
    required this.agent,
    required this.properties,
    required this.projects,
  });

  final RealEstateAgent agent;
  final List<RealEstateProperty> properties;
  final List<RealEstateProject> projects;

  factory RealEstateAgentProfile.fromJson(Map<String, dynamic> json) {
    return RealEstateAgentProfile(
      agent: RealEstateAgent.fromJson(
        Map<String, dynamic>.from(json['data'] as Map? ?? {}),
      ),
      properties:
          _list(json['properties']).map(RealEstateProperty.fromJson).toList(),
      projects:
          _list(json['projects']).map(RealEstateProject.fromJson).toList(),
    );
  }
}

class RealEstateAgent {
  const RealEstateAgent({
    required this.id,
    required this.zoneId,
    required this.businessName,
    required this.ownerName,
    required this.phone,
    required this.email,
    required this.bio,
    required this.profileImageUrl,
    required this.zoneName,
  });

  final int id;
  final int zoneId;
  final String businessName;
  final String ownerName;
  final String phone;
  final String email;
  final String bio;
  final String profileImageUrl;
  final String zoneName;

  factory RealEstateAgent.fromJson(Map<String, dynamic> json) {
    return RealEstateAgent(
      id: _int(json['id']),
      zoneId: _int(json['zone_id']),
      businessName: json['business_name']?.toString() ?? 'Agent',
      ownerName: json['owner_name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      bio: json['bio']?.toString() ?? '',
      profileImageUrl: json['profile_image_full_url']?.toString() ??
          json['profile_image']?.toString() ??
          '',
      zoneName: json['zone_name']?.toString() ?? '',
    );
  }
}

class RealEstateProperty {
  const RealEstateProperty({
    required this.id,
    required this.agentId,
    required this.zoneId,
    required this.title,
    required this.listingPurpose,
    required this.propertyCategory,
    required this.price,
    required this.priceUnit,
    required this.city,
    required this.area,
    required this.address,
    required this.latitude,
    required this.longitude,
    required this.description,
    required this.bedrooms,
    required this.bathrooms,
    required this.balconies,
    required this.parking,
    required this.builtUpArea,
    required this.carpetArea,
    required this.plotArea,
    required this.areaUnit,
    required this.furnishing,
    required this.ownershipType,
    required this.propertyAge,
    required this.thumbnailUrl,
    required this.agentName,
    required this.agentPhone,
    required this.isVerified,
    required this.isFeatured,
  });

  final int id;
  final int agentId;
  final int zoneId;
  final String title;
  final String listingPurpose;
  final String propertyCategory;
  final double price;
  final String priceUnit;
  final String city;
  final String area;
  final String address;
  final double? latitude;
  final double? longitude;
  final String description;
  final int bedrooms;
  final int bathrooms;
  final int balconies;
  final int parking;
  final double builtUpArea;
  final double carpetArea;
  final double plotArea;
  final String areaUnit;
  final String furnishing;
  final String ownershipType;
  final String propertyAge;
  final String thumbnailUrl;
  final String agentName;
  final String agentPhone;
  final bool isVerified;
  final bool isFeatured;

  String get locationLabel {
    final parts = [area, city].where((item) => item.trim().isNotEmpty).toList();
    return parts.isEmpty ? address : parts.join(', ');
  }

  String get purposeLabel {
    switch (listingPurpose) {
      case 'rent':
        return 'For Rent';
      case 'lease':
        return 'For Lease';
      default:
        return 'For Sale';
    }
  }

  String get categoryLabel {
    if (propertyCategory.isEmpty) return 'Property';
    return propertyCategory
        .split('_')
        .map((part) => part.isEmpty
            ? part
            : '${part[0].toUpperCase()}${part.substring(1)}')
        .join(' ');
  }

  double get displayArea => builtUpArea > 0
      ? builtUpArea
      : carpetArea > 0
          ? carpetArea
          : plotArea;

  factory RealEstateProperty.fromJson(Map<String, dynamic> json) {
    return RealEstateProperty(
      id: _int(json['id']),
      agentId: _int(json['agent_id']),
      zoneId: _int(json['zone_id']),
      title: json['title']?.toString() ?? '',
      listingPurpose: json['listing_purpose']?.toString() ?? 'sell',
      propertyCategory: json['property_category']?.toString() ?? 'apartment',
      price: _double(json['price']),
      priceUnit: json['price_unit']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      area: json['area']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      latitude: json['latitude'] == null ? null : _double(json['latitude']),
      longitude: json['longitude'] == null ? null : _double(json['longitude']),
      description: json['description']?.toString() ?? '',
      bedrooms: _int(json['bedrooms']),
      bathrooms: _int(json['bathrooms']),
      balconies: _int(json['balconies']),
      parking: _int(json['parking']),
      builtUpArea: _double(json['built_up_area']),
      carpetArea: _double(json['carpet_area']),
      plotArea: _double(json['plot_area']),
      areaUnit: json['area_unit']?.toString() ?? 'sq ft',
      furnishing: json['furnishing']?.toString() ?? '',
      ownershipType: json['ownership_type']?.toString() ?? '',
      propertyAge: json['property_age']?.toString() ?? '',
      thumbnailUrl: json['thumbnail_full_url']?.toString() ??
          json['thumbnail']?.toString() ??
          '',
      agentName: json['agent_name']?.toString() ??
          json['agent_owner_name']?.toString() ??
          'Agent',
      agentPhone: json['agent_phone']?.toString() ?? '',
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
    );
  }
}

class RealEstateProject {
  const RealEstateProject({
    required this.id,
    required this.builderId,
    required this.zoneId,
    required this.name,
    required this.city,
    required this.area,
    required this.address,
    required this.description,
    required this.latitude,
    required this.longitude,
    required this.launchDate,
    required this.thumbnailUrl,
    required this.builderName,
    required this.builderPhone,
    required this.isFeatured,
  });

  final int id;
  final int builderId;
  final int zoneId;
  final String name;
  final String city;
  final String area;
  final String address;
  final String description;
  final double? latitude;
  final double? longitude;
  final String launchDate;
  final String thumbnailUrl;
  final String builderName;
  final String builderPhone;
  final bool isFeatured;

  String get locationLabel {
    final parts = [area, city].where((item) => item.trim().isNotEmpty).toList();
    return parts.isEmpty ? address : parts.join(', ');
  }

  factory RealEstateProject.fromJson(Map<String, dynamic> json) {
    return RealEstateProject(
      id: _int(json['id']),
      builderId: _int(json['builder_id']),
      zoneId: _int(json['zone_id']),
      name: json['name']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      area: json['area']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      latitude: json['latitude'] == null ? null : _double(json['latitude']),
      longitude: json['longitude'] == null ? null : _double(json['longitude']),
      launchDate: json['launch_date']?.toString() ?? '',
      thumbnailUrl: json['thumbnail_full_url']?.toString() ??
          json['thumbnail']?.toString() ??
          '',
      builderName: json['builder_name']?.toString() ?? 'Builder',
      builderPhone: json['builder_phone']?.toString() ?? '',
      isFeatured: json['is_featured'] == true || json['is_featured'] == 1,
    );
  }
}

class RealEstateProjectDetails {
  const RealEstateProjectDetails({
    required this.project,
    required this.units,
  });

  final RealEstateProject project;
  final List<RealEstateProjectUnit> units;

  factory RealEstateProjectDetails.fromJson(Map<String, dynamic> json) {
    return RealEstateProjectDetails(
      project: RealEstateProject.fromJson(
        Map<String, dynamic>.from(json['data'] as Map? ?? {}),
      ),
      units: _list(json['units']).map(RealEstateProjectUnit.fromJson).toList(),
    );
  }
}

class RealEstateProjectUnit {
  const RealEstateProjectUnit({
    required this.id,
    required this.projectId,
    required this.name,
    required this.priceFrom,
    required this.area,
    required this.bedrooms,
    required this.bathrooms,
    required this.floorPlanUrl,
  });

  final int id;
  final int projectId;
  final String name;
  final double priceFrom;
  final double area;
  final int bedrooms;
  final int bathrooms;
  final String floorPlanUrl;

  factory RealEstateProjectUnit.fromJson(Map<String, dynamic> json) {
    return RealEstateProjectUnit(
      id: _int(json['id']),
      projectId: _int(json['project_id']),
      name: json['name']?.toString() ?? '',
      priceFrom: _double(json['price_from']),
      area: _double(json['area']),
      bedrooms: _int(json['bedrooms']),
      bathrooms: _int(json['bathrooms']),
      floorPlanUrl: json['floor_plan_full_url']?.toString() ??
          json['floor_plan_image']?.toString() ??
          '',
    );
  }
}

class RealEstateAmenity {
  const RealEstateAmenity({
    required this.id,
    required this.name,
    required this.icon,
  });

  final int id;
  final String name;
  final String icon;

  factory RealEstateAmenity.fromJson(Map<String, dynamic> json) {
    return RealEstateAmenity(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      icon: json['icon']?.toString() ?? '',
    );
  }
}

class RealEstateFloorPlan {
  const RealEstateFloorPlan({
    required this.id,
    required this.name,
    required this.imageUrl,
    required this.area,
    required this.bedrooms,
    required this.bathrooms,
  });

  final int id;
  final String name;
  final String imageUrl;
  final double area;
  final int bedrooms;
  final int bathrooms;

  factory RealEstateFloorPlan.fromJson(Map<String, dynamic> json) {
    return RealEstateFloorPlan(
      id: _int(json['id']),
      name: json['name']?.toString() ?? '',
      imageUrl: json['image_full_url']?.toString() ??
          json['image_url']?.toString() ??
          '',
      area: _double(json['area']),
      bedrooms: _int(json['bedrooms']),
      bathrooms: _int(json['bathrooms']),
    );
  }
}

class RealEstateSiteVisit {
  const RealEstateSiteVisit({
    required this.id,
    required this.visitNumber,
    required this.propertyTitle,
    required this.requestedDate,
    required this.requestedTime,
    required this.status,
  });

  final int id;
  final String visitNumber;
  final String propertyTitle;
  final String requestedDate;
  final String requestedTime;
  final String status;

  factory RealEstateSiteVisit.fromJson(Map<String, dynamic> json) {
    return RealEstateSiteVisit(
      id: _int(json['id']),
      visitNumber: json['visit_number']?.toString() ?? '',
      propertyTitle: json['property_title']?.toString() ?? 'Property visit',
      requestedDate: json['requested_date']?.toString() ?? '',
      requestedTime: json['requested_time']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
    );
  }
}

class RealEstateInquiry {
  const RealEstateInquiry({
    required this.id,
    required this.inquiryNumber,
    required this.propertyTitle,
    required this.agentName,
    required this.message,
    required this.status,
    required this.agentReply,
    required this.createdAt,
    required this.thumbnailUrl,
  });

  final int id;
  final String inquiryNumber;
  final String propertyTitle;
  final String agentName;
  final String message;
  final String status;
  final String agentReply;
  final String createdAt;
  final String thumbnailUrl;

  factory RealEstateInquiry.fromJson(Map<String, dynamic> json) {
    return RealEstateInquiry(
      id: _int(json['id']),
      inquiryNumber: json['inquiry_number']?.toString() ?? '',
      propertyTitle: json['property_title']?.toString() ?? 'Property inquiry',
      agentName: json['agent_name']?.toString() ?? 'Agent',
      message: json['message']?.toString() ?? '',
      status: json['status']?.toString() ?? 'open',
      agentReply: json['agent_reply']?.toString() ?? '',
      createdAt: json['created_at']?.toString() ?? '',
      thumbnailUrl: json['thumbnail_full_url']?.toString() ??
          json['thumbnail']?.toString() ??
          '',
    );
  }
}

class RealEstateSavedSearch {
  const RealEstateSavedSearch({
    required this.id,
    required this.name,
    required this.filters,
    required this.notify,
  });

  final int id;
  final String name;
  final Map<String, dynamic> filters;
  final bool notify;

  factory RealEstateSavedSearch.fromJson(Map<String, dynamic> json) {
    return RealEstateSavedSearch(
      id: _int(json['id']),
      name: json['name']?.toString() ?? 'Saved search',
      filters: Map<String, dynamic>.from(json['filters'] as Map? ?? {}),
      notify: json['notify'] == true || json['notify'] == 1,
    );
  }
}

List<Map<String, dynamic>> _list(dynamic value) {
  if (value is! List) return const [];
  return value
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

List<String> _stringList(dynamic value) {
  if (value is! List) return const [];
  return value.map((item) => item.toString()).toList();
}

int _int(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _double(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
