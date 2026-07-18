class NarrativeContext {
  final List<String> narrativeSentences;

  const NarrativeContext({
    required this.narrativeSentences,
  });

  String get condensedNarrative => narrativeSentences.join('\n');
}
