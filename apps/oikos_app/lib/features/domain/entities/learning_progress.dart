class LearningProgress {
  final String userId;
  final int currentStreak;
  final int totalLessonsCompleted;
  final DateTime lastLearningDate;

  const LearningProgress({
    required this.userId,
    required this.currentStreak,
    required this.totalLessonsCompleted,
    required this.lastLearningDate,
  });
}
