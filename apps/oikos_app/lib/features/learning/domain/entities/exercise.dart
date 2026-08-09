import 'question.dart';
import '../../../missions/domain/entities/mission.dart';

class Exercise {
  final String id;
  final Question question;
  final Mission? mission;

  const Exercise({
    required this.id,
    required this.question,
    this.mission,
  });

  Exercise copyWith({
    String? id,
    Question? question,
    Mission? mission,
  }) {
    return Exercise(
      id: id ?? this.id,
      question: question ?? this.question,
      mission: mission ?? this.mission,
    );
  }
}
