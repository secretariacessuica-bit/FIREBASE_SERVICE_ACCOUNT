// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_progress_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class LearningProgressModelAdapter extends TypeAdapter<LearningProgressModel> {
  @override
  final int typeId = 7;

  @override
  LearningProgressModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return LearningProgressModel(
      userId: fields[0] as String,
      currentStreak: fields[1] as int,
      totalLessonsCompleted: fields[2] as int,
      lastLearningDate: fields[3] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, LearningProgressModel obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.userId)
      ..writeByte(1)
      ..write(obj.currentStreak)
      ..writeByte(2)
      ..write(obj.totalLessonsCompleted)
      ..writeByte(3)
      ..write(obj.lastLearningDate);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningProgressModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
