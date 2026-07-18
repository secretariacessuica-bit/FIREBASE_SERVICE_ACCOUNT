enum LegacyArchetype {
  courage,
  care,
  curiosity,
  generosity,
  hope,
  tradition,
  belonging,
  resilience
}

enum LegacyStatus {
  suggested,
  established
}

class LegacyEvidence {
  final String sourceType; // ex: 'chapter', 'treasure'
  final String sourceId;
  final String contribution; // Por que isso é uma evidência? (ex: "A família se manteve unida durante a mudança de cidade")
  final String narrative; // Pequeno resumo narrativo permanente (ex: "Foi aqui que o cuidado deixou de ser um gesto isolado.")
  final DateTime observedAt;

  const LegacyEvidence({
    required this.sourceType,
    required this.sourceId,
    required this.contribution,
    required this.narrative,
    required this.observedAt,
  });
}
