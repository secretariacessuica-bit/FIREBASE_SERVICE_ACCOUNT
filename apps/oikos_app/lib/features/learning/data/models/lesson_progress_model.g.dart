// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'lesson_progress_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class LessonProgressModelAdapter extends TypeAdapter<LessonProgressModel> {
  @override
  final int typeId = 19;

  @override
  LessonProgressModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return LessonProgressModel(
      lessonId: fields[0] as String,
      userId: fields[1] as String,
      isCompleted: fields[2] as bool,
      lastAccessedExerciseIndex: fields[3] as int,
      lastAccessedAt: fields[4] as DateTime?,
    );
  }

  @override
  void write(BinaryWriter writer, LessonProgressModel obj) {
    writer
      ..writeByte(5)
      ..writeByte(0)
      ..write(obj.lessonId)
      ..writeByte(1)
      ..write(obj.userId)
      ..writeByte(2)
      ..write(obj.isCompleted)
      ..writeByte(3)
      ..write(obj.lastAccessedExerciseIndex)
      ..writeByte(4)
      ..write(obj.lastAccessedAt);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LessonProgressModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
