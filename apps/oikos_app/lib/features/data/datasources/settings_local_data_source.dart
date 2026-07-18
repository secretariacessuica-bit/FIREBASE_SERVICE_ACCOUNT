import 'package:hive/hive.dart';
import '../models/settings_model.dart';

class SettingsLocalDataSource {
  final Box<SettingsModel> settingsBox;

  SettingsLocalDataSource(this.settingsBox);

  Future<SettingsModel> getSettings() async {
    if (settingsBox.isEmpty) {
      return SettingsModel(isFirstLaunch: true);
    }
    return settingsBox.getAt(0)!;
  }

  Future<void> saveSettings(SettingsModel settings) async {
    await settingsBox.put(0, settings);
  }
}
