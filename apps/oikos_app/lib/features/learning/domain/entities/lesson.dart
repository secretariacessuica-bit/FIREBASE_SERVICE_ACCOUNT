import 'exercise.dart';

class LessonContent {
  final String id;
  final String text;

  const LessonContent({
    required this.id,
    required this.text,
  });

  LessonContent copyWith({
    String? id,
    String? text,
  }) {
    return LessonContent(
      id: id ?? this.id,
      text: text ?? this.text,
    );
  }
}

class Lesson {
  final String id;
  final String title;
  final String description;
  final LessonContent content;
  final List<Exercise> exercises;
  final int order;

  const Lesson({
    required this.id,
    required this.title,
    required this.description,
    required this.content,
    required this.exercises,
    required this.order,
  });

  Lesson copyWith({
    String? id,
    String? title,
    String? description,
    LessonContent? content,
    List<Exercise>? exercises,
    int? order,
  }) {
    return Lesson(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      content: content ?? this.content,
      exercises: exercises ?? this.exercises,
      order: order ?? this.order,
    );
  }
}
