import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/onboarding_wizard_provider.dart';
import '../widgets/onboarding_scaffold.dart';

class FamilyNamePage extends ConsumerStatefulWidget {
  const FamilyNamePage({super.key});

  @override
  ConsumerState<FamilyNamePage> createState() => _FamilyNamePageState();
}

class _FamilyNamePageState extends ConsumerState<FamilyNamePage> {
  late TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    final state = ref.read(onboardingWizardProvider);
    _controller = TextEditingController(text: state.familyName);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onNext() {
    final name = _controller.text.trim();
    if (name.isNotEmpty) {
      ref.read(onboardingWizardProvider.notifier).setFamilyName(name);
      ref.read(onboardingWizardProvider.notifier).nextStep();
    }
  }

  @override
  Widget build(BuildContext context) {
    return OnboardingScaffold(
      title: 'Nome da Família',
      subtitle: 'Como sua família gostaria de ser conhecida aqui?',
      progress: 0.15,
      onBack: () => ref.read(onboardingWizardProvider.notifier).previousStep(),
      onNext: _onNext,
      isNextEnabled: _controller.text.trim().isNotEmpty,
      child: TextField(
        controller: _controller,
        autofocus: true,
        textCapitalization: TextCapitalization.words,
        onChanged: (_) => setState(() {}),
        onSubmitted: (_) => _onNext(),
        decoration: InputDecoration(
          hintText: 'Ex: Família Oliveira',
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          filled: true,
          fillColor: Colors.black.withValues(alpha: 0.05),
          contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
        ),
        style: const TextStyle(fontSize: 18),
      ),
    );
  }
}
