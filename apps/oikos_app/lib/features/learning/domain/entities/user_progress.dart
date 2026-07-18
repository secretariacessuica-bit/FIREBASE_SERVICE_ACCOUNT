class UserProgress {
  final String userId;
  final String journeyId;
  final List<String> completedLessonIds;
  final String? currentLessonId;
  final int totalXpEarned;
  final List<String> unlockedRewardIds;

  const UserProgress({
    required this.userId,
    required this.journeyId,
    required this.completedLessonIds,
    this.currentLessonId,
    required this.totalXpEarned,
    required this.unlockedRewardIds,
  });

  UserProgress copyWith({
    String? userId,
    String? journeyId,
    List<String>? completedLessonIds,
    String? currentLessonId,
    int? totalXpEarned,
    List<String>? unlockedRewardIds,
  }) {
    return UserProgress(
      userId: userId ?? this.userId,
      journeyId: journeyId ?? this.journeyId,
      completedLessonIds: completedLessonIds ?? this.completedLessonIds,
      currentLessonId: currentLessonId ?? this.currentLessonId,
      totalXpEarned: totalXpEarned ?? this.totalXpEarned,
      unlockedRewardIds: unlockedRewardIds ?? this.unlockedRewardIds,
    );
  }
}
