import '../../domain/entities/story.dart';
import '../../domain/entities/story_chapter.dart';
import '../../domain/entities/story_highlight.dart';
import '../../domain/entities/story_illustration_type.dart';
import '../../domain/entities/story_mood.dart';

class StorybookDemoData {
  static List<StoryChapter> getChapters() {
    final now = DateTime.now();
    return [
      StoryChapter(
        id: 'chap1',
        title: 'Julho de 2026',
        period: now,
        stories: [
          Story(
            id: 's1',
            title: 'Nossa Primeira Semana',
            narrative: 'Nesta semana nossa família descobriu que aprender juntos é muito mais divertido.',
            lumoReflection: 'Toda grande história começa com pequenos passos.',
            date: now.subtract(const Duration(days: 7)),
            mood: StoryMood.celebration,
            illustration: StoryIllustrationType.family,
          ),
          Story(
            id: 's2',
            title: 'O Livro Preferido do Pedro',
            narrative: 'Pedro ficou encantado com as aventuras dos dinossauros e pediu para ler novamente.',
            lumoReflection: 'Cada página virada é uma semente plantada na imaginação.',
            date: now.subtract(const Duration(days: 5)),
            mood: StoryMood.curiosity,
            illustration: StoryIllustrationType.reading,
          ),
          Story(
            id: 's3',
            title: 'Uma Gentileza',
            narrative: 'Maria ajudou João espontaneamente hoje.',
            lumoReflection: 'Pequenas atitudes constroem um lar gigante.',
            date: now.subtract(const Duration(days: 2)),
            mood: StoryMood.kindness,
            illustration: StoryIllustrationType.heart,
          ),
          Story(
            id: 's4',
            title: 'Um Pequeno Cientista',
            narrative: 'Hoje Pedro resolveu um problema de Matemática que parecia impossível alguns dias atrás.',
            lumoReflection: 'O aprendizado é a mágica de transformar "não consigo" em "eu fiz".',
            date: now.subtract(const Duration(hours: 4)),
            mood: StoryMood.discovery,
            illustration: StoryIllustrationType.math,
          ),
        ],
      )
    ];
  }

  static List<StoryHighlight> getHighlights() {
    final now = DateTime.now();
    return [
      StoryHighlight(
        id: 'h1',
        title: 'Primeira Lição',
        illustration: StoryIllustrationType.rocket,
        date: now.subtract(const Duration(days: 10)),
      ),
      StoryHighlight(
        id: 'h2',
        title: 'Primeiro Livro',
        illustration: StoryIllustrationType.book,
        date: now.subtract(const Duration(days: 8)),
      ),
      StoryHighlight(
        id: 'h3',
        title: 'Primeira Gentileza',
        illustration: StoryIllustrationType.heart,
        date: now.subtract(const Duration(days: 6)),
      ),
      StoryHighlight(
        id: 'h4',
        title: 'Primeiro Dia em Família',
        illustration: StoryIllustrationType.family,
        date: now.subtract(const Duration(days: 12)),
      ),
    ];
  }
}
