import 'package:hive/hive.dart';
import '../../domain/entities/lesson.dart';
import 'lesson_content_model.dart';
import 'exercise_model.dart';

part 'lesson_model.g.dart';

@HiveType(typeId: 12)
class LessonModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final LessonContentModel content;
  @HiveField(4)
  final List<ExerciseModel> exercises;
  @HiveField(5)
  final int order;

  LessonModel({
    required this.id,
    required this.title,
    required this.description,
    required this.content,
    required this.exercises,
    required this.order,
  });

  factory LessonModel.fromEntity(Lesson entity) {
    return LessonModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      content: LessonContentModel.fromEntity(entity.content),
      exercises: entity.exercises.map((e) => ExerciseModel.fromEntity(e)).toList(),
      order: entity.order,
    );
  }

  Lesson toEntity() {
    return Lesson(
      id: id,
      title: title,
      description: description,
      content: content.toEntity(),
      exercises: exercises.map((e) => e.toEntity()).toList(),
      order: order,
    );
  }
}
