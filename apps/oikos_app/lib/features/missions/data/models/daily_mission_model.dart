import 'package:hive/hive.dart';
import '../../domain/entities/daily_mission.dart';
import 'mission_model.dart';

part 'daily_mission_model.g.dart';

@HiveType(typeId: 22)
class DailyMissionModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String userId;
  @HiveField(2)
  final DateTime date;
  @HiveField(3)
  final List<MissionModel> missions;

  DailyMissionModel({
    required this.id,
    required this.userId,
    required this.date,
    required this.missions,
  });

  factory DailyMissionModel.fromEntity(DailyMission entity) {
    return DailyMissionModel(
      id: entity.id,
      userId: entity.userId,
      date: entity.date,
      missions: entity.missions.map((m) => MissionModel.fromEntity(m)).toList(),
    );
  }

  DailyMission toEntity() {
    return DailyMission(
      id: id,
      userId: userId,
      date: date,
      missions: missions.map((m) => m.toEntity()).toList(),
    );
  }
}
