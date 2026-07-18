class Insight {
  final String id;
  final String householdId;
  final String content;
  final DateTime generatedAt;
  final bool acceptedByFamily;

  const Insight({
    required this.id,
    required this.householdId,
    required this.content,
    required this.generatedAt,
    this.acceptedByFamily = false,
  });
}
