class FamilyActivity {
  final String id;
  final String memberId;
  final String eventType;
  final DateTime date;
  final Map<String, dynamic>? metadata;

  const FamilyActivity({
    required this.id,
    required this.memberId,
    required this.eventType,
    required this.date,
    this.metadata,
  });
}
