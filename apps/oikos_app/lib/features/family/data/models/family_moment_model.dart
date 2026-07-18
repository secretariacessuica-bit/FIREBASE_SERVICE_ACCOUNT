import 'package:hive/hive.dart';
import '../../domain/entities/family_moment.dart';
import '../../domain/entities/family_moment_type.dart';

part 'family_moment_model.g.dart';

@HiveType(typeId: 32)
class FamilyMomentModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final String typeName;
  @HiveField(4)
  final DateTime date;
  @HiveField(5)
  final String? emoji;

  FamilyMomentModel({
    required this.id,
    required this.title,
    required this.description,
    required this.typeName,
    required this.date,
    this.emoji,
  });

  factory FamilyMomentModel.fromEntity(FamilyMoment entity) {
    return FamilyMomentModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      typeName: entity.type.name,
      date: entity.date,
      emoji: entity.emoji,
    );
  }

  FamilyMoment toEntity() {
    return FamilyMoment(
      id: id,
      title: title,
      description: description,
      type: FamilyMomentType.values.firstWhere(
        (e) => e.name == typeName,
        orElse: () => FamilyMomentType.celebration,
      ),
      date: date,
      emoji: emoji,
    );
  }
}
