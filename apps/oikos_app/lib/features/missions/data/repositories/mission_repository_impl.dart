import '../../domain/entities/daily_mission.dart';
import '../../domain/repositories/mission_repository.dart';
import '../datasources/mission_local_data_source.dart';
import '../models/daily_mission_model.dart';

class MissionRepositoryImpl implements MissionRepository {
  final MissionLocalDataSource localDataSource;

  MissionRepositoryImpl(this.localDataSource);

  @override
  Future<void> saveDailyMission(DailyMission dailyMission) async {
    final model = DailyMissionModel.fromEntity(dailyMission);
    await localDataSource.saveDailyMission(model);
  }

  @override
  Future<DailyMission?> getDailyMission(String userId, DateTime date) async {
    final model = await localDataSource.getDailyMission(userId, date);
    return model?.toEntity();
  }

  @override
  Future<List<DailyMission>> getMissionHistory(String userId) async {
    final models = await localDataSource.getMissionHistory(userId);
    return models.map((m) => m.toEntity()).toList();
  }
}
