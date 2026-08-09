import 'mission_category.dart';

class MissionOption {
  final String id;
  final String label;
  final bool isCorrect;

  const MissionOption({
    required this.id,
    required this.label,
    required this.isCorrect,
  });
}

class Mission {
  final String id;
  final String title;
  final String description;
  final MissionCategory category;
  final int xpReward;
  final bool isCompleted;
  final DateTime? completedAt;

  // ── Novos atributos da Missão Contextual ──────────────────────────────────
  final String? contextDescription;
  final String? promptPhrase;
  final List<MissionOption>? options;
  final String? helpExplanation;
  final String? practicedCompetency;

  const Mission({
    required this.id,
    required this.title,
    required this.description,
    this.category = MissionCategory.aprender,
    this.xpReward = 20,
    this.isCompleted = false,
    this.completedAt,
    this.contextDescription,
    this.promptPhrase,
    this.options,
    this.helpExplanation,
    this.practicedCompetency,
  });

  Mission copyWith({
    String? id,
    String? title,
    String? description,
    MissionCategory? category,
    int? xpReward,
    bool? isCompleted,
    DateTime? completedAt,
    String? contextDescription,
    String? promptPhrase,
    List<MissionOption>? options,
    String? helpExplanation,
    String? practicedCompetency,
  }) {
    return Mission(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      category: category ?? this.category,
      xpReward: xpReward ?? this.xpReward,
      isCompleted: isCompleted ?? this.isCompleted,
      completedAt: completedAt ?? this.completedAt,
      contextDescription: contextDescription ?? this.contextDescription,
      promptPhrase: promptPhrase ?? this.promptPhrase,
      options: options ?? this.options,
      helpExplanation: helpExplanation ?? this.helpExplanation,
      practicedCompetency: practicedCompetency ?? this.practicedCompetency,
    );
  }
}
