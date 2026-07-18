import 'package:uuid/uuid.dart';
import '../../domain/entities/family.dart';
import '../../domain/entities/pin_data.dart';
import '../../domain/entities/settings.dart';
import '../../domain/repositories/auth_repository.dart';
import '../../domain/repositories/family_repository.dart';
import '../../domain/repositories/settings_repository.dart';
import 'onboarding_state.dart';

class SubmitOnboardingUseCase {
  final FamilyRepository familyRepository;
  final AuthRepository authRepository;
  final SettingsRepository settingsRepository;

  SubmitOnboardingUseCase({
    required this.familyRepository,
    required this.authRepository,
    required this.settingsRepository,
  });

  Future<void> execute(OnboardingState state) async {
    if (state.guardian == null || state.firstPin == null || state.familyName.isEmpty) {
      throw Exception('Missing required onboarding data');
    }

    const uuid = Uuid();
    final familyId = uuid.v4();

    // 1. Create family
    final family = Family(
      id: familyId,
      name: state.familyName,
      createdAt: DateTime.now(),
    );
    await familyRepository.saveFamily(family);

    // 2. Prepare members (Guardian + Children)
    final members = [
      state.guardian!.copyWith(familyId: familyId),
      ...state.children.map((child) => child.copyWith(familyId: familyId)),
    ];
    await familyRepository.saveFamilyMembers(members);

    // 3. Save PINs for everyone (in a real app, maybe children don't get PINs immediately, but MVP assigns same PIN)
    for (var member in members) {
      await authRepository.savePinData(PinData(userId: member.id, hashedPin: state.firstPin!));
    }

    // 4. Update settings
    await settingsRepository.saveSettings(Settings(
      isFirstLaunch: false,
      lastFamilyId: familyId,
    ));
  }
}
