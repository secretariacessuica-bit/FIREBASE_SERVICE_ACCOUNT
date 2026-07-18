import 'package:hive/hive.dart';
import '../../domain/entities/reward.dart';

part 'reward_model.g.dart';

@HiveType(typeId: 17)
class RewardModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String title;
  @HiveField(2)
  final int xpAmount;
  @HiveField(3)
  final String emoji;

  RewardModel({
    required this.id,
    required this.title,
    required this.xpAmount,
    required this.emoji,
  });

  factory RewardModel.fromEntity(Reward entity) {
    return RewardModel(
      id: entity.id,
      title: entity.title,
      xpAmount: entity.xpAmount,
      emoji: entity.emoji,
    );
  }

  Reward toEntity() {
    return Reward(
      id: id,
      title: title,
      xpAmount: xpAmount,
      emoji: emoji,
    );
  }
}
