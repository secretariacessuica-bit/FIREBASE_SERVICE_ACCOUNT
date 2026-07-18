import 'package:hive/hive.dart';
import '../../domain/entities/family_activity.dart';

part 'family_activity_model.g.dart';

@HiveType(typeId: 31)
class FamilyActivityModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String memberId;
  @HiveField(2)
  final String eventType;
  @HiveField(3)
  final DateTime date;
  @HiveField(4)
  final Map<String, dynamic>? metadata;

  FamilyActivityModel({
    required this.id,
    required this.memberId,
    required this.eventType,
    required this.date,
    this.metadata,
  });

  factory FamilyActivityModel.fromEntity(FamilyActivity entity) {
    return FamilyActivityModel(
      id: entity.id,
      memberId: entity.memberId,
      eventType: entity.eventType,
      date: entity.date,
      metadata: entity.metadata,
    );
  }

  FamilyActivity toEntity() {
    return FamilyActivity(
      id: id,
      memberId: memberId,
      eventType: eventType,
      date: date,
      metadata: metadata,
    );
  }
}
