import 'package:hive/hive.dart';
import '../../domain/entities/lesson.dart';

part 'lesson_model.g.dart';

@HiveType(typeId: 6)
class LessonModel extends HiveObject {
  @HiveField(0)
  final String id;

  @HiveField(1)
  final String journeyId;

  @HiveField(2)
  final String name;

  @HiveField(3)
  final bool isCompleted;

  LessonModel({
    required this.id,
    required this.journeyId,
    required this.name,
    required this.isCompleted,
  });

  factory LessonModel.fromEntity(Lesson entity) {
    return LessonModel(
      id: entity.id,
      journeyId: entity.journeyId,
      name: entity.name,
      isCompleted: entity.isCompleted,
    );
  }

  Lesson toEntity() {
    return Lesson(
      id: id,
      journeyId: journeyId,
      name: name,
      isCompleted: isCompleted,
    );
  }
}
