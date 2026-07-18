import 'package:hive/hive.dart';
import '../../domain/entities/journey.dart';

part 'journey_model.g.dart';

@HiveType(typeId: 5)
class JourneyModel extends HiveObject {
  @HiveField(0)
  final String id;

  @HiveField(1)
  final String userId;

  @HiveField(2)
  final String title;

  @HiveField(3)
  final String currentLessonName;

  @HiveField(4)
  final double progressPercentage;

  JourneyModel({
    required this.id,
    required this.userId,
    required this.title,
    required this.currentLessonName,
    required this.progressPercentage,
  });

  factory JourneyModel.fromEntity(Journey entity) {
    return JourneyModel(
      id: entity.id,
      userId: entity.userId,
      title: entity.title,
      currentLessonName: entity.currentLessonName,
      progressPercentage: entity.progressPercentage,
    );
  }

  Journey toEntity() {
    return Journey(
      id: id,
      userId: userId,
      title: title,
      currentLessonName: currentLessonName,
      progressPercentage: progressPercentage,
    );
  }
}
