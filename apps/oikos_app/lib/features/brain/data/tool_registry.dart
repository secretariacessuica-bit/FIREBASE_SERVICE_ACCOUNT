import '../domain/entities/tool_definition.dart';
import '../domain/entities/mood_hint.dart';
import '../../domain/entities/age_experience_mode.dart';
import '../domain/entities/difficulty_level.dart';

class ToolRegistry {
  static const List<ToolDefinition> all = [
    ToolDefinition(
      id: 'story',
      emoji: '📖',
      name: 'Story Quest',
      suitableModes: [
        AgeExperienceMode.earlyChildhood,
        AgeExperienceMode.explorer,
      ],
      suitableMoods: [
        MoodHint.calm,
        MoodHint.playful,
        MoodHint.tired,
      ],
      minMinutes: 5,
      maxMinutes: 15,
      isImplemented: true,
    ),
    ToolDefinition(
      id: 'conversation',
      emoji: '🎤',
      name: 'Conversation',
      suitableModes: [
        AgeExperienceMode.teen,
        AgeExperienceMode.youngMentor,
        AgeExperienceMode.adult,
        AgeExperienceMode.senior,
      ],
      suitableMoods: [
        MoodHint.energetic,
        MoodHint.focused,
      ],
      minMinutes: 5,
      maxMinutes: 30,
      isImplemented: true,
    ),
    ToolDefinition(
      id: 'flashcard',
      emoji: '📚',
      name: 'Spaced Repetition',
      suitableModes: [
        AgeExperienceMode.teen,
        AgeExperienceMode.youngMentor,
        AgeExperienceMode.adult,
        AgeExperienceMode.senior,
      ],
      suitableMoods: [
        MoodHint.focused,
        MoodHint.calm,
        MoodHint.competitive,
      ],
      minMinutes: 3,
      maxMinutes: 15,
      isImplemented: true,
    ),
    ToolDefinition(
      id: 'game',
      emoji: '🎮',
      name: 'Time Attack',
      suitableModes: [
        AgeExperienceMode.explorer,
        AgeExperienceMode.teen,
        AgeExperienceMode.youngMentor,
      ],
      suitableMoods: [
        MoodHint.energetic,
        MoodHint.competitive,
        MoodHint.playful,
      ],
      minMinutes: 3,
      maxMinutes: 10,
      isImplemented: true,
    ),
    ToolDefinition(
      id: 'movie',
      emoji: '🎬',
      name: 'Movie Clips',
      suitableModes: [
        AgeExperienceMode.teen,
        AgeExperienceMode.youngMentor,
        AgeExperienceMode.adult,
        AgeExperienceMode.senior,
      ],
      suitableMoods: [
        MoodHint.tired,
        MoodHint.calm,
      ],
      minMinutes: 10,
      maxMinutes: 30,
      isImplemented: false,
    ),
  ];

  static ToolDefinition? findById(String id) {
    try {
      return all.firstWhere((t) => t.id == id);
    } catch (_) {
      return null;
    }
  }

  static List<ToolDefinition> implemented() {
    return all.where((t) => t.isImplemented).toList();
  }
}
