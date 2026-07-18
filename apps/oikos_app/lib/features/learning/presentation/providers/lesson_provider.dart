import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/lesson.dart';

class LessonNotifier extends Notifier<Lesson?> {
  @override
  Lesson? build() {
    return null;
  }

  void openLesson(Lesson lesson) {
    state = lesson;
  }

  void closeLesson() {
    state = null;
  }
}

final lessonProvider = NotifierProvider<LessonNotifier, Lesson?>(() {
  return LessonNotifier();
});
