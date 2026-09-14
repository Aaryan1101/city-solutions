import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:firebase_app_check/firebase_app_check.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

@pragma('vm:entry-point')
Future<void> citySolutionsFirebaseBackgroundHandler(
    RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // A build without native Firebase configuration keeps working normally.
  }
}

class CityFirebaseService {
  CityFirebaseService._();

  static final CityFirebaseService instance = CityFirebaseService._();
  static const _apiRoot = String.fromEnvironment(
    'CITY_API_ROOT',
    defaultValue: 'https://snow-grouse-381496.hostingersite.com/api/v1',
  );
  static const _channel = AndroidNotificationChannel(
    'city_solutions_updates',
    'Orders and bookings',
    description: 'Order, booking, support, refund and assignment updates.',
    importance: Importance.high,
  );

  final _notifications = FlutterLocalNotificationsPlugin();
  final _http = HttpClient()..connectionTimeout = const Duration(seconds: 8);
  StreamSubscription<String>? _tokenSubscription;
  bool _enabled = false;

  bool get isEnabled => _enabled;

  Future<void> initialize() async {
    if (_enabled) return;
    try {
      await Firebase.initializeApp();
      _enabled = true;
    } catch (error) {
      debugPrint('Firebase is not configured for this build: $error');
      return;
    }

    FirebaseMessaging.onBackgroundMessage(
      citySolutionsFirebaseBackgroundHandler,
    );

    try {
      // ignore: deprecated_member_use
      await FirebaseAppCheck.instance.activate(
        // ignore: deprecated_member_use
        androidProvider:
            kDebugMode ? AndroidProvider.debug : AndroidProvider.playIntegrity,
      );
    } catch (error) {
      debugPrint('Firebase App Check could not start: $error');
    }

    await _configureNotifications();
    await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
    FirebaseMessaging.onMessage.listen(_showMessage);
  }

  Future<void> syncCustomer({
    required int customerId,
    required String guestId,
    String bearerToken = '',
  }) async {
    if (!_enabled) return;
    final token = await FirebaseMessaging.instance.getToken();
    if (token == null || token.isEmpty) return;
    await _registerCustomerToken(token, customerId, guestId, bearerToken);
    await _tokenSubscription?.cancel();
    _tokenSubscription = FirebaseMessaging.instance.onTokenRefresh.listen(
      (value) =>
          _registerCustomerToken(value, customerId, guestId, bearerToken),
    );
  }

  Future<void> syncWorker({
    required int workerId,
    required String role,
    required String bearerToken,
  }) async {
    if (!_enabled) return;
    final token = await FirebaseMessaging.instance.getToken();
    if (token == null || token.isEmpty) return;
    await _registerWorkerToken(token, workerId, role, bearerToken);
    await _tokenSubscription?.cancel();
    _tokenSubscription = FirebaseMessaging.instance.onTokenRefresh.listen(
      (value) => _registerWorkerToken(value, workerId, role, bearerToken),
    );
  }

  Future<void> _registerWorkerToken(
    String token,
    int workerId,
    String role,
    String bearerToken,
  ) =>
      _register(
        '$_apiRoot/workers/device-token',
        token,
        ownerType: role.isEmpty ? 'worker' : role,
        ownerId: workerId,
        bearerToken: bearerToken,
      );

  Future<void> _registerCustomerToken(
    String token,
    int customerId,
    String guestId,
    String bearerToken,
  ) async {
    for (final module in const [
      'global',
      'mart',
      'ecommerce',
      'medical',
      'services',
    ]) {
      final endpoint = module == 'global'
          ? '$_apiRoot/device-token'
          : '$_apiRoot/$module/device-token';
      await _register(
        endpoint,
        token,
        ownerType: 'customer',
        ownerId: customerId,
        guestId: guestId,
        bearerToken: bearerToken,
      );
    }
  }

  Future<void> _register(
    String endpoint,
    String token, {
    required String ownerType,
    int ownerId = 0,
    String guestId = '',
    String bearerToken = '',
  }) async {
    try {
      final request = await _http.postUrl(Uri.parse(endpoint));
      request.headers.contentType = ContentType.json;
      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      if (bearerToken.isNotEmpty) {
        request.headers.set(
          HttpHeaders.authorizationHeader,
          'Bearer $bearerToken',
        );
      }
      request.write(jsonEncode({
        'token': token,
        'owner_type': ownerType,
        if (ownerId > 0) 'owner_id': ownerId,
        if (guestId.isNotEmpty) 'guest_id': guestId,
        'platform': Platform.operatingSystem,
      }));
      final response = await request.close();
      await response.drain<void>();
      if (response.statusCode < 200 || response.statusCode >= 300) {
        debugPrint('Device-token registration failed: ${response.statusCode}');
      }
    } catch (error) {
      debugPrint('Device-token registration failed: $error');
    }
  }

  Future<void> _configureNotifications() async {
    const settings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(
        requestAlertPermission: false,
        requestBadgePermission: false,
        requestSoundPermission: false,
      ),
    );
    await _notifications.initialize(settings: settings);
    await _notifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_channel);
  }

  Future<void> _showMessage(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;
    await _notifications.show(
      id: message.messageId?.hashCode ??
          DateTime.now().millisecondsSinceEpoch.remainder(100000),
      title: notification.title ?? 'City Solutions',
      body: notification.body ?? '',
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          'city_solutions_updates',
          'Orders and bookings',
          channelDescription:
              'Order, booking, support, refund and assignment updates.',
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: DarwinNotificationDetails(),
      ),
      payload: jsonEncode(message.data),
    );
  }
}
