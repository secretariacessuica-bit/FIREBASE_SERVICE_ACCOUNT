import 'difficulty_level.dart';

class SceneObjectSpec {
  final String id;
  final String emoji;
  final String semanticLabel;
  final String toolId;

  const SceneObjectSpec({
    required this.id,
    required this.emoji,
    required this.semanticLabel,
    required this.toolId,
  });
}

class LearningDecision {
  final String toolId;
  final String toolEmoji;
  final String topic;
  final DifficultyLevel difficulty;
  final int durationMinutes;
  final String reason;
  final double confidence; // 0.0 - 1.0
  final String motivationLine;
  final List<SceneObjectSpec> sceneObjects;

  const LearningDecision({
    required this.toolId,
    required this.toolEmoji,
    required this.topic,
    required this.difficulty,
    required this.durationMinutes,
    required this.reason,
    required this.confidence,
    required this.motivationLine,
    required this.sceneObjects,
  });
}
