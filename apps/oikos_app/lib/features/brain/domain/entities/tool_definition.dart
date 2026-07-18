import '../../../domain/entities/age_experience_mode.dart';
import 'mood_hint.dart';

class ToolDefinition {
  final String id;
  final String emoji;
  final String name;
  final List<AgeExperienceMode> suitableModes;
  final List<MoodHint> suitableMoods;
  final int minMinutes;
  final int maxMinutes;
  final bool isImplemented;

  const ToolDefinition({
    required this.id,
    required this.emoji,
    required this.name,
    required this.suitableModes,
    required this.suitableMoods,
    required this.minMinutes,
    required this.maxMinutes,
    required this.isImplemented,
  });
}
