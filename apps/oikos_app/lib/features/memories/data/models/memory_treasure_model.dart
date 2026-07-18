import 'package:hive/hive.dart';
import '../../domain/entities/memory_theme.dart';
import '../../domain/entities/memory_treasure.dart';
import '../../domain/entities/reflection.dart';

part 'memory_treasure_model.g.dart';

@HiveType(typeId: 61)
class MemoryTreasureModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String storyId;
  @HiveField(2)
  final String title;
  @HiveField(3)
  final String narrative;
  @HiveField(4)
  final String coverEmoji;
  @HiveField(5)
  final String themeName;
  @HiveField(6)
  final String reflectionText;
  @HiveField(7)
  final DateTime date;

  MemoryTreasureModel({
    required this.id,
    required this.storyId,
    required this.title,
    required this.narrative,
    required this.coverEmoji,
    required this.themeName,
    required this.reflectionText,
    required this.date,
  });

  factory MemoryTreasureModel.fromEntity(MemoryTreasure entity) {
    return MemoryTreasureModel(
      id: entity.id,
      storyId: entity.storyId,
      title: entity.title,
      narrative: entity.narrative,
      coverEmoji: entity.coverEmoji,
      themeName: entity.theme.name,
      reflectionText: entity.reflection.text,
      date: entity.date,
    );
  }

  MemoryTreasure toEntity() {
    return MemoryTreasure(
      id: id,
      storyId: storyId,
      title: title,
      narrative: narrative,
      coverEmoji: coverEmoji,
      theme: MemoryTheme.values.firstWhere((e) => e.name == themeName, orElse: () => MemoryTheme.family),
      reflection: Reflection(reflectionText),
      date: date,
    );
  }
}
