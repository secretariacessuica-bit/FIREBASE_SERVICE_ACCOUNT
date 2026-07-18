import 'mission_category.dart';

class Mission {
  final String id;
  final String title;
  final String description;
  final MissionCategory category;
  final int xpReward;
  final bool isCompleted;
  final DateTime? completedAt;

  const Mission({
    required this.id,
    required this.title,
    required this.description,
    required this.category,
    required this.xpReward,
    this.isCompleted = false,
    this.completedAt,
  });

  Mission copyWith({
    String? id,
    String? title,
    String? description,
    MissionCategory? category,
    int? xpReward,
    bool? isCompleted,
    DateTime? completedAt,
  }) {
    return Mission(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      category: category ?? this.category,
      xpReward: xpReward ?? this.xpReward,
      isCompleted: isCompleted ?? this.isCompleted,
      completedAt: completedAt ?? this.completedAt,
    );
  }
}
