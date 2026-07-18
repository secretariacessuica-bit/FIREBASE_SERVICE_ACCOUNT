// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'story_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class StoryModelAdapter extends TypeAdapter<StoryModel> {
  @override
  final int typeId = 51;

  @override
  StoryModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return StoryModel(
      id: fields[0] as String,
      title: fields[1] as String,
      narrative: fields[2] as String,
      lumoReflection: fields[3] as String,
      date: fields[4] as DateTime,
      moodName: fields[5] as String,
      illustrationName: fields[6] as String,
    );
  }

  @override
  void write(BinaryWriter writer, StoryModel obj) {
    writer
      ..writeByte(7)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.narrative)
      ..writeByte(3)
      ..write(obj.lumoReflection)
      ..writeByte(4)
      ..write(obj.date)
      ..writeByte(5)
      ..write(obj.moodName)
      ..writeByte(6)
      ..write(obj.illustrationName);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StoryModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
