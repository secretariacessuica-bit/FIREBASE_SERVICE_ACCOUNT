import 'package:flutter/material.dart';
import '../../features/splash/splash_page.dart';
import '../../features/learning/presentation/pages/journey_page.dart';
import '../../features/onboarding/presentation/pages/age_selection_page.dart';
import '../../features/onboarding/presentation/pages/adult_chat_page.dart';
import '../../features/onboarding/presentation/pages/kids_welcome_page.dart';
import '../../features/family/presentation/pages/family_settings_page.dart';

class AppRoutes {
  static Map<String, WidgetBuilder> get routes => {
        '/': (context) => const SplashPage(),
        '/learning': (context) => const JourneyPage(),
        '/onboarding/age_selection': (context) => const AgeSelectionPage(),
        '/onboarding/adult_chat': (context) => const AdultChatPage(),
        '/onboarding/kids_welcome': (context) => const KidsWelcomePage(),
        '/settings/family': (context) => const FamilySettingsPage(),
      };
}
