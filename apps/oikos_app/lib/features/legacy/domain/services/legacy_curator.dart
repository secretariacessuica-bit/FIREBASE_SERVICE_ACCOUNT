import 'enduring_value.dart';
import 'legacy_value_objects.dart';
import '../../chapters/domain/entities/household_chapter.dart';
import '../../chapters/domain/entities/chapter_value_objects.dart';

class LegacyCurator {
  /// O LegacyCurator procura ecos de permanência através de diferentes fases da vida.
  EnduringValue? suggestLegacyValue({
    required List<HouseholdChapter> closedChapters,
    required List<EnduringValue> existingValues,
  }) {
    // Regra oficial: O valor precisa aparecer em pelo menos DOIS Chapters fechados distintos.
    if (closedChapters.length < 2) return null;

    // Exemplo heurístico para Prova de Conceito:
    // Se temos dois capítulos fechados recentes, vamos simular que o Cuidado (Care) esteve presente
    // em ambos (nascimento e mudança).

    final hasCareAlready = existingValues.any((v) => v.archetype == LegacyArchetype.care);

    if (!hasCareAlready) {
      final evidence1 = LegacyEvidence(
        sourceType: 'chapter',
        sourceId: closedChapters[0].chapterId,
        contribution: "A família demonstrou extremo zelo durante a adaptação descrita em '${closedChapters[0].chapterTitle}'",
        narrative: "Foi a primeira vez que o Cuidado se revelou mais forte que a mudança.",
        observedAt: DateTime.now(),
      );

      final evidence2 = LegacyEvidence(
        sourceType: 'chapter',
        sourceId: closedChapters[1].chapterId,
        contribution: "O mesmo zelo se repetiu no capítulo '${closedChapters[1].chapterTitle}'",
        narrative: "Aqui o Cuidado deixou de ser uma reação e virou uma tradição da casa.",
        observedAt: DateTime.now(),
      );

      return EnduringValue(
        valueId: 'legacy_care_${DateTime.now().millisecondsSinceEpoch}',
        archetype: LegacyArchetype.care,
        familyName: 'O Cuidado Constante',
        discoveryDate: DateTime.now(),
        reflection: 'Mesmo com as fases mudando, o cuidado mútuo encontrou espaço entre vocês.',
        evidences: [evidence1, evidence2],
        confidence: 0.8,
        status: LegacyStatus.suggested,
      );
    }
    
    return null;
  }
}
