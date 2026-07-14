import 'package:flutter/material.dart';
import '../../features/splash/splash_page.dart';

class AppRoutes {
  static Map<String, WidgetBuilder> get routes => {
        '/': (context) => const SplashPage(),
      };
}
