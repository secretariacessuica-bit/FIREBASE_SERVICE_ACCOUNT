import '../models/journey_model.dart';
import '../models/lesson_model.dart';
import '../models/user_progress_model.dart';
import '../models/lesson_progress_model.dart';

abstract class LearningLocalDataSource {
  Future<List<JourneyModel>> getJourneys();
  Future<JourneyModel?> getJourneyById(String journeyId);
  Future<void> saveJourneys(List<JourneyModel> journeys);
  
  Future<LessonModel?> getLessonById(String lessonId);
  
  Future<UserProgressModel> getUserProgress(String userId, String journeyId);
  Future<void> saveUserProgress(UserProgressModel progress);
  
  Future<LessonProgressModel?> getLessonProgress(String userId, String lessonId);
  Future<void> saveLessonProgress(LessonProgressModel progress);
}
