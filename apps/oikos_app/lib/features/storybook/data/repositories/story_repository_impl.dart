import '../../domain/entities/story_chapter.dart';
import '../../domain/entities/story_highlight.dart';
import '../../domain/repositories/story_repository.dart';
import '../datasources/story_local_data_source.dart';

class StoryRepositoryImpl implements StoryRepository {
  final StoryLocalDataSource localDataSource;

  StoryRepositoryImpl(this.localDataSource);

  @override
  Future<List<StoryChapter>> getChapters() async {
    final models = await localDataSource.getChapters();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<List<StoryHighlight>> getHighlights() async {
    final models = await localDataSource.getHighlights();
    return models.map((m) => m.toEntity()).toList();
  }
}
