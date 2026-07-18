abstract class MissionRepository {
  Future<void> saveDailyMission(DailyMission dailyMission);
  Future<DailyMission?> getDailyMission(String userId, DateTime date);
  Future<List<DailyMission>> getMissionHistory(String userId);
}
