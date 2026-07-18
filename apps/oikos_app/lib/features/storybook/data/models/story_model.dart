import 'package:hive/hive.dart';
import '../../domain/entities/story.dart';
import '../../domain/entities/story_illustration_type.dart';
import '../../domain/entities/story_mood.dart';

part 'story_model.g.dart';

@HiveType(typeId: 51)
class StoryModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String narrative;
  @HiveField(3)
  final String lumoReflection;
  @HiveField(4)
  final DateTime date;
  @HiveField(5)
  final String moodName;
  @HiveField(6)
  final String illustrationName;

  StoryModel({
    required this.id,
    required this.title,
    required this.narrative,
    required this.lumoReflection,
    required this.date,
    required this.moodName,
    required this.illustrationName,
  });

  factory StoryModel.fromEntity(Story entity) {
    return StoryModel(
      id: entity.id,
      title: entity.title,
      narrative: entity.narrative,
      lumoReflection: entity.lumoReflection,
      date: entity.date,
      moodName: entity.mood.name,
      illustrationName: entity.illustration.name,
    );
  }

  Story toEntity() {
    return Story(
      id: id,
      title: title,
      narrative: narrative,
      lumoReflection: lumoReflection,
      date: date,
      mood: StoryMood.values.firstWhere((e) => e.name == moodName, orElse: () => StoryMood.discovery),
      illustration: StoryIllustrationType.values.firstWhere((e) => e.name == illustrationName, orElse: () => StoryIllustrationType.book),
    );
  }
}
