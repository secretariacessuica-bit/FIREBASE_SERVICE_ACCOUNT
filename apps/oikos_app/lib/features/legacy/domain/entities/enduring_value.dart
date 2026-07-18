import 'legacy_value_objects.dart';

class EnduringValue {
  final String valueId;
  final LegacyArchetype archetype;
  final String familyName; // Nome afetivo dado pela família (ex: "Nossa Fase dos Porquês")
  final DateTime discoveryDate;
  final String reflection; // Texto que explica o valor
  final List<LegacyEvidence> evidences;
  final double confidence; // Nível de confiança do Curator (0.0 a 1.0)
  final LegacyStatus status;

  EnduringValue({
    required this.valueId,
    required this.archetype,
    required this.familyName,
    required this.discoveryDate,
    required this.reflection,
    required this.evidences,
    required this.confidence,
    required this.status,
  });

  EnduringValue addEvidence(LegacyEvidence newEvidence) {
    return _copyWith(
      evidences: [...evidences, newEvidence],
      confidence: (confidence + 0.1).clamp(0.0, 1.0),
    );
  }

  EnduringValue rename(String newFamilyName) {
    return _copyWith(familyName: newFamilyName);
  }

  EnduringValue accept() {
    return _copyWith(status: LegacyStatus.established);
  }

  EnduringValue _copyWith({
    String? familyName,
    String? reflection,
    List<LegacyEvidence>? evidences,
    double? confidence,
    LegacyStatus? status,
  }) {
    return EnduringValue(
      valueId: valueId,
      archetype: archetype,
      familyName: familyName ?? this.familyName,
      discoveryDate: discoveryDate,
      reflection: reflection ?? this.reflection,
      evidences: evidences ?? this.evidences,
      confidence: confidence ?? this.confidence,
      status: status ?? this.status,
    );
  }
}
