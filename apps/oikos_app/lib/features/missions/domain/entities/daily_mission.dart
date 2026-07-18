import 'mission.dart';

class DailyMission {
  final String id;
  final String userId;
  final DateTime date;
  final List<Mission> missions;

  const DailyMission({
    required this.id,
    required this.userId,
    required this.date,
    required this.missions,
  });

  bool get isAllCompleted => missions.isNotEmpty && missions.every((m) => m.isCompleted);
  int get completedCount => missions.where((m) => m.isCompleted).length;

  DailyMission copyWith({
    String? id,
    String? userId,
    DateTime? date,
    List<Mission>? missions,
  }) {
    return DailyMission(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      date: date ?? this.date,
      missions: missions ?? this.missions,
    );
  }
}
