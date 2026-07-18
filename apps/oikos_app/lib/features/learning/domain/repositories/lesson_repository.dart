import '../entities/lesson.dart';

abstract class LessonRepository {
  Future<Lesson?> getLessonById(String lessonId);
  Future<List<Lesson>> getLessonsByChapterId(String chapterId);
}
