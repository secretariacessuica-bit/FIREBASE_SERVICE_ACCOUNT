class Reward {
  final String id;
  final String title;
  final int xpAmount;
  final String emoji;

  const Reward({
    required this.id,
    required this.title,
    required this.xpAmount,
    required this.emoji,
  });

  Reward copyWith({
    String? id,
    String? title,
    int? xpAmount,
    String? emoji,
  }) {
    return Reward(
      id: id ?? this.id,
      title: title ?? this.title,
      xpAmount: xpAmount ?? this.xpAmount,
      emoji: emoji ?? this.emoji,
    );
  }
}
