import 'package:hive/hive.dart';
import '../../domain/entities/story_chapter.dart';
import 'story_model.dart';

part 'story_chapter_model.g.dart';

@HiveType(typeId: 52)
class StoryChapterModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final DateTime period;
  @HiveField(3)
  final List<StoryModel> stories;

  StoryChapterModel({
    required this.id,
    required this.title,
    required this.period,
    required this.stories,
  });

  factory StoryChapterModel.fromEntity(StoryChapter entity) {
    return StoryChapterModel(
      id: entity.id,
      title: entity.title,
      period: entity.period,
      stories: entity.stories.map((s) => StoryModel.fromEntity(s)).toList(),
    );
  }

  StoryChapter toEntity() {
    return StoryChapter(
      id: id,
      title: title,
      period: period,
      stories: stories.map((s) => s.toEntity()).toList(),
    );
  }
}
