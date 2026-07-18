import 'package:hive_flutter/hive_flutter.dart';
import '../../features/data/models/family_model.dart';
import '../../features/data/models/family_member_model.dart';
import '../../features/data/models/settings_model.dart';
import '../../features/data/models/pin_data_model.dart';
import '../../features/data/models/mission_model.dart';
import '../../features/data/models/journey_model.dart';
import '../../features/data/models/lesson_model.dart';
import '../../features/data/models/learning_progress_model.dart';
import '../../features/domain/entities/age_experience_mode.dart';
import '../../features/brain/data/models/learning_event_model.dart';
import '../../features/brain/data/repositories/hive_trajectory_repository.dart';

class HiveInitializer {
  static Future<void> initialize() async {
    await Hive.initFlutter();
    
    Hive.registerAdapter(AgeExperienceModeAdapter());
    Hive.registerAdapter(FamilyModelAdapter());
    Hive.registerAdapter(FamilyMemberModelAdapter());
    Hive.registerAdapter(SettingsModelAdapter());
    Hive.registerAdapter(PinDataModelAdapter());
    Hive.registerAdapter(MissionModelAdapter());
    Hive.registerAdapter(JourneyModelAdapter());
    Hive.registerAdapter(LessonModelAdapter());
    Hive.registerAdapter(LearningProgressModelAdapter());
    Hive.registerAdapter(LearningEventModelAdapter());

    await Hive.openBox<FamilyModel>('familyBox');
    await Hive.openBox<FamilyMemberModel>('familyMembersBox');
    await Hive.openBox<SettingsModel>('settingsBox');
    await Hive.openBox<PinDataModel>('pinDataBox');
    await Hive.openBox<MissionModel>('missionsBox');
    await Hive.openBox<JourneyModel>('journeysBox');
    await Hive.openBox<LessonModel>('lessonsBox');
    await Hive.openBox<LearningProgressModel>('learningProgressBox');
    await Hive.openBox<int>('xpBox'); // XP por membro — sem adapter (tipo primitivo)
    await Hive.openBox<LearningEventModel>(HiveTrajectoryRepository.boxName);
  }
}
