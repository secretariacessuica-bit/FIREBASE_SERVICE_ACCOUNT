import 'package:hive/hive.dart';
import '../../domain/entities/learning_event.dart';
import '../../domain/repositories/trajectory_repository.dart';
import '../models/learning_event_model.dart';

class HiveTrajectoryRepository implements TrajectoryRepository {
  static const String boxName = 'trajectory_events_box';
  
  final Box<LearningEventModel> _box;

  HiveTrajectoryRepository(this._box);

  @override
  Future<void> saveEvent(LearningEvent event) async {
    final model = LearningEventModel.fromEntity(event);
    await _box.put(model.eventId, model);
  }

  @override
  Future<List<LearningEvent>> getUserEvents(String userId) async {
    final events = _box.values
        .where((e) => e.userId == userId)
        .map((e) => e.toEntity())
        .toList();
    
    events.sort((a, b) => a.timestamp.compareTo(b.timestamp));
    return events;
  }

  @override
  Future<List<LearningEvent>> getUserEventsSince(String userId, DateTime since) async {
    final events = _box.values
        .where((e) => e.userId == userId && e.timestamp.isAfter(since))
        .map((e) => e.toEntity())
        .toList();
    
    events.sort((a, b) => a.timestamp.compareTo(b.timestamp));
    return events;
  }

  @override
  Future<List<LearningEvent>> getRecentUserEvents(String userId, int limit) async {
    final events = _box.values
        .where((e) => e.userId == userId)
        .map((e) => e.toEntity())
        .toList();
    
    events.sort((a, b) => b.timestamp.compareTo(a.timestamp)); // Descending
    return events.take(limit).toList();
  }
}
