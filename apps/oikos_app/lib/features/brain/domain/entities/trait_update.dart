/// Intermediate value object produced by the [HorizonCognitiveEngine] when
/// translating a [LearningEvidence] into an intended change for one trait.
///
/// The engine operates in two steps:
/// 1. **Inference**: Evidence → List<TraitUpdate>  (what *should* change?)
/// 2. **Application**: TraitUpdate → CognitiveTrait  (how much does it change,
///    given current stability and learning rate?)
///
/// Splitting these steps makes each independently testable and allows
/// combining multiple evidences into a single update before touching a trait.
class TraitUpdate {
  const TraitUpdate({
    required this.traitKey,
    required this.targetValue,
    required this.evidenceWeight,
    required this.confidenceImpact,
    required this.stabilityImpact,
    required this.sourceEvidenceIds,
  });

  /// The trait this update targets.
  final String traitKey;

  /// The value that the evidence *suggests* the trait should move toward.
  /// Range [-1.0, +1.0].
  ///
  /// The engine does **not** jump to this value; it moves the current value
  /// incrementally using the online-learning formula:
  ///   newValue = currentValue + effectiveLearningRate × (targetValue − currentValue)
  final double targetValue;

  /// Aggregated strength of the contributing evidences. Range [0.0, 1.0].
  /// Higher weight → larger step toward [targetValue].
  final double evidenceWeight;

  /// Proposed signed change to [CognitiveTrait.confidenceScore].
  final double confidenceImpact;

  /// Proposed signed change to [CognitiveTrait.stability].
  /// Positive if evidence is consistent with the current value,
  /// negative if contradictory.
  final double stabilityImpact;

  /// IDs of the [LearningEvidence] records that produced this update.
  /// Forwarded verbatim into [TraitChange.evidenceIds] for auditability.
  final List<String> sourceEvidenceIds;
}
