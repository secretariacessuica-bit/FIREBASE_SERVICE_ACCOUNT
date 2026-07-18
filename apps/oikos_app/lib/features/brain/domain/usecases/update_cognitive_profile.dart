import '../cognitive_engine.dart';
import '../entities/cognitive_profile.dart';
import '../entities/learning_evidence.dart';
import '../horizon_cognitive_engine.dart';
import '../repositories/cognitive_profile_repository.dart';
import '../repositories/trajectory_repository.dart';

/// Exception thrown when the optimistic-lock retry limit is exhausted.
class CognitiveProfileConcurrencyException implements Exception {
  const CognitiveProfileConcurrencyException(this.userId);
  final String userId;

  @override
  String toString() =>
      'CognitiveProfileConcurrencyException: could not update profile for '
      '"$userId" after max retries.';
}

/// Fetches new evidences since the profile's cursor.
///
/// Defined as an abstract contract so the use-case does not depend on a
/// concrete EvidenceRepository (which may not exist yet).
abstract interface class LearningEvidenceRepository {
  /// Returns evidences for [userId] generated *after* [sinceSequence] at
  /// [sinceDate], ordered by (generatedAt, sequence) ascending.
  Future<List<LearningEvidence>> getEvidencesSince({
    required String userId,
    required DateTime sinceDate,
    required int sinceSequence,
  });

  /// Returns all evidences for [userId] ordered by (generatedAt, sequence).
  Future<List<LearningEvidence>> getAllEvidences(String userId);
}

/// Coordinates the incremental update of a [CognitiveProfile].
///
/// ## Algorithm
/// 1. Load the current profile (or create an empty one).
/// 2. If [profile.engineVersion] differs from [HorizonCognitiveEngine.version],
///    mark the profile as [CognitiveProfileStatus.stale] and schedule a full
///    rebuild via [CognitiveProfileRebuilder].  Use the stale profile
///    temporarily (strategy: use-previous-while-rebuilding).
/// 3. Fetch only new evidences (since the cursor).
/// 4. Call [CognitiveEngine.evolve] (pure function — no side effects).
/// 5. Persist with optimistic locking, retrying up to [_maxRetries] times.
/// 6. Return the updated profile.
class UpdateCognitiveProfile {
  UpdateCognitiveProfile({
    required this.profileRepository,
    required this.evidenceRepository,
    required this.engine,
    this.rebuilder,
  });

  final CognitiveProfileRepository profileRepository;
  final LearningEvidenceRepository evidenceRepository;
  final CognitiveEngine engine;

  /// Optional. If provided, called whenever a rebuild is needed due to an
  /// engine version mismatch.
  final CognitiveProfileRebuilder? rebuilder;

  static const int _maxRetries = 3;

  Future<CognitiveProfile> execute(String userId) async {
    // 1. Load or initialise the profile.
    var profile = await profileRepository.getProfile(userId) ??
        CognitiveProfile.empty(
          userId: userId,
          currentEngineVersion: HorizonCognitiveEngine.version,
          createdAt: DateTime.now(),
        );

    // 2. Engine version check.
    if (profile.engineVersion != HorizonCognitiveEngine.version) {
      // Mark as stale and schedule a full async rebuild.
      profile = profile.copyWith(status: CognitiveProfileStatus.stale);
      rebuilder?.scheduleRebuild(userId);
      // Continue with the stale profile for this session.
    }

    // 3. Fetch new evidences since the cursor.
    final List<LearningEvidence> newEvidences;
    final cursor = profile.lastProcessedEvidence;

    if (cursor == null) {
      newEvidences = await evidenceRepository.getAllEvidences(userId);
    } else {
      newEvidences = await evidenceRepository.getEvidencesSince(
        userId: userId,
        sinceDate: cursor.occurredAt,
        sinceSequence: cursor.sequence,
      );
    }

    if (newEvidences.isEmpty) return profile;

    // 4. Evolve (pure, deterministic).
    final evaluatedAt = DateTime.now();
    var evolved = engine.evolve(
      currentProfile: profile,
      newEvidences: newEvidences,
      evaluatedAt: evaluatedAt,
    );

    // 5. Persist with optimistic locking and retry.
    int attempt = 0;
    while (attempt < _maxRetries) {
      try {
        final saved = await profileRepository.save(
          evolved,
          expectedRevision: profile.revision,
        );
        return saved;
      } on CognitiveProfileConflictException {
        attempt++;
        if (attempt >= _maxRetries) {
          throw CognitiveProfileConcurrencyException(userId);
        }

        // Reload the newer profile and re-fetch only still-pending evidences.
        profile = (await profileRepository.getProfile(userId))!;
        evolved = engine.evolve(
          currentProfile: profile,
          newEvidences: newEvidences, // engine will skip already-processed ones
          evaluatedAt: DateTime.now(),
        );
      }
    }

    // Should be unreachable.
    throw CognitiveProfileConcurrencyException(userId);
  }
}

/// Contract for scheduling a full profile rebuild in the background.
///
/// Implementations should run the rebuild asynchronously without blocking
/// the current session.
abstract interface class CognitiveProfileRebuilder {
  void scheduleRebuild(String userId);
}
