import 'family_moment_type.dart';

class FamilyMoment {
  final String id;
  final String title;
  final String description;
  final FamilyMomentType type;
  final DateTime date;
  final String? emoji;

  const FamilyMoment({
    required this.id,
    required this.title,
    required this.description,
    required this.type,
    required this.date,
    this.emoji,
  });
}
