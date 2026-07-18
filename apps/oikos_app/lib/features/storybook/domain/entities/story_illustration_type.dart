enum StoryIllustrationType {
  book,
  tree,
  stars,
  family,
  heart,
  rocket,
  math,
  reading,
  kindness,
}

extension StoryIllustrationTypeExtension on StoryIllustrationType {
  String get emoji {
    switch (this) {
      case StoryIllustrationType.book: return '📖';
      case StoryIllustrationType.tree: return '🌳';
      case StoryIllustrationType.stars: return '✨';
      case StoryIllustrationType.family: return '👨‍👩‍👧‍👦';
      case StoryIllustrationType.heart: return '❤️';
      case StoryIllustrationType.rocket: return '🚀';
      case StoryIllustrationType.math: return '📐';
      case StoryIllustrationType.reading: return '📚';
      case StoryIllustrationType.kindness: return '🤝';
    }
  }
}
