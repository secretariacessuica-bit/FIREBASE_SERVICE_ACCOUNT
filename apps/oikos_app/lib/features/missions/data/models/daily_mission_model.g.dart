// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'daily_mission_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class DailyMissionModelAdapter extends TypeAdapter<DailyMissionModel> {
  @override
  final int typeId = 22;

  @override
  DailyMissionModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return DailyMissionModel(
      id: fields[0] as String,
      userId: fields[1] as String,
      date: fields[2] as DateTime,
      missions: (fields[3] as List).cast<MissionModel>(),
    );
  }

  @override
  void write(BinaryWriter writer, DailyMissionModel obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.userId)
      ..writeByte(2)
      ..write(obj.date)
      ..writeByte(3)
      ..write(obj.missions);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DailyMissionModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
