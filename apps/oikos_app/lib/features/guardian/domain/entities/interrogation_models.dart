class InterrogationPrompt {
  final String id;
  final String question;
  final String context; // Ex: 'hobby_discovery', 'learning_style', 'travel_goals'

  const InterrogationPrompt({
    required this.id,
    required this.question,
    required this.context,
  });
}

class GuardianInsight {
  final String originalAnswer;
  final List<String> extractedTags;
  final String detectedMood;

  const GuardianInsight({
    required this.originalAnswer,
    required this.extractedTags,
    required this.detectedMood,
  });
}
