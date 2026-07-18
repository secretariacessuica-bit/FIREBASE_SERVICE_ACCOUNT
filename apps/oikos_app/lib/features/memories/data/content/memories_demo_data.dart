import '../../domain/entities/memory_theme.dart';
import '../../domain/entities/memory_treasure.dart';
import '../../domain/entities/reflection.dart';

class MemoriesDemoData {
  static List<MemoryTreasure> getTreasures() {
    final now = DateTime.now();
    return [
      MemoryTreasure(
        id: 't1',
        householdId: 'h1',
        storyId: 's2',
        title: 'Nosso Primeiro Livro',
        narrative: 'O dia em que Pedro ficou encantado com as aventuras dos dinossauros e pediu para ler novamente.',
        coverEmoji: '📖',
        theme: MemoryTheme.learning,
        reflection: const Reflection('Algumas descobertas ficam conosco para sempre.'),
        date: now.subtract(const Duration(days: 5)),
      ),
      MemoryTreasure(
        id: 't2',
        householdId: 'h1',
        storyId: 's3',
        title: 'A Primeira Gentileza',
        narrative: 'O momento especial em que Maria ajudou João espontaneamente, mostrando que empatia nasce em casa.',
        coverEmoji: '❤️',
        theme: MemoryTheme.kindness,
        reflection: const Reflection('Os melhores momentos costumam nascer das pequenas atitudes.'),
        date: now.subtract(const Duration(days: 2)),
      ),
      MemoryTreasure(
        id: 't3',
        householdId: 'h1',
        storyId: 's4',
        title: 'Pedro Descobriu Matemática',
        narrative: 'O dia em que Pedro resolveu um problema que parecia impossível e percebeu que era capaz.',
        coverEmoji: '📐',
        theme: MemoryTheme.discovery,
        reflection: const Reflection('A confiança cresce quando superamos o que achávamos difícil.'),
        date: now.subtract(const Duration(hours: 4)),
      ),
    ];
  }
}
