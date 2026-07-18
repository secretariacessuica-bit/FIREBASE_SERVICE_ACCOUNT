// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'story_chapter_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class StoryChapterModelAdapter extends TypeAdapter<StoryChapterModel> {
  @override
  final int typeId = 52;

  @override
  StoryChapterModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return StoryChapterModel(
      id: fields[0] as String,
      title: fields[1] as String,
      period: fields[2] as DateTime,
      stories: (fields[3] as List).cast<StoryModel>(),
    );
  }

  @override
  void write(BinaryWriter writer, StoryChapterModel obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.period)
      ..writeByte(3)
      ..write(obj.stories);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StoryChapterModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
