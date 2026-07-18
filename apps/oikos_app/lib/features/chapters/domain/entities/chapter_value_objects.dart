enum ChapterTheme {
  discovery,
  growth,
  belonging,
  healing,
  newBeginning,
  adventure,
  resilience,
  tradition,
  care,
  wonder,
  unknown
}

class ChapterPeriod {
  final DateTime startedAt;
  final DateTime? endedAt;

  const ChapterPeriod({
    required this.startedAt,
    this.endedAt,
  });

  bool get isOpen => endedAt == null;
  
  Duration? get duration => endedAt != null ? endedAt!.difference(startedAt) : null;
}

enum ChapterStatus {
  draft,
  suggested,
  active,
  closed,
  archived
}
