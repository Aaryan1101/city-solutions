import 'dart:convert';
import 'dart:io';

class FloatingAdClient {
  FloatingAdClient({String? baseUrl})
      : baseUrl =
            (baseUrl ?? FloatingAdConfig.baseUrl).replaceAll(RegExp(r'/$'), ''),
        _client = HttpClient()..connectionTimeout = const Duration(seconds: 8);

  final String baseUrl;
  final HttpClient _client;

  Future<FloatingAdData> fetch() async {
    final request = await _client.getUrl(Uri.parse('$baseUrl/app/config'));
    request.headers.set(HttpHeaders.acceptHeader, 'application/json');
    final response = await request.close();
    final raw = await response.transform(utf8.decoder).join();
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw FloatingAdException(raw);
    }
    final decoded = raw.isEmpty ? <String, dynamic>{} : jsonDecode(raw);
    final ad = decoded is Map ? decoded['floating_ad'] : null;
    if (ad is! Map) return FloatingAdData.disabled();
    return FloatingAdData.fromJson(Map<String, dynamic>.from(ad));
  }
}

class FloatingAdConfig {
  static const String baseUrl = String.fromEnvironment(
    'CITY_API_BASE_URL',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1',
  );
}

class FloatingAdData {
  const FloatingAdData({
    required this.enabled,
    required this.id,
    required this.title,
    required this.message,
    required this.imageUrl,
    required this.videoUrl,
    required this.ctaText,
    required this.linkUrl,
    required this.targetModule,
    required this.targetType,
    required this.targetValue,
  });

  final bool enabled;
  final String id;
  final String title;
  final String message;
  final String imageUrl;
  final String videoUrl;
  final String ctaText;
  final String linkUrl;
  final String targetModule;
  final String targetType;
  final String targetValue;

  bool get hasContent =>
      title.trim().isNotEmpty ||
      message.trim().isNotEmpty ||
      imageUrl.trim().isNotEmpty ||
      videoUrl.trim().isNotEmpty;

  factory FloatingAdData.disabled() => const FloatingAdData(
        enabled: false,
        id: '',
        title: '',
        message: '',
        imageUrl: '',
        videoUrl: '',
        ctaText: '',
        linkUrl: '',
        targetModule: '',
        targetType: '',
        targetValue: '',
      );

  factory FloatingAdData.fromJson(Map<String, dynamic> json) {
    return FloatingAdData(
      enabled: json['enabled'] == true || json['enabled']?.toString() == '1',
      id: json['id']?.toString() ?? 'default',
      title: json['title']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      imageUrl: json['image_url']?.toString() ?? '',
      videoUrl: json['video_url']?.toString() ?? '',
      ctaText: json['cta_text']?.toString() ?? '',
      linkUrl: json['link_url']?.toString() ?? '',
      targetModule: json['target_module']?.toString() ?? '',
      targetType: json['target_type']?.toString() ?? '',
      targetValue: json['target_value']?.toString() ?? '',
    );
  }
}

class FloatingAdException implements Exception {
  FloatingAdException(this.message);
  final String message;
  @override
  String toString() => message;
}
