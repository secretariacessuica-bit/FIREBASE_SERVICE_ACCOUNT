import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';
import '../../../domain/entities/family_member.dart';
import '../../../presentation/providers/di_providers.dart';
import '../../domain/onboarding_state.dart';
import '../../domain/submit_onboarding_usecase.dart';
import '../../../presentation/providers/app_bootstrap_provider.dart';
import '../../../avatar/domain/avatar.dart';
final onboardingWizardProvider = StateNotifierProvider<OnboardingWizardNotifier, OnboardingState>((ref) {
  final submitUseCase = ref.watch(submitOnboardingUseCaseProvider);
  return OnboardingWizardNotifier(submitUseCase, ref);
});

class OnboardingWizardNotifier extends StateNotifier<OnboardingState> {
  final SubmitOnboardingUseCase _submitUseCase;
  final Ref ref;

  OnboardingWizardNotifier(this._submitUseCase, this.ref) : super(OnboardingState());

  void setFamilyName(String name) {
    state = state.copyWith(familyName: name);
  }

  String? _getAvatarAsset(String emoji) {
    if (emoji.contains('👨')) return 'assets/images/avatars/dad_avatar.png';
    if (emoji.contains('👩')) return 'assets/images/avatars/mom_avatar.png';
    if (emoji.contains('👦')) return 'assets/images/avatars/boy_avatar.png';
    if (emoji.contains('👧')) return 'assets/images/avatars/girl_avatar.png';
    return null;
  }

  void setGuardian(String name, String emoji, String colorHex, {OikosAvatar? avatar}) {
    final guardian = FamilyMember(
      id: const Uuid().v4(),
      familyId: '', // vai ser preenchido no submit
      name: name,
      emoji: emoji,
      colorHex: colorHex,
      lastLogin: DateTime.now(),
      avatarAsset: avatar?.toJsonString() ?? _getAvatarAsset(emoji),
    );
    state = state.copyWith(guardian: guardian);
  }

  void addChild(String name, String emoji, String colorHex, {OikosAvatar? avatar}) {
    final child = FamilyMember(
      id: const Uuid().v4(),
      familyId: '', 
      name: name,
      emoji: emoji,
      colorHex: colorHex,
      lastLogin: DateTime.now(),
      avatarAsset: avatar?.toJsonString() ?? _getAvatarAsset(emoji),
    );
    state = state.copyWith(children: [...state.children, child]);
  }

  void removeChild(String id) {
    state = state.copyWith(
      children: state.children.where((c) => c.id != id).toList(),
    );
  }

  void setPin(String pin) {
    state = state.copyWith(firstPin: pin);
  }

  void nextStep() {
    state = state.copyWith(currentStep: state.currentStep + 1);
  }

  void previousStep() {
    if (state.currentStep > 0) {
      state = state.copyWith(currentStep: state.currentStep - 1);
    }
  }

  Future<void> submit() async {
    state = state.copyWith(isSubmitting: true);
    try {
      await _submitUseCase.execute(state);
      state = state.copyWith(
        isSubmitting: false, 
        isCompleted: true,
        currentStep: 6, // Advance to SummaryPage
      );
      ref.read(appBootstrapProvider.notifier).completeOnboarding();
    } catch (e) {
      state = state.copyWith(isSubmitting: false);
      // Aqui poderíamos disparar um erro
      rethrow;
    }
  }
}
