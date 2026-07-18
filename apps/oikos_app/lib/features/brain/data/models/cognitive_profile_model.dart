import 'dart:convert';
import 'package:hive/hive.dart';
import '../../domain/entities/cognitive_profile.dart';
import '../../domain/entities/cognitive_trait.dart';
import '../../domain/entities/evidence_cursor.dart';
import '../../domain/entities/trait_change.dart';

part 'cognitive_profile_model.g.dart';

/// Hive persistence model for [CognitiveProfile].
///
/// Serialises the domain entity to/from a flat Hive record.
/// Complex nested fields ([traits], [recentChanges]) are stored as JSON
/// strings to avoid deep adapter hierarchies — a pragmatic choice given that
/// Hive is used for local caching, not a relational database.
@HiveType(typeId: 90)
class CognitiveProfileModel extends HiveObject {
  @HiveField(0)
  final String userId;

  /// JSON-encoded `Map<String, Map<String, dynamic>>` representing [traits].
  @HiveField(1)
  final String traitsJson;

  /// JSON-encoded `List<Map<String, dynamic>>` representing [recentChanges].
  @HiveField(2)
  final String recentChangesJson;

  /// Cursor — stored as two fields for easy querying.
  @HiveField(3)
  final DateTime? cursorOccurredAt;

  @HiveField(4)
  final int? cursorSequence;

  @HiveField(5)
  final int engineVersion;

  @HiveField(6)
  final String status;

  @HiveField(7)
  final int revision;

  @HiveField(8)
  final DateTime lastUpdated;

  CognitiveProfileModel({
    required this.userId,
    required this.traitsJson,
    required this.recentChangesJson,
    this.cursorOccurredAt,
    this.cursorSequence,
    required this.engineVersion,
    required this.status,
    required this.revision,
    required this.lastUpdated,
  });

  // ── Serialisation ──────────────────────────────────────────────────────────

  factory CognitiveProfileModel.fromEntity(CognitiveProfile entity) {
    final traitsMap = entity.traits.map((key, trait) => MapEntry(key, {
          'value': trait.value,
          'confidenceScore': trait.confidenceScore,
          'stability': trait.stability,
          'evidenceCount': trait.evidenceCount,
          'lastUpdated': trait.lastUpdated.toIso8601String(),
        }));

    final changesList = entity.recentChanges
        .map((c) => {
              'traitKey': c.traitKey,
              'previousValue': c.previousValue,
              'currentValue': c.currentValue,
              'previousConfidence': c.previousConfidence,
              'currentConfidence': c.currentConfidence,
              'previousStability': c.previousStability,
              'currentStability': c.currentStability,
              'evidenceIds': c.evidenceIds,
              'changedAt': c.changedAt.toIso8601String(),
              'engineVersion': c.engineVersion,
            })
        .toList();

    return CognitiveProfileModel(
      userId: entity.userId,
      traitsJson: jsonEncode(traitsMap),
      recentChangesJson: jsonEncode(changesList),
      cursorOccurredAt: entity.lastProcessedEvidence?.occurredAt,
      cursorSequence: entity.lastProcessedEvidence?.sequence,
      engineVersion: entity.engineVersion,
      status: entity.status.name,
      revision: entity.revision,
      lastUpdated: entity.lastUpdated,
    );
  }

  CognitiveProfile toEntity() {
    final traitsRaw =
        (jsonDecode(traitsJson) as Map<String, dynamic>);
    final traits = traitsRaw.map((key, raw) {
      final m = raw as Map<String, dynamic>;
      return MapEntry(
        key,
        CognitiveTrait(
          key: key,
          value: (m['value'] as num).toDouble(),
          confidenceScore: (m['confidenceScore'] as num).toDouble(),
          stability: (m['stability'] as num).toDouble(),
          evidenceCount: m['evidenceCount'] as int,
          lastUpdated: DateTime.parse(m['lastUpdated'] as String),
        ),
      );
    });

    final changesRaw = jsonDecode(recentChangesJson) as List<dynamic>;
    final recentChanges = changesRaw.map((raw) {
      final m = raw as Map<String, dynamic>;
      return TraitChange(
        traitKey: m['traitKey'] as String,
        previousValue: (m['previousValue'] as num).toDouble(),
        currentValue: (m['currentValue'] as num).toDouble(),
        previousConfidence: (m['previousConfidence'] as num).toDouble(),
        currentConfidence: (m['currentConfidence'] as num).toDouble(),
        previousStability: (m['previousStability'] as num).toDouble(),
        currentStability: (m['currentStability'] as num).toDouble(),
        evidenceIds: List<String>.from(m['evidenceIds'] as List),
        changedAt: DateTime.parse(m['changedAt'] as String),
        engineVersion: m['engineVersion'] as int,
      );
    }).toList();

    final cursor = (cursorOccurredAt != null && cursorSequence != null)
        ? EvidenceCursor(
            occurredAt: cursorOccurredAt!,
            sequence: cursorSequence!,
          )
        : null;

    return CognitiveProfile(
      userId: userId,
      traits: traits,
      recentChanges: recentChanges,
      lastProcessedEvidence: cursor,
      engineVersion: engineVersion,
      status: CognitiveProfileStatus.values.firstWhere(
        (s) => s.name == status,
        orElse: () => CognitiveProfileStatus.stale,
      ),
      revision: revision,
      lastUpdated: lastUpdated,
    );
  }
}
