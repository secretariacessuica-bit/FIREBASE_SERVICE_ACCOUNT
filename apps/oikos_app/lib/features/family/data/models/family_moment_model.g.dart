// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_moment_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class FamilyMomentModelAdapter extends TypeAdapter<FamilyMomentModel> {
  @override
  final int typeId = 32;

  @override
  FamilyMomentModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return FamilyMomentModel(
      id: fields[0] as String,
      title: fields[1] as String,
      description: fields[2] as String,
      typeName: fields[3] as String,
      date: fields[4] as DateTime,
      emoji: fields[5] as String?,
    );
  }

  @override
  void write(BinaryWriter writer, FamilyMomentModel obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.description)
      ..writeByte(3)
      ..write(obj.typeName)
      ..writeByte(4)
      ..write(obj.date)
      ..writeByte(5)
      ..write(obj.emoji);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMomentModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
