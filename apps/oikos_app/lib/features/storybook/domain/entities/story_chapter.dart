import 'story.dart';

class StoryChapter {
  final String id;
  final String title; // e.g. "Julho de 2026"
  final DateTime period;
  final List<Story> stories;

  const StoryChapter({
    required this.id,
    required this.title,
    required this.period,
    required this.stories,
  });
}
