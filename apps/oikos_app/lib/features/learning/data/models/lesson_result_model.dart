import 'package:hive/hive.dart';
import '../../domain/entities/lesson_result.dart';

part 'lesson_result_model.g.dart';

@HiveType(typeId: 20)
class LessonResultModel {
  @HiveField(0)
  final String lessonId;
  @HiveField(1)
  final int correctAnswers;
  @HiveField(2)
  final int wrongAnswers;
  @HiveField(3)
  final int earnedXp;
  @HiveField(4)
  final bool completed;

  LessonResultModel({
    required this.lessonId,
    required this.correctAnswers,
    required this.wrongAnswers,
    required this.earnedXp,
    required this.completed,
  });

  factory LessonResultModel.fromEntity(LessonResult entity) {
    return LessonResultModel(
      lessonId: entity.lessonId,
      correctAnswers: entity.correctAnswers,
      wrongAnswers: entity.wrongAnswers,
      earnedXp: entity.earnedXp,
      completed: entity.completed,
    );
  }

  LessonResult toEntity() {
    return LessonResult(
      lessonId: lessonId,
      correctAnswers: correctAnswers,
      wrongAnswers: wrongAnswers,
      earnedXp: earnedXp,
      completed: completed,
    );
  }
}
