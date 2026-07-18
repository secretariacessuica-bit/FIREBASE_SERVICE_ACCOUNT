// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_member_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class FamilyMemberModelAdapter extends TypeAdapter<FamilyMemberModel> {
  @override
  final int typeId = 1;

  @override
  FamilyMemberModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return FamilyMemberModel(
      id: fields[0] as String,
      familyId: fields[1] as String,
      name: fields[2] as String,
      emoji: fields[3] as String,
      colorHex: fields[4] as String,
      lastLogin: fields[5] as DateTime,
      avatarAsset: fields[6] as String?,
      birthDate: fields[7] as DateTime?,
      experienceMode: fields[8] as AgeExperienceMode,
    );
  }

  @override
  void write(BinaryWriter writer, FamilyMemberModel obj) {
    writer
      ..writeByte(9)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.familyId)
      ..writeByte(2)
      ..write(obj.name)
      ..writeByte(3)
      ..write(obj.emoji)
      ..writeByte(4)
      ..write(obj.colorHex)
      ..writeByte(5)
      ..write(obj.lastLogin)
      ..writeByte(6)
      ..write(obj.avatarAsset)
      ..writeByte(7)
      ..write(obj.birthDate)
      ..writeByte(8)
      ..write(obj.experienceMode);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMemberModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
