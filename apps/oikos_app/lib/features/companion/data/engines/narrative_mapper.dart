import '../../domain/entities/household_context.dart';
import '../../domain/entities/narrative_context.dart';

class NarrativeMapper {
  NarrativeContext map(HouseholdContext context) {
    List<String> sentences = [];

    // Foco no Legado (Identidade Permanente)
    if (context.coreLegacyValues.isNotEmpty) {
      sentences.add("Lembre-se: os valores fundamentais que atravessam a história desta família são: ${context.coreLegacyValues.join(' e ')}.");
    }

    // Foco nas Eras (Chapters)
    if (context.activeChapterTitle != null) {
      sentences.add("Atualmente, a história que ainda está sendo escrita chama-se: '${context.activeChapterTitle}'.");
    }
    
    if (context.closedChapters.isNotEmpty) {
      sentences.add("No passado, eles já viveram eras como: ${context.closedChapters.join(' e ')}.");
    }

    if (context.emotionalSignals.contains('Calma')) {
      sentences.add("Nos últimos dias, a atmosfera tem sido serena.");
    } else {
      sentences.add("A curiosidade tem ditado o ritmo das descobertas.");
    }

    if (context.treasuredMoments.isNotEmpty) {
      sentences.add("Um Tesouro inesquecível guardado recentemente foi ${context.treasuredMoments.first}.");
    }

    return NarrativeContext(narrativeSentences: sentences);
  }
}
