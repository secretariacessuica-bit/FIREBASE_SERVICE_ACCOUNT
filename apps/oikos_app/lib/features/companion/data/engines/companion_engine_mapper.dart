import '../../domain/entities/household_context.dart';

class CompanionEngineMapper {
  /// Reduz o contexto para proteger a privacidade e evitar tokens desnecessários.
  /// Implementa o princípio "Negado por Padrão" exigido na Sprint 013.
  Map<String, dynamic> toMinimalPayload(HouseholdContext context) {
    return {
      // NÃO ENVIAMOS IDs ou Meta-dados de Sincronização.
      // Apenas os sinais emocionais e um número limitado de tesouros
      'emotional_signals': context.emotionalSignals.take(3).toList(),
      'treasured_moments': context.treasuredMoments.take(2).toList(),
      'recurring_habits': context.recurringHabits.take(3).toList(),
    };
  }
}
