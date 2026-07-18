import '../models/story_chapter_model.dart';
import '../models/story_highlight_model.dart';

abstract class StoryLocalDataSource {
  Future<List<StoryChapterModel>> getChapters();
  Future<List<StoryHighlightModel>> getHighlights();
}
