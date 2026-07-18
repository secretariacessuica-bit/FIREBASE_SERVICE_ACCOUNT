import '../entities/user_progress.dart';
import '../entities/lesson_progress.dart';

abstract class ProgressRepository {
  Future<UserProgress> getUserProgress(String userId, String journeyId);
  Future<void> saveUserProgress(UserProgress progress);
  
  Future<LessonProgress?> getLessonProgress(String userId, String lessonId);
  Future<void> saveLessonProgress(LessonProgress progress);
}
