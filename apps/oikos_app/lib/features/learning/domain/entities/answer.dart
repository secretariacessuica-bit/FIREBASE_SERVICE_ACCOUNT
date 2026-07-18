class Answer {
  final String id;
  final String text;
  final bool isCorrect;
  final String? explanation;

  const Answer({
    required this.id,
    required this.text,
    required this.isCorrect,
    this.explanation,
  });

  Answer copyWith({
    String? id,
    String? text,
    bool? isCorrect,
    String? explanation,
  }) {
    return Answer(
      id: id ?? this.id,
      text: text ?? this.text,
      isCorrect: isCorrect ?? this.isCorrect,
      explanation: explanation ?? this.explanation,
    );
  }
}
