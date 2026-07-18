import 'household_chapter.dart';
import 'chapter_value_objects.dart';
import '../../companion/domain/entities/household_atmosphere.dart';

class ChapterCurator {
  /// O ChapterCurator observa padrões que ocorrem ao longo de meses.
  /// Quando ele percebe que uma "Era" madurou, ele sugere à família.
  HouseholdChapter? suggestChapterTransition({
    required HouseholdChapter currentOpenChapter,
    required List<HouseholdAtmosphere> historicalAtmospheres,
    required List<String> recentTreasures,
    required List<String> recentStories,
  }) {
    // Regra heurística simples para a Prova de Conceito:
    // Se o capítulo atual tem mais de 20 histórias ou tesouros significativos
    // e o tema da atmosfera predominante está mudando, o Curador sugere o fim dessa era.
    
    final totalMemories = currentOpenChapter.chapterStories.length + currentOpenChapter.chapterTreasures.length;
    final timePassed = DateTime.now().difference(currentOpenChapter.chapterPeriod.startedAt).inDays;

    if (totalMemories >= 10 && timePassed >= 30) {
      // Cria a sugestão do capítulo de fechamento (Draft/Suggested)
      return HouseholdChapter(
        chapterId: 'suggestion_${DateTime.now().millisecondsSinceEpoch}',
        householdId: currentOpenChapter.householdId,
        chapterTitle: 'Uma Época de Descobertas',
        chapterReflection: 'A curiosidade nos levou a lugares novos. Essa fase nos ensinou a olhar com mais cuidado.',
        chapterPeriod: currentOpenChapter.chapterPeriod,
        chapterTheme: ChapterTheme.discovery,
        chapterStatus: ChapterStatus.suggested,
        chapterStories: currentOpenChapter.chapterStories,
        chapterTreasures: currentOpenChapter.chapterTreasures,
      );
    }
    
    return null; // A Era atual continua.
  }
}
