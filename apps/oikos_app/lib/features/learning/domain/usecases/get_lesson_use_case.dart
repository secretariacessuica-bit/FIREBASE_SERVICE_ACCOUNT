import '../entities/lesson.dart';
import '../repositories/lesson_repository.dart';

class GetLessonUseCase {
  final LessonRepository repository;

  GetLessonUseCase(this.repository);

  Future<Lesson?> call(String lessonId) async {
    return await repository.getLessonById(lessonId);
  }
}
