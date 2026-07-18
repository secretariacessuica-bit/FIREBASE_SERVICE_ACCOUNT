import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/daily_mission.dart';
import '../../domain/entities/mission.dart';
import '../../data/content/mission_seeds.dart';

class DailyMissionNotifier extends Notifier<DailyMission?> {
  @override
  DailyMission? build() {
    // For now, load from seeds
    return DailyMission(
      id: 'dm_1',
      userId: 'user_1',
      date: DateTime.now(),
      missions: MissionSeeds.getDailySeeds(),
    );
  }

  void completeMission(String missionId) {
    if (state == null) return;
    
    final updatedMissions = state!.missions.map((m) {
      if (m.id == missionId && !m.isCompleted) {
        return m.copyWith(isCompleted: true, completedAt: DateTime.now());
      }
      return m;
    }).toList();

    state = state!.copyWith(missions: updatedMissions);
  }
}

final dailyMissionProvider = NotifierProvider<DailyMissionNotifier, DailyMission?>(() {
  return DailyMissionNotifier();
});
