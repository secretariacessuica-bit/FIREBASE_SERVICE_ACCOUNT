import 'mission_category.dart';

class MissionTemplate {
  final String id;
  final String title;
  final String description;
  final MissionCategory category;
  final int baseXpReward;

  const MissionTemplate({
    required this.id,
    required this.title,
    required this.description,
    required this.category,
    this.baseXpReward = 10,
  });
}
