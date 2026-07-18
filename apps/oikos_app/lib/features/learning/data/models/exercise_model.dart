import 'package:hive/hive.dart';
import '../../domain/entities/exercise.dart';
import 'question_model.dart';

part 'exercise_model.g.dart';

@HiveType(typeId: 14)
class ExerciseModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final QuestionModel question;

  ExerciseModel({
    required this.id,
    required this.question,
  });

  factory ExerciseModel.fromEntity(Exercise entity) {
    return ExerciseModel(
      id: entity.id,
      question: QuestionModel.fromEntity(entity.question),
    );
  }

  Exercise toEntity() {
    return Exercise(
      id: id,
      question: question.toEntity(),
    );
  }
}
