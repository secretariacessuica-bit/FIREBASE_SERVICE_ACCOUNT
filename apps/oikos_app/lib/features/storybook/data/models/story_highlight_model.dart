import 'package:hive/hive.dart';
import '../../domain/entities/story_highlight.dart';
import '../../domain/entities/story_illustration_type.dart';

part 'story_highlight_model.g.dart';

@HiveType(typeId: 53)
class StoryHighlightModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String illustrationName;
  @HiveField(3)
  final DateTime date;

  StoryHighlightModel({
    required this.id,
    required this.title,
    required this.illustrationName,
    required this.date,
  });

  factory StoryHighlightModel.fromEntity(StoryHighlight entity) {
    return StoryHighlightModel(
      id: entity.id,
      title: entity.title,
      illustrationName: entity.illustration.name,
      date: entity.date,
    );
  }

  StoryHighlight toEntity() {
    return StoryHighlight(
      id: id,
      title: title,
      illustration: StoryIllustrationType.values.firstWhere(
        (e) => e.name == illustrationName,
        orElse: () => StoryIllustrationType.stars,
      ),
      date: date,
    );
  }
}
