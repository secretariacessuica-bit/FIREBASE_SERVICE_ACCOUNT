class Memory {
  final String id;
  final String title;
  final String description;
  final DateTime date;
  final String? emoji;

  const Memory({
    required this.id,
    required this.title,
    required this.description,
    required this.date,
    this.emoji,
  });
}
