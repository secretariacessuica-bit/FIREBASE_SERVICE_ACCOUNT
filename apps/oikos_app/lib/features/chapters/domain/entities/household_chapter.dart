import 'chapter_value_objects.dart';

class HouseholdChapter {
  final String chapterId;
  final String householdId;
  final String chapterTitle;
  final String chapterReflection;
  final ChapterPeriod chapterPeriod;
  final ChapterTheme chapterTheme;
  final ChapterStatus chapterStatus;
  
  final List<String> chapterMilestones;
  final List<String> chapterTreasures; // Referências (IDs) aos tesouros que pertencem a esta Era
  final List<String> chapterStories;   // Referências (IDs) às histórias que pertencem a esta Era

  HouseholdChapter({
    required this.chapterId,
    required this.householdId,
    required this.chapterTitle,
    required this.chapterReflection,
    required this.chapterPeriod,
    required this.chapterTheme,
    required this.chapterStatus,
    this.chapterMilestones = const [],
    this.chapterTreasures = const [],
    this.chapterStories = const [],
  });

  void _assertNotClosed() {
    if (chapterStatus == ChapterStatus.closed || chapterStatus == ChapterStatus.archived) {
      throw StateError("Capítulos fechados são imutáveis e não podem ser alterados.");
    }
  }

  HouseholdChapter addStory(String storyId) {
    _assertNotClosed();
    return _copyWith(chapterStories: [...chapterStories, storyId]);
  }

  HouseholdChapter addTreasure(String treasureId) {
    _assertNotClosed();
    return _copyWith(chapterTreasures: [...chapterTreasures, treasureId]);
  }

  HouseholdChapter changeReflection(String newReflection) {
    _assertNotClosed();
    return _copyWith(chapterReflection: newReflection);
  }

  HouseholdChapter changeTheme(ChapterTheme newTheme) {
    _assertNotClosed();
    return _copyWith(chapterTheme: newTheme);
  }

  HouseholdChapter closeChapter(DateTime closingDate) {
    _assertNotClosed();
    return _copyWith(
      chapterStatus: ChapterStatus.closed,
      chapterPeriod: ChapterPeriod(startedAt: chapterPeriod.startedAt, endedAt: closingDate),
    );
  }

  HouseholdChapter _copyWith({
    String? chapterTitle,
    String? chapterReflection,
    ChapterPeriod? chapterPeriod,
    ChapterTheme? chapterTheme,
    ChapterStatus? chapterStatus,
    List<String>? chapterMilestones,
    List<String>? chapterTreasures,
    List<String>? chapterStories,
  }) {
    return HouseholdChapter(
      chapterId: chapterId,
      householdId: householdId,
      chapterTitle: chapterTitle ?? this.chapterTitle,
      chapterReflection: chapterReflection ?? this.chapterReflection,
      chapterPeriod: chapterPeriod ?? this.chapterPeriod,
      chapterTheme: chapterTheme ?? this.chapterTheme,
      chapterStatus: chapterStatus ?? this.chapterStatus,
      chapterMilestones: chapterMilestones ?? this.chapterMilestones,
      chapterTreasures: chapterTreasures ?? this.chapterTreasures,
      chapterStories: chapterStories ?? this.chapterStories,
    );
  }
}
