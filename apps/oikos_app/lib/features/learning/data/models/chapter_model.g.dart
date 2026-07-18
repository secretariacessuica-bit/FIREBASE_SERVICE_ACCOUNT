// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'chapter_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class ChapterModelAdapter extends TypeAdapter<ChapterModel> {
  @override
  final int typeId = 11;

  @override
  ChapterModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return ChapterModel(
      id: fields[0] as String,
      title: fields[1] as String,
      description: fields[2] as String,
      lessons: (fields[3] as List).cast<LessonModel>(),
      order: fields[4] as int,
      coverImageUrl: fields[5] as String?,
    );
  }

  @override
  void write(BinaryWriter writer, ChapterModel obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.description)
      ..writeByte(3)
      ..write(obj.lessons)
      ..writeByte(4)
      ..write(obj.order)
      ..writeByte(5)
      ..write(obj.coverImageUrl);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChapterModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
