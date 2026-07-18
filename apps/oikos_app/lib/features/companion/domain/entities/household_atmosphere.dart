enum LumoMood {
  sereno,
  contemplativo,
  celebrando,
  acolhedor,
  silencioso
}

class HouseholdAtmosphere {
  final LumoMood mood;
  final double confidence; // Qual o nível de certeza do Lumo sobre o clima da casa
  final List<String> dominantSignals;
  final DateTime generatedAt;

  const HouseholdAtmosphere({
    required this.mood,
    required this.confidence,
    required this.dominantSignals,
    required this.generatedAt,
  });
}
