import 'package:hive/hive.dart';
import '../../domain/entities/question.dart';
import 'answer_model.dart';

part 'question_model.g.dart';

@HiveType(typeId: 15)
class QuestionModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String typeString;
  @HiveField(2)
  final String text;
  @HiveField(3)
  final List<AnswerModel> options;
  @HiveField(4)
  final String? hint;

  QuestionModel({
    required this.id,
    required this.typeString,
    required this.text,
    required this.options,
    this.hint,
  });

  factory QuestionModel.fromEntity(Question entity) {
    return QuestionModel(
      id: entity.id,
      typeString: entity.type.name,
      text: entity.text,
      options: entity.options.map((o) => AnswerModel.fromEntity(o)).toList(),
      hint: entity.hint,
    );
  }

  Question toEntity() {
    return Question(
      id: id,
      type: QuestionType.values.firstWhere((e) => e.name == typeString, orElse: () => QuestionType.multipleChoice),
      text: text,
      options: options.map((o) => o.toEntity()).toList(),
      hint: hint,
    );
  }
}
