import 'package:hive/hive.dart';
import '../../domain/entities/user_progress.dart';

part 'user_progress_model.g.dart';

@HiveType(typeId: 18)
class UserProgressModel {
  @HiveField(0)
  final String userId;
  @HiveField(1)
  final String journeyId;
  @HiveField(2)
  final List<String> completedLessonIds;
  @HiveField(3)
  final String? currentLessonId;
  @HiveField(4)
  final int totalXpEarned;
  @HiveField(5)
  final List<String> unlockedRewardIds;

  UserProgressModel({
    required this.userId,
    required this.journeyId,
    required this.completedLessonIds,
    this.currentLessonId,
    required this.totalXpEarned,
    required this.unlockedRewardIds,
  });

  factory UserProgressModel.fromEntity(UserProgress entity) {
    return UserProgressModel(
      userId: entity.userId,
      journeyId: entity.journeyId,
      completedLessonIds: entity.completedLessonIds,
      currentLessonId: entity.currentLessonId,
      totalXpEarned: entity.totalXpEarned,
      unlockedRewardIds: entity.unlockedRewardIds,
    );
  }

  UserProgress toEntity() {
    return UserProgress(
      userId: userId,
      journeyId: journeyId,
      completedLessonIds: completedLessonIds,
      currentLessonId: currentLessonId,
      totalXpEarned: totalXpEarned,
      unlockedRewardIds: unlockedRewardIds,
    );
  }
}
