import '../../domain/entities/family_member.dart';

class OnboardingState {
  final int currentStep;
  final String familyName;
  final FamilyMember? guardian;
  final List<FamilyMember> children;
  final String? firstPin;
  final bool isSubmitting;
  final bool isCompleted;

  OnboardingState({
    this.currentStep = 0,
    this.familyName = '',
    this.guardian,
    this.children = const [],
    this.firstPin,
    this.isSubmitting = false,
    this.isCompleted = false,
  });

  OnboardingState copyWith({
    int? currentStep,
    String? familyName,
    FamilyMember? guardian,
    List<FamilyMember>? children,
    String? firstPin,
    bool? isSubmitting,
    bool? isCompleted,
  }) {
    return OnboardingState(
      currentStep: currentStep ?? this.currentStep,
      familyName: familyName ?? this.familyName,
      guardian: guardian ?? this.guardian,
      children: children ?? this.children,
      firstPin: firstPin ?? this.firstPin,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      isCompleted: isCompleted ?? this.isCompleted,
    );
  }
}
