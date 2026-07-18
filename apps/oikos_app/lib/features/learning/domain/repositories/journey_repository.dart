import '../entities/journey.dart';
import '../entities/chapter.dart';

abstract class JourneyRepository {
  Future<List<Journey>> getJourneys();
  Future<Journey?> getJourneyById(String journeyId);
  Future<Chapter?> getChapterById(String journeyId, String chapterId);
}
