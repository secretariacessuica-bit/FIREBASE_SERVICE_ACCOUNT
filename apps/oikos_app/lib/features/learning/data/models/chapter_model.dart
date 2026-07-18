import 'package:hive/hive.dart';
import '../../domain/entities/chapter.dart';
import 'lesson_model.dart';

part 'chapter_model.g.dart';

@HiveType(typeId: 11)
class ChapterModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final List<LessonModel> lessons;
  @HiveField(4)
  final int order;
  @HiveField(5)
  final String? coverImageUrl;

  ChapterModel({
    required this.id,
    required this.title,
    required this.description,
    required this.lessons,
    required this.order,
    this.coverImageUrl,
  });

  factory ChapterModel.fromEntity(Chapter entity) {
    return ChapterModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      lessons: entity.lessons.map((l) => LessonModel.fromEntity(l)).toList(),
      order: entity.order,
      coverImageUrl: entity.coverImageUrl,
    );
  }

  Chapter toEntity() {
    return Chapter(
      id: id,
      title: title,
      description: description,
      lessons: lessons.map((l) => l.toEntity()).toList(),
      order: order,
      coverImageUrl: coverImageUrl,
    );
  }
}
