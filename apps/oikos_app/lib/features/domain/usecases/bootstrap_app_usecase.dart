import '../repositories/settings_repository.dart';
import '../repositories/family_repository.dart';

class BootstrapAppUseCase {
  final SettingsRepository settingsRepository;
  final FamilyRepository familyRepository;

  BootstrapAppUseCase(this.settingsRepository, this.familyRepository);

  Future<bool> isFirstLaunch() async {
    final settings = await settingsRepository.getSettings();
    return settings.isFirstLaunch;
  }
  
  Future<bool> hasFamily() async {
    final family = await familyRepository.getFamily();
    return family != null;
  }
}
