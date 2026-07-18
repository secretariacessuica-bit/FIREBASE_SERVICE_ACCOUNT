// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'lesson_result_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class LessonResultModelAdapter extends TypeAdapter<LessonResultModel> {
  @override
  final int typeId = 20;

  @override
  LessonResultModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return LessonResultModel(
      lessonId: fields[0] as String,
      correctAnswers: fields[1] as int,
      wrongAnswers: fields[2] as int,
      earnedXp: fields[3] as int,
      completed: fields[4] as bool,
    );
  }

  @override
  void write(BinaryWriter writer, LessonResultModel obj) {
    writer
      ..writeByte(5)
      ..writeByte(0)
      ..write(obj.lessonId)
      ..writeByte(1)
      ..write(obj.correctAnswers)
      ..writeByte(2)
      ..write(obj.wrongAnswers)
      ..writeByte(3)
      ..write(obj.earnedXp)
      ..writeByte(4)
      ..write(obj.completed);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LessonResultModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
