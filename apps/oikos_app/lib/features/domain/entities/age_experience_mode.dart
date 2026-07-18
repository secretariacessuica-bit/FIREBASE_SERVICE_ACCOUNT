import 'package:hive/hive.dart';

part 'age_experience_mode.g.dart';

@HiveType(typeId: 80)
enum AgeExperienceMode {
  @HiveField(0)
  earlyChildhood, // até 7
  @HiveField(1)
  explorer,       // 8–11
  @HiveField(2)
  teen,           // 12–15
  @HiveField(3)
  youngMentor,    // 16–18
  @HiveField(4)
  adult,          // 19-59
  @HiveField(5)
  senior,         // 60+
}
