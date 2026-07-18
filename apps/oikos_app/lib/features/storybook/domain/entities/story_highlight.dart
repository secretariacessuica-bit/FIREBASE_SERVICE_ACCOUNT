import 'story_illustration_type.dart';

class StoryHighlight {
  final String id;
  final String title;
  final StoryIllustrationType illustration;
  final DateTime date;

  const StoryHighlight({
    required this.id,
    required this.title,
    required this.illustration,
    required this.date,
  });
}
