import 'package:hive/hive.dart';
import '../../domain/entities/memory.dart';

part 'memory_model.g.dart';

@HiveType(typeId: 41)
class MemoryModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final DateTime date;
  @HiveField(4)
  final String? emoji;

  MemoryModel({
    required this.id,
    required this.title,
    required this.description,
    required this.date,
    this.emoji,
  });

  factory MemoryModel.fromEntity(Memory entity) {
    return MemoryModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      date: entity.date,
      emoji: entity.emoji,
    );
  }

  Memory toEntity() {
    return Memory(
      id: id,
      title: title,
      description: description,
      date: date,
      emoji: emoji,
    );
  }
}
