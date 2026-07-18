import 'package:hive/hive.dart';
import '../../domain/entities/lesson.dart';

part 'lesson_content_model.g.dart';

@HiveType(typeId: 13)
class LessonContentModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String text;

  LessonContentModel({
    required this.id,
    required this.text,
  });

  factory LessonContentModel.fromEntity(LessonContent entity) {
    return LessonContentModel(
      id: entity.id,
      text: entity.text,
    );
  }

  LessonContent toEntity() {
    return LessonContent(
      id: id,
      text: text,
    );
  }
}
