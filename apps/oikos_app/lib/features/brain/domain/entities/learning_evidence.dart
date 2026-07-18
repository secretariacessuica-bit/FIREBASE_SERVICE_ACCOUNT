class LearningEvidence {
  final String id;
  final String userId;
  final DateTime generatedAt;
  /// Monotonic counter for deterministic ordering within the same instant.
  /// Used by [EvidenceCursor] to guarantee incremental processing correctness.
  final int sequence;
  final String description; // e.g., "Alta persistência em atividades narrativas."
  final double confidenceWeight; // Peso dessa evidência (0.0 a 1.0)
  final String category; // e.g., 'persistence', 'accuracy', 'speed', 'mood'

  const LearningEvidence({
    required this.id,
    required this.userId,
    required this.generatedAt,
    required this.sequence,
    required this.description,
    required this.confidenceWeight,
    required this.category,
  });
}
