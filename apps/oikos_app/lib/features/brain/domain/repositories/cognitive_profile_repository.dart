import '../entities/cognitive_profile.dart';

/// Persistence contract for [CognitiveProfile].
///
/// The repository is the **sole owner** of the [CognitiveProfile.revision]
/// counter. It must:
/// 1. Increment revision on every successful save.
/// 2. Reject saves where the provided [expectedRevision] no longer matches
///    the stored revision (optimistic locking).
/// 3. Return the persisted profile (with the incremented revision) so the
///    caller's in-memory state stays consistent.
abstract interface class CognitiveProfileRepository {
  /// Retrieves the current profile for [userId], or null if none exists yet.
  Future<CognitiveProfile?> getProfile(String userId);

  /// Persists [profile] using optimistic locking.
  ///
  /// - [expectedRevision] must match the currently stored revision.
  /// - On success, the repository increments the revision and returns the
  ///   persisted profile with `revision = expectedRevision + 1`.
  /// - On conflict (revision mismatch), throws [CognitiveProfileConflictException].
  Future<CognitiveProfile> save(
    CognitiveProfile profile, {
    required int expectedRevision,
  });
}

/// Thrown when [CognitiveProfileRepository.save] detects a revision mismatch,
/// meaning another session already wrote a newer profile while we were computing.
class CognitiveProfileConflictException implements Exception {
  const CognitiveProfileConflictException({
    required this.userId,
    required this.expectedRevision,
  });

  final String userId;
  final int expectedRevision;

  @override
  String toString() =>
      'CognitiveProfileConflictException: revision conflict for user "$userId" '
      '(expected $expectedRevision).';
}
