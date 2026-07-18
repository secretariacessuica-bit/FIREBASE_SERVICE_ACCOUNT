import 'package:hive/hive.dart';
import '../../domain/entities/lesson_progress.dart';

part 'lesson_progress_model.g.dart';

@HiveType(typeId: 19)
class LessonProgressModel {
  @HiveField(0)
  final String lessonId;
  @HiveField(1)
  final String userId;
  @HiveField(2)
  final bool isCompleted;
  @HiveField(3)
  final int lastAccessedExerciseIndex;
  @HiveField(4)
  final DateTime? lastAccessedAt;

  LessonProgressModel({
    required this.lessonId,
    required this.userId,
    required this.isCompleted,
    required this.lastAccessedExerciseIndex,
    this.lastAccessedAt,
  });

  factory LessonProgressModel.fromEntity(LessonProgress entity) {
    return LessonProgressModel(
      lessonId: entity.lessonId,
      userId: entity.userId,
      isCompleted: entity.isCompleted,
      lastAccessedExerciseIndex: entity.lastAccessedExerciseIndex,
      lastAccessedAt: entity.lastAccessedAt,
    );
  }

  LessonProgress toEntity() {
    return LessonProgress(
      lessonId: lessonId,
      userId: userId,
      isCompleted: isCompleted,
      lastAccessedExerciseIndex: lastAccessedExerciseIndex,
      lastAccessedAt: lastAccessedAt,
    );
  }
}
