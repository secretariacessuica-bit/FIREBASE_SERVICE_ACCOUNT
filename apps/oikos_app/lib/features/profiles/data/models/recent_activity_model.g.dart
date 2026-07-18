// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recent_activity_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class RecentActivityModelAdapter extends TypeAdapter<RecentActivityModel> {
  @override
  final int typeId = 42;

  @override
  RecentActivityModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return RecentActivityModel(
      id: fields[0] as String,
      description: fields[1] as String,
      date: fields[2] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, RecentActivityModel obj) {
    writer
      ..writeByte(3)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.description)
      ..writeByte(2)
      ..write(obj.date);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecentActivityModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
