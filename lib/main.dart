import 'dart:async';

import 'package:flutter/material.dart';

import 'core/app_theme.dart';
import 'core/firebase_service.dart';
import 'core/theme_mode_controller.dart';
import 'features/ads/floating_ad_overlay.dart';
import 'features/mart/data/mart_session_store.dart';
import 'features/splash/splash_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppThemeModeController.instance.load();
  await CityFirebaseService.instance.initialize();
  final session = await MartSessionStore().load();
  unawaited(CityFirebaseService.instance.syncCustomer(
    customerId: session.customerId,
    guestId: session.guestId,
    bearerToken: session.token,
  ));
  runApp(const CitySolutionsApp());
}

class CitySolutionsApp extends StatelessWidget {
  const CitySolutionsApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<ThemeMode>(
      valueListenable: AppThemeModeController.instance.themeMode,
      builder: (context, themeMode, _) => MaterialApp(
        title: 'City Solution',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        themeMode: themeMode,
        builder: (context, child) => FloatingAdOverlay(
          child: child ?? const SizedBox.shrink(),
        ),
        home: const SplashScreen(),
      ),
    );
  }
}
