import 'package:flutter/material.dart';
import '../../features/splash/splash_page.dart';
import '../../features/learning/presentation/pages/journey_page.dart';

class AppRoutes {
  static Map<String, WidgetBuilder> get routes => {
        '/': (context) => const SplashPage(),
        '/learning': (context) => const JourneyPage(),
      };
}
