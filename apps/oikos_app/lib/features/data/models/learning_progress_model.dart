import 'package:hive/hive.dart';
import '../../domain/entities/learning_progress.dart';

part 'learning_progress_model.g.dart';

@HiveType(typeId: 7)
class LearningProgressModel extends HiveObject {
  @HiveField(0)
  final String userId;

  @HiveField(1)
  final int currentStreak;

  @HiveField(2)
  final int totalLessonsCompleted;

  @HiveField(3)
  final DateTime lastLearningDate;

  LearningProgressModel({
    required this.userId,
    required this.currentStreak,
    required this.totalLessonsCompleted,
    required this.lastLearningDate,
  });

  factory LearningProgressModel.fromEntity(LearningProgress entity) {
    return LearningProgressModel(
      userId: entity.userId,
      currentStreak: entity.currentStreak,
      totalLessonsCompleted: entity.totalLessonsCompleted,
      lastLearningDate: entity.lastLearningDate,
    );
  }

  LearningProgress toEntity() {
    return LearningProgress(
      userId: userId,
      currentStreak: currentStreak,
      totalLessonsCompleted: totalLessonsCompleted,
      lastLearningDate: lastLearningDate,
    );
  }
}
