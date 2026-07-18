import 'package:hive/hive.dart';
import '../../domain/entities/journey.dart';
import 'chapter_model.dart';

part 'journey_model.g.dart';

@HiveType(typeId: 10)
class JourneyModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final String description;
  @HiveField(3)
  final String colorHex;
  @HiveField(4)
  final String iconPath;
  @HiveField(5)
  final List<ChapterModel> chapters;

  JourneyModel({
    required this.id,
    required this.title,
    required this.description,
    required this.colorHex,
    required this.iconPath,
    required this.chapters,
  });

  factory JourneyModel.fromEntity(Journey entity) {
    return JourneyModel(
      id: entity.id,
      title: entity.title,
      description: entity.description,
      colorHex: entity.colorHex,
      iconPath: entity.iconPath,
      chapters: entity.chapters.map((c) => ChapterModel.fromEntity(c)).toList(),
    );
  }

  Journey toEntity() {
    return Journey(
      id: id,
      title: title,
      description: description,
      colorHex: colorHex,
      iconPath: iconPath,
      chapters: chapters.map((c) => c.toEntity()).toList(),
    );
  }
}
