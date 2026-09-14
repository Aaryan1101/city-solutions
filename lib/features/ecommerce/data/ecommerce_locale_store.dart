import 'package:shared_preferences/shared_preferences.dart';

class EcommerceLocaleStore {
  static const _key = 'city_solutions_ecommerce_locale';

  Future<String?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final locale = prefs.getString(_key);
    if (locale == null || locale.trim().isEmpty) return null;
    return locale;
  }

  Future<void> save(String locale) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, locale);
  }
}
