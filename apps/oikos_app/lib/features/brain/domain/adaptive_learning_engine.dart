import 'dart:math';
import 'entities/cognitive_profile.dart';
import 'entities/cognitive_trait.dart';
import 'entities/learner_snapshot.dart';
import 'entities/learning_context.dart';
import 'entities/learning_decision.dart';
import 'entities/difficulty_level.dart';
import 'entities/mood_hint.dart';
import 'entities/tool_definition.dart';
import '../data/tool_registry.dart';

class AdaptiveLearningEngine {
  static LearningDecision decide(
    LearnerSnapshot snapshot,
    LearningContext context,
  ) {
    // 1. Get implemented tools
    final availableTools = ToolRegistry.implemented();

    // 2. Filter by AgeExperienceMode
    var filtered = availableTools
        .where((t) => t.suitableModes.contains(snapshot.mode))
        .toList();

    // 3. Filter by MinutesAvailable
    filtered = filtered
        .where((t) =>
            t.minMinutes <= context.minutesAvailable)
        .toList();

    // If no tools available after strict filtering, fallback to all implemented
    if (filtered.isEmpty) {
      filtered = availableTools;
    }

    // 4. Score tools
    final random = Random(context.today.millisecondsSinceEpoch + snapshot.userId.hashCode);
    
    // Score tools
    filtered.sort((a, b) {
      double scoreA = 0;
      double scoreB = 0;

      if (a.suitableMoods.contains(context.moodHint)) scoreA += 10;
      if (b.suitableMoods.contains(context.moodHint)) scoreB += 10;

      if (!snapshot.recentToolIds.contains(a.id)) scoreA += 5;
      if (!snapshot.recentToolIds.contains(b.id)) scoreB += 5;

      // Cognitive trait boost: if the learner has a strong, stable preference
      // for a tool category, amplify its score proportionally.
      if (snapshot.cognitiveProfile != null) {
        scoreA += _cognitiveBoost(a, snapshot.cognitiveProfile!);
        scoreB += _cognitiveBoost(b, snapshot.cognitiveProfile!);
      }

      return scoreB.compareTo(scoreA); // Descending
    });

    final selectedTool = filtered.isNotEmpty ? filtered.first : ToolRegistry.all.first;

    // 5. Build scene objects (top 3 tools, or random if not enough)
    final sceneObjects = <SceneObjectSpec>[];
    final objectIds = ['primary', 'secondary', 'tertiary'];
    for (int i = 0; i < 3; i++) {
      final toolForObj = (i < filtered.length) ? filtered[i] : availableTools[random.nextInt(availableTools.length)];
      sceneObjects.add(SceneObjectSpec(
        id: objectIds[i],
        emoji: toolForObj.emoji,
        semanticLabel: toolForObj.name,
        toolId: toolForObj.id,
      ));
    }

    // 6. Calculate confidence
    double confidence = 0.2; // Base confidence
    if (snapshot.totalSessionCount > 10) confidence += 0.3;
    if (snapshot.recentAccuracy > 0.7) confidence += 0.2;
    confidence = min(1.0, confidence);

    // 7. Generate motivation line
    String motivation = "Pronto para continuar?";
    if (context.moodHint == MoodHint.tired) {
      motivation = "Vamos fazer uma sessão rápida e tranquila hoje.";
    } else if (context.moodHint == MoodHint.energetic) {
      motivation = "Vamos com tudo! Hora de avançar rápido!";
    } else if (context.minutesAvailable < 10) {
      motivation = "Temos pouco tempo, mas cada minuto conta!";
    }

    return LearningDecision(
      toolId: selectedTool.id,
      toolEmoji: selectedTool.emoji,
      topic: 'General Practice',
      difficulty: snapshot.recentAccuracy > 0.8 ? DifficultyLevel.hard : DifficultyLevel.medium,
      durationMinutes: max(selectedTool.minMinutes, min(context.minutesAvailable, selectedTool.maxMinutes)),
      reason: 'Selecionado com base no seu humor e tempo disponível.',
      confidence: confidence,
      motivationLine: motivation,
      sceneObjects: sceneObjects,
    );
  }

  /// Computes the effective weight of a [CognitiveTrait] for decision-making.
  ///
  /// This is the Brain's responsibility — not the trait's — so the formula
  /// can evolve (e.g., squaring confidence, using sigmoid on value) without
  /// touching the domain entity.
  static double computeWeight(CognitiveTrait trait) {
    return trait.value * trait.confidenceScore * trait.stability;
  }

  /// Returns a cognitive boost score in range [0, ~15] for [tool] based on
  /// traits in [profile]. Positive traits that match a tool's category amplify
  /// the score; negative traits (aversion) reduce it.
  static double _cognitiveBoost(ToolDefinition tool, CognitiveProfile profile) {
    const traitToToolCategory = <String, List<String>>{
      'narrative_preference': ['story_quest', 'story'],
      'gamification_response': ['word_blast', 'game', 'quiz'],
      'persistence_drive': ['flash_card', 'spaced_repetition'],
      'accuracy_profile': ['fill_blank', 'translation'],
      'hint_dependency': ['guided', 'hint'],
      'response_speed': ['speed_round', 'timed'],
    };

    double boost = 0;
    for (final entry in traitToToolCategory.entries) {
      final trait = profile.traits[entry.key];
      if (trait == null) continue;

      final matchesCategory = entry.value.any(
        (cat) => tool.id.toLowerCase().contains(cat),
      );
      if (matchesCategory) {
        // effective weight: Brain's formula (can change without touching entity)
        boost += computeWeight(trait) * 15.0;
      }
    }
    return boost;
  }
}
