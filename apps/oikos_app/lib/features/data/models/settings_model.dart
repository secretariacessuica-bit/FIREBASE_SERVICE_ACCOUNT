import 'package:hive/hive.dart';
import '../../domain/entities/settings.dart';

part 'settings_model.g.dart';

@HiveType(typeId: 3)
class SettingsModel extends HiveObject {
  @HiveField(0)
  final bool isFirstLaunch;

  @HiveField(1)
  final String? lastFamilyId;

  SettingsModel({
    required this.isFirstLaunch,
    this.lastFamilyId,
  });

  factory SettingsModel.fromEntity(Settings entity) {
    return SettingsModel(
      isFirstLaunch: entity.isFirstLaunch,
      lastFamilyId: entity.lastFamilyId,
    );
  }

  Settings toEntity() {
    return Settings(
      isFirstLaunch: isFirstLaunch,
      lastFamilyId: lastFamilyId,
    );
  }
}
