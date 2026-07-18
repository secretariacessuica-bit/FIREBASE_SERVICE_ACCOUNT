import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../../../features/presentation/providers/family_members_provider.dart';
import '../../../../features/domain/entities/age_experience_mode.dart';
import '../../domain/entities/cognitive_profile.dart';
import '../../domain/entities/learner_snapshot.dart';
import '../../domain/entities/learning_context.dart';
import '../../domain/entities/learning_decision.dart';
import '../../domain/entities/learning_event.dart';
import '../../domain/entities/learning_evidence.dart';
import '../../domain/entities/mood_hint.dart';
import '../../domain/adaptive_learning_engine.dart';
import '../../domain/horizon_cognitive_engine.dart';
import '../../domain/repositories/cognitive_profile_repository.dart';
import '../../domain/usecases/update_cognitive_profile.dart';
import 'trajectory_provider.dart';

// ── Repository stubs ──────────────────────────────────────────────────────────
// Temporary no-op implementations. Replace in app_module.dart once
// HiveCognitiveProfileRepository is registered with Hive.

class _NullCognitiveProfileRepository implements CognitiveProfileRepository {
  const _NullCognitiveProfileRepository();

  Future<CognitiveProfile?> getProfile(String userId) async => null;

  Future<CognitiveProfile> save(CognitiveProfile profile,
      {required int expectedRevision}) async =>
      profile;
}

class _NullEvidenceRepository implements LearningEvidenceRepository {
  const _NullEvidenceRepository();

  Future<List<LearningEvidence>> getEvidencesSince({
    required String userId,
    required DateTime sinceDate,
    required int sinceSequence,
  }) async =>
      [];

  Future<List<LearningEvidence>> getAllEvidences(String userId) async => [];
}

// ── Providers ─────────────────────────────────────────────────────────────────

final cognitiveProfileRepositoryProvider =
    Provider<CognitiveProfileRepository>(
        (_) => const _NullCognitiveProfileRepository());

final learningEvidenceRepositoryProvider =
    Provider<LearningEvidenceRepository>(
        (_) => const _NullEvidenceRepository());

final updateCognitiveProfileProvider = Provider<UpdateCognitiveProfile>((ref) {
  return UpdateCognitiveProfile(
    profileRepository: ref.watch(cognitiveProfileRepositoryProvider),
    evidenceRepository: ref.watch(learningEvidenceRepositoryProvider),
    engine: const HorizonCognitiveEngine(),
  );
});

final learningDecisionProvider =
    FutureProvider.family<LearningDecision, String>((ref, userId) async {
  final repository = ref.watch(trajectoryRepositoryProvider);
  final updateProfile = ref.watch(updateCognitiveProfileProvider);

  final members = await ref.watch(familyMembersProvider.future);
  final member =
      members.firstWhere((m) => m.id == userId, orElse: () => members.first);

  final events = await repository.getUserEvents(userId);

  // Infer ProfileTheme
  ProfileTheme inferredTheme = ProfileTheme.formal;
  if (member.experienceMode == AgeExperienceMode.earlyChildhood ||
      member.experienceMode == AgeExperienceMode.explorer) {
    inferredTheme = ProfileTheme.playful;
  } else if (member.experienceMode == AgeExperienceMode.teen) {
    inferredTheme = ProfileTheme.gamified;
  }

  final finishedSessions = events.whereType<SessionFinished>().toList();
  final abandonedSessions = events.whereType<SessionAbandoned>().toList();
  final answeredExercises = events.whereType<ExerciseAnswered>().toList();

  final totalSessions = finishedSessions.length + abandonedSessions.length;

  double avgResponseTime = 5.0;
  if (answeredExercises.isNotEmpty) {
    avgResponseTime = answeredExercises
            .map((e) => (e as ExerciseAnswered).timeTakenSeconds.toDouble())
            .reduce((a, b) => a + b) /
        answeredExercises.length;
  }

  double recentAccuracy = 0.5;
  if (finishedSessions.isNotEmpty) {
    final recentFinished = finishedSessions.reversed.take(5).toList();
    recentAccuracy = recentFinished
            .map((e) => (e as SessionFinished).accuracy)
            .reduce((a, b) => a + b) /
        recentFinished.length;
  }

  int recentErrors = 0;
  if (finishedSessions.isNotEmpty) {
    recentErrors = (finishedSessions.last as SessionFinished).errorCount;
  }

  final recentAbandons = abandonedSessions
      .where((e) => (e as SessionAbandoned)
          .timestamp
          .isAfter(DateTime.now().subtract(const Duration(days: 1))))
      .length;

  // ── Cognitive Engine integration ─────────────────────────────────────────
  // Delegate to the use-case. Failures are non-fatal: the Brain degrades
  // gracefully without a cognitive profile.
  CognitiveProfile? cognitiveProfile;
  try {
    cognitiveProfile = await updateProfile.execute(userId);
  } catch (_) {
    // Swallow silently — Brain works fine without a profile.
  }

  final snapshot = LearnerSnapshot(
    userId: member.id,
    mode: member.experienceMode,
    theme: inferredTheme,
    interests: ['music', 'travel'],
    recentAccuracy: recentAccuracy,
    totalSessionCount: totalSessions,
    averageResponseTime: avgResponseTime,
    recentErrors: recentErrors,
    recentAbandons: recentAbandons,
    isFirstSession: totalSessions == 0,
    cognitiveProfile: cognitiveProfile,
  );

  final context = LearningContext(
    today: DateTime.now(),
    currentTime: TimeOfDay.now(),
    locale: 'pt_BR',
    minutesAvailable: 15,
    moodHint: _inferMoodHint(snapshot),
    sessionNumber: 1,
    isWeekend: DateTime.now().weekday >= 6,
    familyContext:
        const FamilyContext(otherMembersActive: true, familyStreakDays: 5),
  );

  return AdaptiveLearningEngine.decide(snapshot, context);
});

MoodHint _inferMoodHint(LearnerSnapshot snapshot) {
  if (snapshot.recentAbandons > 0) return MoodHint.tired;
  if (snapshot.averageResponseTime < 3.0) return MoodHint.energetic;
  if (snapshot.recentErrors > 5) return MoodHint.focused;
  return MoodHint.calm;
}
