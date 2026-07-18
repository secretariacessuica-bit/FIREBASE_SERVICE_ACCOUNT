/// A single inferred characteristic of a learner's cognitive style.
///
/// The triplet [value], [confidenceScore], and [stability] captures:
/// - **What** the system believes about this learner's trait.
/// - **How certain** it is (based on quality, quantity, diversity, and
///   consistency of evidence).
/// - **How resistant** this trait is to change (behavioural inertia).
///
/// The [LearningBrain] computes the effective weight for each trait
/// independently via its own `computeWeight(trait)` method, keeping
/// inference logic out of this entity.
class CognitiveTrait {
  /// Unique key identifying this trait.
  /// e.g. 'narrative_preference', 'attention_span_minutes', 'gamification_response'
  final String key;

  /// Inferred intensity in the range [-1.0, +1.0].
  /// - Negative → aversion (learner performs worse / disengages).
  /// - Positive → preference (learner performs better / engages).
  final double value;

  /// Degree of certainty in [value] in the range [0.0, 1.0].
  ///
  /// Considers quantity, quality, consistency, recency, and source diversity
  /// of the contributing evidence. A hundred weak, duplicate evidences must
  /// NOT produce artificially high confidence.
  final double confidenceScore;

  /// Resistance to change in the range [0.0, 1.0].
  ///
  /// Independent from [confidenceScore]:
  /// - Rises slowly when evidences are consistent with the current value.
  /// - Falls slowly when evidences are contradictory.
  /// - Affects the effective learning rate used by the [HorizonCognitiveEngine]:
  ///   a trait with stability close to 1.0 requires strong evidence to shift.
  final double stability;

  /// Number of evidence units that have contributed to this trait.
  /// Used as one signal for computing [confidenceScore].
  final int evidenceCount;

  final DateTime lastUpdated;

  const CognitiveTrait({
    required this.key,
    required this.value,
    required this.confidenceScore,
    required this.stability,
    required this.evidenceCount,
    required this.lastUpdated,
  })  : assert(value >= -1.0 && value <= 1.0, 'value must be in [-1.0, 1.0]'),
        assert(
            confidenceScore >= 0.0 && confidenceScore <= 1.0,
            'confidenceScore must be in [0.0, 1.0]'),
        assert(stability >= 0.0 && stability <= 1.0,
            'stability must be in [0.0, 1.0]');

  /// Creates a brand-new, unexplored trait with neutral baseline values.
  factory CognitiveTrait.initial({
    required String key,
    required DateTime createdAt,
  }) {
    return CognitiveTrait(
      key: key,
      value: 0.0,
      confidenceScore: 0.0,
      stability: 0.0,
      evidenceCount: 0,
      lastUpdated: createdAt,
    );
  }

  CognitiveTrait copyWith({
    double? value,
    double? confidenceScore,
    double? stability,
    int? evidenceCount,
    DateTime? lastUpdated,
  }) {
    return CognitiveTrait(
      key: key,
      value: (value ?? this.value).clamp(-1.0, 1.0),
      confidenceScore: (confidenceScore ?? this.confidenceScore).clamp(0.0, 1.0),
      stability: (stability ?? this.stability).clamp(0.0, 1.0),
      evidenceCount: evidenceCount ?? this.evidenceCount,
      lastUpdated: lastUpdated ?? this.lastUpdated,
    );
  }
}
