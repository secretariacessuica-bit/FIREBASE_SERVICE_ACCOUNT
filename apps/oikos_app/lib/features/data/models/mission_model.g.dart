// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mission_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class MissionModelAdapter extends TypeAdapter<MissionModel> {
  @override
  final int typeId = 4;

  @override
  MissionModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return MissionModel(
      id: fields[0] as String,
      familyId: fields[1] as String,
      title: fields[2] as String,
      totalSteps: fields[3] as int,
      completedSteps: fields[4] as int,
      isCompleted: fields[5] as bool,
    );
  }

  @override
  void write(BinaryWriter writer, MissionModel obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.familyId)
      ..writeByte(2)
      ..write(obj.title)
      ..writeByte(3)
      ..write(obj.totalSteps)
      ..writeByte(4)
      ..write(obj.completedSteps)
      ..writeByte(5)
      ..write(obj.isCompleted);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MissionModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
