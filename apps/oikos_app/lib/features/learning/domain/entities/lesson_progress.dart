class LessonProgress {
  final String lessonId;
  final String userId;
  final bool isCompleted;
  final int lastAccessedExerciseIndex;
  final DateTime? lastAccessedAt;

  const LessonProgress({
    required this.lessonId,
    required this.userId,
    required this.isCompleted,
    required this.lastAccessedExerciseIndex,
    this.lastAccessedAt,
  });

  LessonProgress copyWith({
    String? lessonId,
    String? userId,
    bool? isCompleted,
    int? lastAccessedExerciseIndex,
    DateTime? lastAccessedAt,
  }) {
    return LessonProgress(
      lessonId: lessonId ?? this.lessonId,
      userId: userId ?? this.userId,
      isCompleted: isCompleted ?? this.isCompleted,
      lastAccessedExerciseIndex: lastAccessedExerciseIndex ?? this.lastAccessedExerciseIndex,
      lastAccessedAt: lastAccessedAt ?? this.lastAccessedAt,
    );
  }
}
