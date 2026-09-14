import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppThemeModeController {
  AppThemeModeController._();

  static final AppThemeModeController instance = AppThemeModeController._();

  static const _key = 'city_solutions_theme_mode';

  final ValueNotifier<ThemeMode> themeMode =
      ValueNotifier<ThemeMode>(ThemeMode.system);

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    themeMode.value = _fromName(prefs.getString(_key));
  }

  Future<void> setMode(ThemeMode mode) async {
    themeMode.value = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, mode.name);
  }

  static ThemeMode _fromName(String? value) {
    return switch (value) {
      'light' => ThemeMode.light,
      'dark' => ThemeMode.dark,
      _ => ThemeMode.system,
    };
  }
}
