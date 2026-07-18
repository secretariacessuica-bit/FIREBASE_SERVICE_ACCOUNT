import 'lesson.dart';

class Chapter {
  final String id;
  final String title;
  final String description;
  final List<Lesson> lessons;
  final int order;
  final String? coverImageUrl;

  const Chapter({
    required this.id,
    required this.title,
    required this.description,
    required this.lessons,
    required this.order,
    this.coverImageUrl,
  });

  Chapter copyWith({
    String? id,
    String? title,
    String? description,
    List<Lesson>? lessons,
    int? order,
    String? coverImageUrl,
  }) {
    return Chapter(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      lessons: lessons ?? this.lessons,
      order: order ?? this.order,
      coverImageUrl: coverImageUrl ?? this.coverImageUrl,
    );
  }
}
