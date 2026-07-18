import 'package:uuid/uuid.dart';
import '../../features/domain/entities/family.dart';
import '../../features/domain/entities/family_member.dart';
import '../../features/domain/entities/settings.dart';
import '../../features/domain/entities/pin_data.dart';
import '../../features/domain/repositories/family_repository.dart';
import '../../features/domain/repositories/settings_repository.dart';
import '../../features/domain/repositories/auth_repository.dart';
import '../../features/domain/entities/age_experience_mode.dart';

class SeedService {
  final FamilyRepository familyRepository;
  final SettingsRepository settingsRepository;
  final AuthRepository authRepository;

  SeedService(this.familyRepository, this.settingsRepository, this.authRepository);

  Future<void> seedIfEmpty() async {
    const useSeed = bool.fromEnvironment('USE_SEED', defaultValue: false);
    if (!useSeed) {
      return;
    }

    final settings = await settingsRepository.getSettings();
    if (settings.isFirstLaunch) {
      const uuid = Uuid();
      final familyId = uuid.v4();
      
      final family = Family(
        id: familyId,
        name: 'Família Oliveira',
        createdAt: DateTime.now(),
      );
      
      final joaoId = uuid.v4();
      final members = [
        FamilyMember(id: joaoId, familyId: familyId, name: 'Papai', emoji: '👨', colorHex: '#4CAF50', lastLogin: DateTime.now(), avatarAsset: 'assets/images/avatars/dad_avatar.png', birthDate: DateTime.now().subtract(const Duration(days: 365 * 35)), experienceMode: AgeExperienceMode.adult),
        FamilyMember(id: uuid.v4(), familyId: familyId, name: 'Mamãe', emoji: '👩', colorHex: '#E91E63', lastLogin: DateTime.now(), avatarAsset: 'assets/images/avatars/mom_avatar.png', birthDate: DateTime.now().subtract(const Duration(days: 365 * 32)), experienceMode: AgeExperienceMode.adult),
        FamilyMember(id: uuid.v4(), familyId: familyId, name: 'Lorenzo', emoji: '👦', colorHex: '#2196F3', lastLogin: DateTime.now(), avatarAsset: 'assets/images/avatars/boy_avatar.png', birthDate: DateTime.now().subtract(const Duration(days: 365 * 10)), experienceMode: AgeExperienceMode.explorer),
        FamilyMember(id: uuid.v4(), familyId: familyId, name: 'Sofia', emoji: '👧', colorHex: '#E040FB', lastLogin: DateTime.now(), avatarAsset: 'assets/images/avatars/girl_avatar.png', birthDate: DateTime.now().subtract(const Duration(days: 365 * 4)), experienceMode: AgeExperienceMode.earlyChildhood),
      ];
      
      await familyRepository.saveFamily(family);
      await familyRepository.saveFamilyMembers(members);
      
      // Default pin is 1234 for everyone for testing
      for (var member in members) {
        await authRepository.savePinData(PinData(userId: member.id, hashedPin: '1234'));
      }
      
      await settingsRepository.saveSettings(Settings(
        isFirstLaunch: false,
        lastFamilyId: familyId,
      ));
    }
  }
}
