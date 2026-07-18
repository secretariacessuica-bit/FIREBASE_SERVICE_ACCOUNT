class LessonResult {
  final String lessonId;
  final int correctAnswers;
  final int wrongAnswers;
  final int earnedXp;
  final bool completed;

  const LessonResult({
    required this.lessonId,
    required this.correctAnswers,
    required this.wrongAnswers,
    required this.earnedXp,
    required this.completed,
  });

  LessonResult copyWith({
    String? lessonId,
    int? correctAnswers,
    int? wrongAnswers,
    int? earnedXp,
    bool? completed,
  }) {
    return LessonResult(
      lessonId: lessonId ?? this.lessonId,
      correctAnswers: correctAnswers ?? this.correctAnswers,
      wrongAnswers: wrongAnswers ?? this.wrongAnswers,
      earnedXp: earnedXp ?? this.earnedXp,
      completed: completed ?? this.completed,
    );
  }
}
