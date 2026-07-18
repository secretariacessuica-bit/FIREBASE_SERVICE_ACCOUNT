import 'package:hive/hive.dart';
import 'age_experience_mode.dart';

class FamilyMember {
  final String id;
  final String familyId;
  final String name;
  final String emoji;
  final String colorHex;
  final DateTime lastLogin;
  final String? avatarAsset;
  final DateTime? birthDate;
  final AgeExperienceMode experienceMode;

  FamilyMember({
    required this.id,
    required this.familyId,
    required this.name,
    required this.emoji,
    required this.colorHex,
    required this.lastLogin,
    this.avatarAsset,
    this.birthDate,
    this.experienceMode = AgeExperienceMode.adult,
  });

  FamilyMember copyWith({
    String? id,
    String? familyId,
    String? name,
    String? emoji,
    String? colorHex,
    DateTime? lastLogin,
    String? avatarAsset,
    DateTime? birthDate,
    AgeExperienceMode? experienceMode,
  }) {
    return FamilyMember(
      id: id ?? this.id,
      familyId: familyId ?? this.familyId,
      name: name ?? this.name,
      emoji: emoji ?? this.emoji,
      colorHex: colorHex ?? this.colorHex,
      lastLogin: lastLogin ?? this.lastLogin,
      avatarAsset: avatarAsset ?? this.avatarAsset,
      birthDate: birthDate ?? this.birthDate,
      experienceMode: experienceMode ?? this.experienceMode,
    );
  }
}
