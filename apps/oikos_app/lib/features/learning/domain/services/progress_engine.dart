import '../entities/user_progress.dart';

abstract class ProgressEngine {
  /// Awards XP and returns the updated UserProgress.
  /// Also handles leveling up, streaks, and overall percentage recalculations.
  Future<UserProgress> awardXp(String userId, String journeyId, int xpAmount);
  
  /// Gets the current progress for the user in a specific journey.
  Future<UserProgress> getProgress(String userId, String journeyId);
}
