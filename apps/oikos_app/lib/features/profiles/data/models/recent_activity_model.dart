import 'package:hive/hive.dart';
import '../../domain/entities/recent_activity.dart';

part 'recent_activity_model.g.dart';

@HiveType(typeId: 42)
class RecentActivityModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String description;
  @HiveField(2)
  final DateTime date;

  RecentActivityModel({
    required this.id,
    required this.description,
    required this.date,
  });

  factory RecentActivityModel.fromEntity(RecentActivity entity) {
    return RecentActivityModel(
      id: entity.id,
      description: entity.description,
      date: entity.date,
    );
  }

  RecentActivity toEntity() {
    return RecentActivity(
      id: id,
      description: description,
      date: date,
    );
  }
}
