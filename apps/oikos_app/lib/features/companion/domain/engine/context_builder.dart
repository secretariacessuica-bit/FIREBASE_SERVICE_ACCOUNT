import '../entities/household_context.dart';
import '../entities/household_atmosphere.dart';

class ContextBuilder {
  Future<HouseholdContext> build(HouseholdAtmosphere atmosphere) async {
    return HouseholdContext(
      emotionalSignals: atmosphere.dominantSignals,
      treasuredMoments: ['Descoberta da leitura'],
      recurringHabits: ['Ler antes de dormir'],
      anniversaries: [],
      currentSeason: 'inverno',
      activeChapterTitle: 'A Fase dos Porquês',
      closedChapters: ['O Primeiro Lar', 'A Chegada da Maria'],
      coreLegacyValues: ['O Cuidado Constante', 'Curiosidade'],
    );
  }
}
