import 'package:hive/hive.dart';
import '../../domain/entities/family_member.dart';
import '../../domain/entities/age_experience_mode.dart';

part 'family_member_model.g.dart';

@HiveType(typeId: 1)
class FamilyMemberModel extends HiveObject {
  @HiveField(0)
  final String id;

  @HiveField(1)
  final String familyId;

  @HiveField(2)
  final String name;

  @HiveField(3)
  final String emoji;

  @HiveField(4)
  final String colorHex;

  @HiveField(5)
  final DateTime lastLogin;

  @HiveField(6)
  final String? avatarAsset;

  @HiveField(7)
  final DateTime? birthDate;

  @HiveField(8)
  final AgeExperienceMode experienceMode;

  FamilyMemberModel({
    required this.id,
    required this.familyId,
    required this.name,
    required this.emoji,
    required this.colorHex,
    required this.lastLogin,
    this.avatarAsset,
    this.birthDate,
    required this.experienceMode,
  });

  factory FamilyMemberModel.fromEntity(FamilyMember entity) {
    return FamilyMemberModel(
      id: entity.id,
      familyId: entity.familyId,
      name: entity.name,
      emoji: entity.emoji,
      colorHex: entity.colorHex,
      lastLogin: entity.lastLogin,
      avatarAsset: entity.avatarAsset,
      birthDate: entity.birthDate,
      experienceMode: entity.experienceMode,
    );
  }

  FamilyMember toEntity() {
    return FamilyMember(
      id: id,
      familyId: familyId,
      name: name,
      emoji: emoji,
      colorHex: colorHex,
      lastLogin: lastLogin,
      avatarAsset: avatarAsset,
      birthDate: birthDate,
      experienceMode: experienceMode,
    );
  }
}

