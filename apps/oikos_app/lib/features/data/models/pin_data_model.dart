import 'package:hive/hive.dart';
import '../../domain/entities/pin_data.dart';

part 'pin_data_model.g.dart';

@HiveType(typeId: 2)
class PinDataModel extends HiveObject {
  @HiveField(0)
  final String userId;

  @HiveField(1)
  final String hashedPin;

  @HiveField(2)
  final int failedAttempts;

  @HiveField(3)
  final DateTime? lockedUntil;

  PinDataModel({
    required this.userId,
    required this.hashedPin,
    required this.failedAttempts,
    this.lockedUntil,
  });

  factory PinDataModel.fromEntity(PinData entity) {
    return PinDataModel(
      userId: entity.userId,
      hashedPin: entity.hashedPin,
      failedAttempts: entity.failedAttempts,
      lockedUntil: entity.lockedUntil,
    );
  }

  PinData toEntity() {
    return PinData(
      userId: userId,
      hashedPin: hashedPin,
      failedAttempts: failedAttempts,
      lockedUntil: lockedUntil,
    );
  }
}
