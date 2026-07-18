import 'chapter.dart';

class Journey {
  final String id;
  final String title;
  final String description;
  final String colorHex;
  final String iconPath;
  final List<Chapter> chapters;

  const Journey({
    required this.id,
    required this.title,
    required this.description,
    required this.colorHex,
    required this.iconPath,
    required this.chapters,
  });

  Journey copyWith({
    String? id,
    String? title,
    String? description,
    String? colorHex,
    String? iconPath,
    List<Chapter>? chapters,
  }) {
    return Journey(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      colorHex: colorHex ?? this.colorHex,
      iconPath: iconPath ?? this.iconPath,
      chapters: chapters ?? this.chapters,
    );
  }
}
