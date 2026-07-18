/// Marks the position of the last processed [LearningEvidence] in the
/// incremental update pipeline of the [CognitiveProfile].
///
/// The cursor is ordered lexicographically: first by [occurredAt], then by
/// [sequence] — guaranteeing determinism even when multiple evidences share
/// the same timestamp.
class EvidenceCursor {
  final DateTime occurredAt;

  /// Monotonic, stable integer unique within the same [occurredAt] instant.
  /// In a local (offline-first) context this is set by the
  /// [LearningEvidenceRepository] at write time.
  final int sequence;

  const EvidenceCursor({
    required this.occurredAt,
    required this.sequence,
  });

  /// Returns true if this cursor is strictly *before* [other].
  bool isBefore(EvidenceCursor other) {
    if (occurredAt.isBefore(other.occurredAt)) return true;
    if (occurredAt.isAfter(other.occurredAt)) return false;
    return sequence < other.sequence;
  }

  /// Returns true if this cursor is strictly *after* [other].
  bool isAfter(EvidenceCursor other) => other.isBefore(this);

  @override
  String toString() =>
      'EvidenceCursor(${occurredAt.toIso8601String()}, seq=$sequence)';
}
