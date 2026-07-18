// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'story_highlight_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class StoryHighlightModelAdapter extends TypeAdapter<StoryHighlightModel> {
  @override
  final int typeId = 53;

  @override
  StoryHighlightModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return StoryHighlightModel(
      id: fields[0] as String,
      title: fields[1] as String,
      illustrationName: fields[2] as String,
      date: fields[3] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, StoryHighlightModel obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.illustrationName)
      ..writeByte(3)
      ..write(obj.date);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StoryHighlightModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
