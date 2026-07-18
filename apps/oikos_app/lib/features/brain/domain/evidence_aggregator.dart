import 'entities/learning_event.dart';
import 'entities/learning_evidence.dart';

class EvidenceAggregator {
  /// Converte uma lista de eventos brutos em evidências consolidadas
  List<LearningEvidence> aggregate(List<LearningEvent> events, String userId) {
    if (events.isEmpty) return [];

    final evidences = <LearningEvidence>[];
    
    // Agregação 1: Persistência (abandonos vs conclusões)
    final abandoned = events.whereType<SessionAbandoned>().length;
    final finished = events.whereType<SessionFinished>().length;
    
    if (finished > 0 || abandoned > 0) {
      final completionRate = finished / (finished + abandoned);
      if (completionRate > 0.8) {
        evidences.add(LearningEvidence(
          id: 'ev_${DateTime.now().millisecondsSinceEpoch}_persistence',
          userId: userId,
          generatedAt: DateTime.now(),
          sequence: 0,
          description: 'Alta taxa de conclusão de sessões. O usuário demonstra persistência.',
          confidenceWeight: 0.8,
          category: 'persistence',
        ));
      } else if (completionRate < 0.3 && abandoned > 2) {
        evidences.add(LearningEvidence(
          id: 'ev_${DateTime.now().millisecondsSinceEpoch}_persistence',
          userId: userId,
          generatedAt: DateTime.now(),
          sequence: 0,
          description: 'Múltiplos abandonos recentes. O usuário pode estar frustrado ou sem tempo.',
          confidenceWeight: 0.7,
          category: 'persistence',
        ));
      }
    }

    // Agregação 2: Precisão (accuracy médio das sessões finalizadas)
    final finishedEvents = events.whereType<SessionFinished>().toList();
    if (finishedEvents.isNotEmpty) {
      final avgAccuracy = finishedEvents.map((e) => e.accuracy).reduce((a, b) => a + b) / finishedEvents.length;
      if (avgAccuracy > 0.85) {
        evidences.add(LearningEvidence(
          id: 'ev_${DateTime.now().millisecondsSinceEpoch}_accuracy',
          userId: userId,
          generatedAt: DateTime.now(),
          sequence: 0,
          description: 'Alta precisão nas atividades recentes. O nível pode estar muito fácil.',
          confidenceWeight: 0.9,
          category: 'accuracy',
        ));
      } else if (avgAccuracy < 0.5) {
        evidences.add(LearningEvidence(
          id: 'ev_${DateTime.now().millisecondsSinceEpoch}_accuracy',
          userId: userId,
          generatedAt: DateTime.now(),
          sequence: 0,
          description: 'Baixa precisão nas atividades recentes. O usuário está com dificuldades.',
          confidenceWeight: 0.85,
          category: 'accuracy',
        ));
      }
    }

    // Agregação 3: Dicas (HintRequested)
    final hints = events.whereType<HintRequested>().length;
    if (hints > 5) {
      evidences.add(LearningEvidence(
        id: 'ev_${DateTime.now().millisecondsSinceEpoch}_hints',
        userId: userId,
        generatedAt: DateTime.now(),
        sequence: 0,
        description: 'Usuário pede muitas dicas antes de responder.',
        confidenceWeight: 0.75,
        category: 'behavior',
      ));
    }

    return evidences;
  }
}
