class Mission {
  final String id;
  final String familyId;
  final String title;
  final int totalSteps;
  final int completedSteps;
  final bool isCompleted;

  const Mission({
    required this.id,
    required this.familyId,
    required this.title,
    required this.totalSteps,
    required this.completedSteps,
    required this.isCompleted,
  });
}
