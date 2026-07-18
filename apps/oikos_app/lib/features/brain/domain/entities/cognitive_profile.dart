import 'cognitive_trait.dart';
import 'evidence_cursor.dart';
import 'trait_change.dart';

/// Status of a [CognitiveProfile] relative to the current engine version.
enum CognitiveProfileStatus {
  /// Profile was produced by the current engine version. Ready to use.
  active,

  /// Profile was produced by an older engine version. A background rebuild
  /// has been scheduled. The Brain may still use this profile temporarily.
  stale,

  /// A full rebuild is currently running in the background.
  rebuilding,

  /// The last rebuild attempt failed. Retry should be scheduled.
  failed,
}

/// The materialised, persisted representation of what the Horizon system has
/// learned about *how* a specific learner processes information.
///
/// This is NOT the source of truth. It is a **projection** (read model) built
/// incrementally from [LearningEvidence] records by the [HorizonCognitiveEngine].
///
/// ## Incremental Update Contract
/// 1. The repository loads the current [CognitiveProfile].
/// 2. The evidence repository fetches only evidences *after* [lastProcessedEvidence].
/// 3. The engine calls `evolve(profile, newEvidences, evaluatedAt)`.
/// 4. The repository saves the new profile using optimistic locking on [revision].
///
/// ## Invariants
/// - [traits] and [recentChanges] are **unmodifiable** collections.
/// - [revision] is owned and incremented exclusively by the repository.
/// - [engineVersion] is set by the engine and checked by the orchestrator
///   to detect stale profiles.
class CognitiveProfile {
  final String userId;

  /// Map of trait key → [CognitiveTrait]. Unmodifiable.
  final Map<String, CognitiveTrait> traits;

  /// Short-term audit log. Capped at [maxRecentChanges]. Unmodifiable.
  final List<TraitChange> recentChanges;

  /// Maximum number of entries kept in [recentChanges].
  static const int maxRecentChanges = 50;

  /// Cursor pointing to the last evidence processed into this profile.
  /// Null if no evidences have been processed yet.
  final EvidenceCursor? lastProcessedEvidence;

  /// The version of [HorizonCognitiveEngine] that produced this profile.
  /// If it differs from `HorizonCognitiveEngine.version`, the orchestrator
  /// must schedule an async rebuild.
  final int engineVersion;

  /// Current lifecycle state of this profile.
  final CognitiveProfileStatus status;

  /// Used for optimistic locking. Owned and incremented by the repository.
  /// The engine must return a new profile with the **same** revision it
  /// received; the repository increments it on a successful save.
  final int revision;

  final DateTime lastUpdated;

  CognitiveProfile({
    required this.userId,
    required Map<String, CognitiveTrait> traits,
    required List<TraitChange> recentChanges,
    required this.lastProcessedEvidence,
    required this.engineVersion,
    required this.status,
    required this.revision,
    required this.lastUpdated,
  })  : traits = Map.unmodifiable(traits),
        recentChanges = List.unmodifiable(recentChanges);

  /// Creates a blank profile for a first-time user.
  factory CognitiveProfile.empty({
    required String userId,
    required int currentEngineVersion,
    required DateTime createdAt,
  }) {
    return CognitiveProfile(
      userId: userId,
      traits: const {},
      recentChanges: const [],
      lastProcessedEvidence: null,
      engineVersion: currentEngineVersion,
      status: CognitiveProfileStatus.active,
      revision: 0,
      lastUpdated: createdAt,
    );
  }

  CognitiveProfile copyWith({
    Map<String, CognitiveTrait>? traits,
    List<TraitChange>? recentChanges,
    EvidenceCursor? lastProcessedEvidence,
    int? engineVersion,
    CognitiveProfileStatus? status,
    int? revision,
    DateTime? lastUpdated,
  }) {
    return CognitiveProfile(
      userId: userId,
      traits: traits ?? this.traits,
      recentChanges: recentChanges ?? this.recentChanges,
      lastProcessedEvidence:
          lastProcessedEvidence ?? this.lastProcessedEvidence,
      engineVersion: engineVersion ?? this.engineVersion,
      status: status ?? this.status,
      revision: revision ?? this.revision,
      lastUpdated: lastUpdated ?? this.lastUpdated,
    );
  }
}
