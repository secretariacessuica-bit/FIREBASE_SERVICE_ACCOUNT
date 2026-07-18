// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'age_experience_mode.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class AgeExperienceModeAdapter extends TypeAdapter<AgeExperienceMode> {
  @override
  final int typeId = 80;

  @override
  AgeExperienceMode read(BinaryReader reader) {
    switch (reader.readByte()) {
      case 0:
        return AgeExperienceMode.earlyChildhood;
      case 1:
        return AgeExperienceMode.explorer;
      case 2:
        return AgeExperienceMode.teen;
      case 3:
        return AgeExperienceMode.youngMentor;
      case 4:
        return AgeExperienceMode.adult;
      case 5:
        return AgeExperienceMode.senior;
      default:
        return AgeExperienceMode.earlyChildhood;
    }
  }

  @override
  void write(BinaryWriter writer, AgeExperienceMode obj) {
    switch (obj) {
      case AgeExperienceMode.earlyChildhood:
        writer.writeByte(0);
        break;
      case AgeExperienceMode.explorer:
        writer.writeByte(1);
        break;
      case AgeExperienceMode.teen:
        writer.writeByte(2);
        break;
      case AgeExperienceMode.youngMentor:
        writer.writeByte(3);
        break;
      case AgeExperienceMode.adult:
        writer.writeByte(4);
        break;
      case AgeExperienceMode.senior:
        writer.writeByte(5);
        break;
    }
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AgeExperienceModeAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
