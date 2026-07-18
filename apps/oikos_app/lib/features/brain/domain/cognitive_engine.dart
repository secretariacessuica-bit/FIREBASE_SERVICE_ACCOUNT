import 'entities/cognitive_profile.dart';
import 'entities/learning_evidence.dart';

/// Contract for the Horizon Cognitive Engine.
///
/// The engine is a **pure function** — stateless, side-effect-free, with no
/// dependency on any repository or infrastructure service.
///
/// Receiving [evaluatedAt] as a parameter (instead of calling [DateTime.now]
/// internally) keeps the function deterministic and fully testable.
///
/// ## Contract
/// - [newEvidences] MUST be ordered by `(generatedAt, sequence)` ascending.
///   Callers should enforce this at the repository level; the implementation
///   validates and re-sorts defensively in debug mode.
/// - Evidences whose cursor ≤ `currentProfile.lastProcessedEvidence` are
///   silently skipped (idempotent).
/// - The returned [CognitiveProfile] has the **same [revision]** as the input;
///   it is the repository's responsibility to increment it on a successful save.
abstract interface class CognitiveEngine {
  /// Applies [newEvidences] to [currentProfile] and returns an evolved copy.
  CognitiveProfile evolve({
    required CognitiveProfile currentProfile,
    required List<LearningEvidence> newEvidences,
    required DateTime evaluatedAt,
  });
}
