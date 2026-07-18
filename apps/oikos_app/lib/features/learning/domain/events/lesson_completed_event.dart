import '../entities/lesson_result.dart';

class LessonCompletedEvent {
  final String userId;
  final String journeyId;
  final String chapterId;
  final LessonResult result;

  const LessonCompletedEvent({
    required this.userId,
    required this.journeyId,
    required this.chapterId,
    required this.result,
  });
}
