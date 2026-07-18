import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/repositories/journey_repository.dart';
import '../datasources/learning_local_data_source.dart';

class JourneyRepositoryImpl implements JourneyRepository {
  final LearningLocalDataSource localDataSource;

  JourneyRepositoryImpl(this.localDataSource);

  @override
  Future<List<Journey>> getJourneys() async {
    final models = await localDataSource.getJourneys();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<Journey?> getJourneyById(String journeyId) async {
    final model = await localDataSource.getJourneyById(journeyId);
    return model?.toEntity();
  }

  @override
  Future<Chapter?> getChapterById(String journeyId, String chapterId) async {
    final journey = await getJourneyById(journeyId);
    if (journey == null) return null;
    
    try {
      return journey.chapters.firstWhere((c) => c.id == chapterId);
    } catch (e) {
      return null;
    }
  }
}
