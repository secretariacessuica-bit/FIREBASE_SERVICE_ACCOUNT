import 'package:hive/hive.dart';
import '../../domain/entities/answer.dart';

part 'answer_model.g.dart';

@HiveType(typeId: 16)
class AnswerModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String text;
  @HiveField(2)
  final bool isCorrect;
  @HiveField(3)
  final String? explanation;

  AnswerModel({
    required this.id,
    required this.text,
    required this.isCorrect,
    this.explanation,
  });

  factory AnswerModel.fromEntity(Answer entity) {
    return AnswerModel(
      id: entity.id,
      text: entity.text,
      isCorrect: entity.isCorrect,
      explanation: entity.explanation,
    );
  }

  Answer toEntity() {
    return Answer(
      id: id,
      text: text,
      isCorrect: isCorrect,
      explanation: explanation,
    );
  }
}
