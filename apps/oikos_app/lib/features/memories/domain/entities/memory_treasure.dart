import 'memory_theme.dart';
import 'reflection.dart';

class MemoryTreasure {
  final String id;
  final String householdId;
  final String storyId;
  final String title;
  final String narrative;
  final String coverEmoji;
  final MemoryTheme theme;
  final Reflection reflection;
  final DateTime date;

  const MemoryTreasure({
    required this.id,
    required this.householdId,
    required this.storyId,
    required this.title,
    required this.narrative,
    required this.coverEmoji,
    required this.theme,
    required this.reflection,
    required this.date,
  });

  factory MemoryTreasure.create({
    required String householdId,
    required String storyId,
    required String title,
    required String narrative,
    required String coverEmoji,
    required MemoryTheme theme,
    required Reflection reflection,
    required DateTime date,
  }) {
    // ID determinístico para evitar duplicação (Regra da Sprint 011)
    final id = 'tr_${householdId}_$storyId'.hashCode.toUnsigned(32).toRadixString(16);
    return MemoryTreasure(
      id: id,
      householdId: householdId,
      storyId: storyId,
      title: title,
      narrative: narrative,
      coverEmoji: coverEmoji,
      theme: theme,
      reflection: reflection,
      date: date,
    );
  }
}
