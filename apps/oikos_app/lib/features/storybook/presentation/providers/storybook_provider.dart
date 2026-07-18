import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/story_chapter.dart';
import '../../domain/entities/story_highlight.dart';
import '../../data/content/storybook_demo_data.dart';

final storybookChaptersProvider = Provider<List<StoryChapter>>((ref) {
  return StorybookDemoData.getChapters();
});

final storybookHighlightsProvider = Provider<List<StoryHighlight>>((ref) {
  return StorybookDemoData.getHighlights();
});
