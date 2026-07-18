/// An auditable record of a change applied to a [CognitiveTrait].
///
/// Stored in [CognitiveProfile.recentChanges] (capped at 50 entries) to
/// enable:
/// - Debugging and explainability ("why is StoryQuest being recommended?")
/// - Analytics
/// - Audit without re-reading all historical evidences
class TraitChange {
  final String traitKey;

  final double previousValue;
  final double currentValue;

  final double previousConfidence;
  final double currentConfidence;

  final double previousStability;
  final double currentStability;

  /// IDs of the [LearningEvidence] records that caused this change.
  final List<String> evidenceIds;

  final DateTime changedAt;

  /// Version of the [HorizonCognitiveEngine] that produced this change.
  final int engineVersion;

  const TraitChange({
    required this.traitKey,
    required this.previousValue,
    required this.currentValue,
    required this.previousConfidence,
    required this.currentConfidence,
    required this.previousStability,
    required this.currentStability,
    required this.evidenceIds,
    required this.changedAt,
    required this.engineVersion,
  });

  double get valueDelta => currentValue - previousValue;
}
