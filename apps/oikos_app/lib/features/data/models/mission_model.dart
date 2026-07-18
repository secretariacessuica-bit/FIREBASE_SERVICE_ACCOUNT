import 'package:hive/hive.dart';
import '../../domain/entities/mission.dart';

part 'mission_model.g.dart';

@HiveType(typeId: 4)
class MissionModel extends HiveObject {
  @HiveField(0)
  final String id;

  @HiveField(1)
  final String familyId;

  @HiveField(2)
  final String title;

  @HiveField(3)
  final int totalSteps;

  @HiveField(4)
  final int completedSteps;

  @HiveField(5)
  final bool isCompleted;

  MissionModel({
    required this.id,
    required this.familyId,
    required this.title,
    required this.totalSteps,
    required this.completedSteps,
    required this.isCompleted,
  });

  factory MissionModel.fromEntity(Mission entity) {
    return MissionModel(
      id: entity.id,
      familyId: entity.familyId,
      title: entity.title,
      totalSteps: entity.totalSteps,
      completedSteps: entity.completedSteps,
      isCompleted: entity.isCompleted,
    );
  }

  Mission toEntity() {
    return Mission(
      id: id,
      familyId: familyId,
      title: title,
      totalSteps: totalSteps,
      completedSteps: completedSteps,
      isCompleted: isCompleted,
    );
  }
}
