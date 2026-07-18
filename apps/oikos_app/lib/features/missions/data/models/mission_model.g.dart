// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mission_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class MissionModelAdapter extends TypeAdapter<MissionModel> {
  @override
  final int typeId = 21;

  @override
  MissionModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return MissionModel(
      id: fields[0] as String,
      title: fields[1] as String,
      description: fields[2] as String,
      categoryName: fields[3] as String,
      xpReward: fields[4] as int,
      isCompleted: fields[5] as bool,
      completedAt: fields[6] as DateTime?,
    );
  }

  @override
  void write(BinaryWriter writer, MissionModel obj) {
    writer
      ..writeByte(7)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.description)
      ..writeByte(3)
      ..write(obj.categoryName)
      ..writeByte(4)
      ..write(obj.xpReward)
      ..writeByte(5)
      ..write(obj.isCompleted)
      ..writeByte(6)
      ..write(obj.completedAt);
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
