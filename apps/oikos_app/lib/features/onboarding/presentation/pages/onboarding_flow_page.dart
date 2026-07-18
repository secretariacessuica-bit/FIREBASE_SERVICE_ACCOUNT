import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/onboarding_wizard_provider.dart';
import 'welcome_page.dart';
import 'family_name_page.dart';
import 'guardian_page.dart';
import 'children_page.dart';
import 'pin_setup_page.dart';
import 'review_page.dart';
import 'summary_page.dart';

class OnboardingFlowPage extends ConsumerWidget {
  const OnboardingFlowPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(onboardingWizardProvider);

    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 300),
      child: _buildPage(state.currentStep),
    );
  }

  Widget _buildPage(int step) {
    switch (step) {
      case 0:
        return const WelcomePage(key: ValueKey('step_0'));
      case 1:
        return const FamilyNamePage(key: ValueKey('step_1'));
      case 2:
        return const GuardianPage(key: ValueKey('step_2'));
      case 3:
        return const ChildrenPage(key: ValueKey('step_3'));
      case 4:
        return const PinSetupPage(key: ValueKey('step_4'));
      case 5:
        return const ReviewPage(key: ValueKey('step_5'));
      case 6:
        return const SummaryPage(key: ValueKey('step_6'));
      default:
        return const WelcomePage(key: ValueKey('step_0'));
    }
  }
}
