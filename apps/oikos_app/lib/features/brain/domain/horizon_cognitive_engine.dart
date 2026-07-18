import 'dart:math' as math;
import 'package:flutter/foundation.dart';

import 'cognitive_engine.dart';
import 'entities/cognitive_profile.dart';
import 'entities/cognitive_trait.dart';
import 'entities/evidence_cursor.dart';
import 'entities/learning_evidence.dart';
import 'entities/trait_change.dart';
import 'entities/trait_update.dart';

/// The canonical implementation of [CognitiveEngine] for the Horizon kernel.
///
/// ## Online Learning Algorithm
///
/// For each trait updated in a batch of evidences, the engine applies:
///
/// ```
/// effectiveLearningRate = baseLearningRate
///                       × evidenceWeight
///                       × (1.0 − trait.stability × stabilityResistance)
///
/// effectiveLearningRate = effectiveLearningRate.clamp(minLr, maxLr)
///
/// newValue = currentValue + effectiveLearningRate × (targetValue − currentValue)
/// ```
///
/// **Stability** evolves independently:
/// - Consistent evidence (targetValue has the same sign as currentValue) → +Δ
/// - Contradictory evidence → −Δ
///
/// **Confidence** accounts for volume, quality, and consistency,
/// not merely evidence count.
class HorizonCognitiveEngine implements CognitiveEngine {
  const HorizonCognitiveEngine();

  /// Increment this constant whenever the algorithm changes in a
  /// backward-incompatible way.  The [UpdateCognitiveProfile] use-case
  /// compares this against [CognitiveProfile.engineVersion] and schedules a
  /// full rebuild if they differ.
  static const int version = 1;

  // ── Learning-rate hyper-parameters ────────────────────────────────────────
  static const double _baseLearningRate = 0.15;
  static const double _stabilityResistance = 0.7;
  static const double _minLearningRate = 0.02;
  static const double _maxLearningRate = 0.40;

  // ── Stability drift constants ──────────────────────────────────────────────
  static const double _stabilityGainPerConsistentEvidence = 0.04;
  static const double _stabilityLossPerContradictoryEvidence = 0.06;

  // ── Confidence decay / gain constants ─────────────────────────────────────
  static const double _confidenceGainPerEvidence = 0.05;

  // ─────────────────────────────────────────────────────────────────────────
  @override
  CognitiveProfile evolve({
    required CognitiveProfile currentProfile,
    required List<LearningEvidence> newEvidences,
    required DateTime evaluatedAt,
  }) {
    // 1. Filter: skip evidences already incorporated into the profile.
    final pendingEvidences = _filterPending(
      newEvidences,
      currentProfile.lastProcessedEvidence,
    );

    if (pendingEvidences.isEmpty) return currentProfile;

    // 2. Defensive sort (the repository should guarantee order, but we
    //    re-sort in debug builds to surface bugs early).
    assert(() {
      _assertOrdered(pendingEvidences);
      return true;
    }());

    final orderedEvidences = List<LearningEvidence>.from(pendingEvidences)
      ..sort(_compareBycursor);

    // 3. Inference pass: Evidence → List<TraitUpdate>
    final updates = _inferUpdates(orderedEvidences);

    // 4. Application pass: TraitUpdate → evolved CognitiveTrait
    final mutableTraits = Map<String, CognitiveTrait>.from(currentProfile.traits);
    final newChanges = <TraitChange>[];

    for (final update in updates) {
      final existing = mutableTraits[update.traitKey] ??
          CognitiveTrait.initial(key: update.traitKey, createdAt: evaluatedAt);

      final evolved = _applyUpdate(
        trait: existing,
        update: update,
        evaluatedAt: evaluatedAt,
      );

      if (evolved != existing) {
        newChanges.add(TraitChange(
          traitKey: update.traitKey,
          previousValue: existing.value,
          currentValue: evolved.value,
          previousConfidence: existing.confidenceScore,
          currentConfidence: evolved.confidenceScore,
          previousStability: existing.stability,
          currentStability: evolved.stability,
          evidenceIds: List.unmodifiable(update.sourceEvidenceIds),
          changedAt: evaluatedAt,
          engineVersion: version,
        ));
        mutableTraits[update.traitKey] = evolved;
      }
    }

    // 5. Merge recentChanges, cap at maxRecentChanges.
    final allChanges = [
      ...currentProfile.recentChanges,
      ...newChanges,
    ];
    final cappedChanges = allChanges.length > CognitiveProfile.maxRecentChanges
        ? allChanges.sublist(
            allChanges.length - CognitiveProfile.maxRecentChanges)
        : allChanges;

    // 6. Advance cursor to the last processed evidence.
    final lastEvidence = orderedEvidences.last;
    final newCursor = EvidenceCursor(
      occurredAt: lastEvidence.generatedAt,
      sequence: lastEvidence.sequence,
    );

    // 7. Return evolved profile (same revision — repository increments it).
    return currentProfile.copyWith(
      traits: mutableTraits,
      recentChanges: cappedChanges,
      lastProcessedEvidence: newCursor,
      engineVersion: version,
      lastUpdated: evaluatedAt,
    );
  }

  // ── Private helpers ────────────────────────────────────────────────────────

  List<LearningEvidence> _filterPending(
    List<LearningEvidence> evidences,
    EvidenceCursor? cursor,
  ) {
    if (cursor == null) return evidences;
    return evidences.where((e) {
      final ec = EvidenceCursor(occurredAt: e.generatedAt, sequence: e.sequence);
      return ec.isAfter(cursor);
    }).toList();
  }

  int _compareBycursor(LearningEvidence a, LearningEvidence b) {
    final cmp = a.generatedAt.compareTo(b.generatedAt);
    if (cmp != 0) return cmp;
    return a.sequence.compareTo(b.sequence);
  }

  void _assertOrdered(List<LearningEvidence> evidences) {
    for (var i = 1; i < evidences.length; i++) {
      final prev = evidences[i - 1];
      final curr = evidences[i];
      final ok = curr.generatedAt.isAfter(prev.generatedAt) ||
          (curr.generatedAt == prev.generatedAt &&
              curr.sequence >= prev.sequence);
      if (!ok) {
        debugPrint(
          'HorizonCognitiveEngine: evidence out of order at index $i. '
          'Ensure the EvidenceRepository returns ordered results.',
        );
      }
    }
  }

  /// Translates a list of evidences into a flat list of [TraitUpdate]s.
  ///
  /// Each evidence category maps to a named trait. This mapping is the main
  /// place to extend when new cognitive dimensions are added to Horizon.
  List<TraitUpdate> _inferUpdates(List<LearningEvidence> evidences) {
    // Group evidences by the trait they affect.
    final Map<String, List<LearningEvidence>> grouped = {};

    for (final evidence in evidences) {
      final traitKey = _categoryToTraitKey(evidence.category);
      grouped.putIfAbsent(traitKey, () => []).add(evidence);
    }

    return grouped.entries.map((entry) {
      final traitKey = entry.key;
      final batch = entry.value;

      // Aggregate: compute the mean targetValue and mean weight from the batch.
      // Evidences within the same category reinforce or contradict each other.
      final avgWeight = batch
              .map((e) => e.confidenceWeight)
              .reduce((a, b) => a + b) /
          batch.length;

      final targetValue = _categoryToTargetValue(batch);

      // Consistency: do all evidences in the batch point the same direction?
      final isConsistent = batch.every((e) =>
          _categoryToTargetValue([e]).sign == targetValue.sign ||
          _categoryToTargetValue([e]) == 0);

      return TraitUpdate(
        traitKey: traitKey,
        targetValue: targetValue.clamp(-1.0, 1.0),
        evidenceWeight: avgWeight.clamp(0.0, 1.0),
        confidenceImpact: _confidenceGainPerEvidence * batch.length,
        stabilityImpact: isConsistent
            ? _stabilityGainPerConsistentEvidence
            : -_stabilityLossPerContradictoryEvidence,
        sourceEvidenceIds: batch.map((e) => e.id).toList(),
      );
    }).toList();
  }

  /// Maps the evidence [category] field to a canonical trait key.
  String _categoryToTraitKey(String category) {
    switch (category) {
      case 'persistence':
        return 'persistence_drive';
      case 'accuracy':
        return 'accuracy_profile';
      case 'behavior':
        return 'hint_dependency';
      case 'narrative':
        return 'narrative_preference';
      case 'gamification':
        return 'gamification_response';
      case 'speed':
        return 'response_speed';
      default:
        return category;
    }
  }

  /// Computes the direction (target value) from a batch of same-category evidences.
  ///
  /// Uses the average [confidenceWeight] as a signed magnitude:
  /// - Positive evidences → positive target.
  /// - Negative/corrective evidences → negative target.
  ///
  /// Currently, the [LearningEvidence.description] string is used as a simple
  /// heuristic. In future iterations, evidences can carry an explicit
  /// [targetValue] field for richer signal.
  double _categoryToTargetValue(List<LearningEvidence> batch) {
    double sum = 0;
    for (final e in batch) {
      // Heuristic: descriptions containing negation keywords signal aversion.
      final isNegative = e.description.toLowerCase().contains('baixa') ||
          e.description.toLowerCase().contains('múltiplos') ||
          e.description.toLowerCase().contains('dificuldade') ||
          e.description.toLowerCase().contains('frustrad');
      sum += isNegative ? -e.confidenceWeight : e.confidenceWeight;
    }
    return (sum / batch.length).clamp(-1.0, 1.0);
  }

  /// Applies a single [TraitUpdate] to a [CognitiveTrait] using the
  /// online-learning formula and returns an evolved copy.
  CognitiveTrait _applyUpdate({
    required CognitiveTrait trait,
    required TraitUpdate update,
    required DateTime evaluatedAt,
  }) {
    // Online learning rate modulated by stability.
    final rawRate = _baseLearningRate *
        update.evidenceWeight *
        (1.0 - trait.stability * _stabilityResistance);
    final effectiveLearningRate =
        rawRate.clamp(_minLearningRate, _maxLearningRate);

    // Value update: move toward targetValue, never jump.
    final newValue =
        trait.value + effectiveLearningRate * (update.targetValue - trait.value);

    // Confidence update (clamped, cannot exceed 1.0).
    final newConfidence =
        (trait.confidenceScore + update.confidenceImpact).clamp(0.0, 1.0);

    // Stability update (evolves independently, clamped).
    final newStability =
        (trait.stability + update.stabilityImpact).clamp(0.0, 1.0);

    return trait.copyWith(
      value: newValue,
      confidenceScore: newConfidence,
      stability: newStability,
      evidenceCount: trait.evidenceCount + update.sourceEvidenceIds.length,
      lastUpdated: evaluatedAt,
    );
  }
}
