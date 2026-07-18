import 'question.dart';

class Exercise {
  final String id;
  final Question question;

  const Exercise({
    required this.id,
    required this.question,
  });

  Exercise copyWith({
    String? id,
    Question? question,
  }) {
    return Exercise(
      id: id ?? this.id,
      question: question ?? this.question,
    );
  }
}
