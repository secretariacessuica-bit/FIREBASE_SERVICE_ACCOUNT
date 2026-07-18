import '../models/daily_mission_model.dart';

abstract class MissionLocalDataSource {
  Future<void> saveDailyMission(DailyMissionModel dailyMission);
  Future<DailyMissionModel?> getDailyMission(String userId, DateTime date);
  Future<List<DailyMissionModel>> getMissionHistory(String userId);
}
