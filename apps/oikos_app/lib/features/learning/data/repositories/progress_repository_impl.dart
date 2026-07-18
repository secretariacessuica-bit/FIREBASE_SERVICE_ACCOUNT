import '../../domain/entities/user_progress.dart';
import '../../domain/entities/lesson_progress.dart';
import '../../domain/repositories/progress_repository.dart';
import '../datasources/learning_local_data_source.dart';
import '../models/user_progress_model.dart';
import '../models/lesson_progress_model.dart';

class ProgressRepositoryImpl implements ProgressRepository {
  final LearningLocalDataSource localDataSource;

  ProgressRepositoryImpl(this.localDataSource);

  @override
  Future<UserProgress> getUserProgress(String userId, String journeyId) async {
    final model = await localDataSource.getUserProgress(userId, journeyId);
    return model.toEntity();
  }

  @override
  Future<void> saveUserProgress(UserProgress progress) async {
    await localDataSource.saveUserProgress(UserProgressModel.fromEntity(progress));
  }

  @override
  Future<LessonProgress?> getLessonProgress(String userId, String lessonId) async {
    final model = await localDataSource.getLessonProgress(userId, lessonId);
    return model?.toEntity();
  }

  @override
  Future<void> saveLessonProgress(LessonProgress progress) async {
    await localDataSource.saveLessonProgress(LessonProgressModel.fromEntity(progress));
  }
}
