import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/progress_provider.dart';

final lumoLearningReactionsProvider = Provider<LumoLearningReactions>((ref) {
  return LumoLearningReactions(ref);
});

class LumoLearningReactions {
  final Ref ref;

  LumoLearningReactions(this.ref);

  void onLessonCompleted({
    required int earnedXp,
    required int correctAnswers,
    required int totalQuestions,
  }) {
    // In a real app, this would call LumoService to queue a message.
    // For now, we simulate Lumo's reaction.
    final percentage = correctAnswers / totalQuestions;
    String message = 'Excelente trabalho! Você concluiu mais uma lição.';
    
    if (percentage == 1.0) {
      message = 'Perfeito! Você acertou todas as questões. Continue brilhando!';
    } else if (percentage >= 0.5) {
      message = 'Muito bem! Aprender todos os dias faz você crescer.';
    } else {
      message = 'A prática leva à perfeição. Continue tentando, estou com você!';
    }

    // Since we don't have LumoService fully available in this context without breaking other code,
    // we just print or dispatch to a global notification provider.
    print('Lumo says: $message');
  }
}
