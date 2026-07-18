class HouseholdContext {
  final List<String> emotionalSignals;
  final List<String> recurringHabits;
  final List<String> treasuredMoments;
  final List<String> anniversaries;
  final String currentSeason;
  final String? activeChapterTitle;
  final List<String> closedChapters;
  final List<String> coreLegacyValues; // O Legado permanente da família (ex: "Coragem", "Cuidado Mútuo")

  const HouseholdContext({
    required this.emotionalSignals,
    required this.recurringHabits,
    required this.treasuredMoments,
    required this.anniversaries,
    required this.currentSeason,
    this.activeChapterTitle,
    this.closedChapters = const [],
    this.coreLegacyValues = const [],
  });
}
