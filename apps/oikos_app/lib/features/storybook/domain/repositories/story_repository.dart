import '../entities/story_chapter.dart';
import '../entities/story_highlight.dart';

abstract class StoryRepository {
  Future<List<StoryChapter>> getChapters();
  Future<List<StoryHighlight>> getHighlights();
}
