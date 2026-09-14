import 'dart:convert';
import 'dart:math';

import 'package:shared_preferences/shared_preferences.dart';

import '../domain/mart_models.dart';

class MartSessionStore {
  static const _key = 'city_solutions_mart_customer_session';

  Future<MartCustomerSession> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null || raw.isEmpty) return _createGuestSession(prefs);
    final decoded = jsonDecode(raw);
    if (decoded is! Map) return _createGuestSession(prefs);
    final session =
        MartCustomerSession.fromJson(Map<String, dynamic>.from(decoded));
    if (session.guestId.isEmpty) {
      return _createGuestSession(prefs);
    }
    return session;
  }

  Future<void> save(MartCustomerSession session) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, jsonEncode(session.toJson()));
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
  }

  Future<MartCustomerSession> _createGuestSession(
      SharedPreferences prefs) async {
    final random = Random.secure();
    final guestId = 'mart-guest-${DateTime.now().microsecondsSinceEpoch}-'
        '${random.nextInt(1 << 32)}';
    final session = MartCustomerSession(
      customerId: 0,
      guestId: guestId,
      token: '',
      name: '',
      phone: '',
      email: '',
    );
    await prefs.setString(_key, jsonEncode(session.toJson()));
    return session;
  }
}
