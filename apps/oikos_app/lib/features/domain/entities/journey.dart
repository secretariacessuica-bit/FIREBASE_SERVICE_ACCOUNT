class Journey {
  final String id;
  final String userId;
  final String title;
  final String currentLessonName;
  final double progressPercentage; // 0.0 to 1.0

  const Journey({
    required this.id,
    required this.userId,
    required this.title,
    required this.currentLessonName,
    required this.progressPercentage,
  });
}
