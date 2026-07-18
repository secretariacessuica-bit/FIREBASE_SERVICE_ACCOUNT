import 'package:hive/hive.dart';
import '../../domain/entities/family.dart';

part 'family_model.g.dart';

@HiveType(typeId: 0)
class FamilyModel extends HiveObject {
  @HiveField(0)
  final String id;

  @HiveField(1)
  final String name;

  @HiveField(2)
  final DateTime createdAt;

  FamilyModel({
    required this.id,
    required this.name,
    required this.createdAt,
  });

  factory FamilyModel.fromEntity(Family entity) {
    return FamilyModel(
      id: entity.id,
      name: entity.name,
      createdAt: entity.createdAt,
    );
  }

  Family toEntity() {
    return Family(
      id: id,
      name: name,
      createdAt: createdAt,
    );
  }
}
