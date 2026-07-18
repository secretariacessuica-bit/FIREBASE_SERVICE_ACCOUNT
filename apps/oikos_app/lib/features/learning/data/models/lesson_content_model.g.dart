// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'lesson_content_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class LessonContentModelAdapter extends TypeAdapter<LessonContentModel> {
  @override
  final int typeId = 13;

  @override
  LessonContentModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return LessonContentModel(
      id: fields[0] as String,
      text: fields[1] as String,
    );
  }

  @override
  void write(BinaryWriter writer, LessonContentModel obj) {
    writer
      ..writeByte(2)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.text);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LessonContentModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
