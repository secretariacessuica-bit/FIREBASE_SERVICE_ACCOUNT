import 'story_illustration_type.dart';
import 'story_mood.dart';

class Story {
  final String id;
  final String title;
  final String narrative;
  final String lumoReflection;
  final DateTime date;
  final StoryMood mood;
  final StoryIllustrationType illustration;

  const Story({
    required this.id,
    required this.title,
    required this.narrative,
    required this.lumoReflection,
    required this.date,
    required this.mood,
    required this.illustration,
  });
}
