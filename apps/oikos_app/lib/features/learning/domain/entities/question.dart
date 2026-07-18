import 'answer.dart';

enum QuestionType {
  multipleChoice,
}

class Question {
  final String id;
  final QuestionType type;
  final String text;
  final List<Answer> options;
  final String? hint;

  const Question({
    required this.id,
    this.type = QuestionType.multipleChoice,
    required this.text,
    required this.options,
    this.hint,
  });

  Question copyWith({
    String? id,
    QuestionType? type,
    String? text,
    List<Answer>? options,
    String? hint,
  }) {
    return Question(
      id: id ?? this.id,
      type: type ?? this.type,
      text: text ?? this.text,
      options: options ?? this.options,
      hint: hint ?? this.hint,
    );
  }
}
