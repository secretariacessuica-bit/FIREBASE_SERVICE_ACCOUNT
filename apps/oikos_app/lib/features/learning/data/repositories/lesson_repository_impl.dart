import '../../domain/entities/lesson.dart';
import '../../domain/repositories/lesson_repository.dart';
import '../datasources/learning_local_data_source.dart';

class LessonRepositoryImpl implements LessonRepository {
  final LearningLocalDataSource localDataSource;

  LessonRepositoryImpl(this.localDataSource);

  @override
  Future<Lesson?> getLessonById(String lessonId) async {
    final model = await localDataSource.getLessonById(lessonId);
    return model?.toEntity();
  }

  @override
  Future<List<Lesson>> getLessonsByChapterId(String chapterId) async {
    // Ideally this would be optimized, but for local data we might need to fetch the journey
    // or rely on a specific lookup. For now we will return empty or we'd need a method in datasource.
    // Assuming we fetch from journeys
    throw UnimplementedError("Use getChapterById to retrieve lessons instead");
  }
}
