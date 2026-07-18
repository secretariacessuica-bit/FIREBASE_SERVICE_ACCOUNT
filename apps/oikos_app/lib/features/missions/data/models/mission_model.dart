import 'package:hive/hive.dart';
import '../../domain/entities/mission.dart';
import '../../domain/entities/mission_category.dart';

part 'mission_model.g.dart';

@HiveType(typeId: 21)
class MissionModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final String categoryName;
  @HiveField(4)
  final int xpReward;
  @HiveField(5)
  final bool isCompleted;
  @HiveField(6)
  final DateTime? completedAt;

  MissionModel({
    required this.id,
    required this.title,
    required this.description,
    required this.categoryName,
    required this.xpReward,
    required this.isCompleted,
    this.completedAt,
  });

  factory MissionModel.fromEntity(Mission entity) {
    return MissionModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      categoryName: entity.category.name,
      xpReward: entity.xpReward,
      isCompleted: entity.isCompleted,
      completedAt: entity.completedAt,
    );
  }

  Mission toEntity() {
    return Mission(
      id: id,
      title: title,
      description: description,
      category: MissionCategory.values.firstWhere(
        (e) => e.name == categoryName,
        orElse: () => MissionCategory.aprender,
      ),
      xpReward: xpReward,
      isCompleted: isCompleted,
      completedAt: completedAt,
    );
  }
}
