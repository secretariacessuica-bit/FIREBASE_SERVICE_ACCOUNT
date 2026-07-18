import '../entities/household_atmosphere.dart';

class MemoryInterpreter {
  Future<HouseholdAtmosphere> interpretRecentHistory() async {
    // Aqui nós leríamos o repositório de Habits e Stories.
    await Future.delayed(const Duration(milliseconds: 500));
    
    // Por enquanto, geramos uma atmosfera fixa ou baseada em sinais fixos
    return HouseholdAtmosphere(
      mood: LumoMood.sereno,
      confidence: 0.9,
      dominantSignals: ['Calma', 'Foco no aprendizado', 'Ausência de agitação'],
      generatedAt: DateTime.now(),
    );
  }
}
