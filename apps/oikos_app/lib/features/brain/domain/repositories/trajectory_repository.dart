import '../entities/learning_event.dart';

abstract class TrajectoryRepository {
  /// Record a new event in the user's trajectory
  Future<void> saveEvent(LearningEvent event);

  /// Fetch all events for a specific user, ordered by timestamp
  Future<List<LearningEvent>> getUserEvents(String userId);

  /// Fetch events for a user within a specific timeframe
  Future<List<LearningEvent>> getUserEventsSince(String userId, DateTime since);

  /// Get the last N events for a user
  Future<List<LearningEvent>> getRecentUserEvents(String userId, int limit);
}
