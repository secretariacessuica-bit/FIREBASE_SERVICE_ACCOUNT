import 'package:flutter/material.dart';
import '../../features/splash/splash_page.dart';
import '../../features/onboarding/onboarding_page.dart';
import '../../features/avatar/avatar_page.dart';
import '../../features/pin/pin_page.dart';
import '../../features/home/home_page.dart';

class AppRoutes {
  static Map<String, WidgetBuilder> get routes => {
        '/': (context) => const SplashPage(),
        '/onboarding': (context) => const OnboardingPage(),
        '/avatar': (context) => const AvatarPage(),
        '/pin': (context) => const PinPage(),
        '/home': (context) => const HomePage(),
      };
}
