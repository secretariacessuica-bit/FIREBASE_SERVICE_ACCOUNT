// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'user_progress_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class UserProgressModelAdapter extends TypeAdapter<UserProgressModel> {
  @override
  final int typeId = 18;

  @override
  UserProgressModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return UserProgressModel(
      userId: fields[0] as String,
      journeyId: fields[1] as String,
      completedLessonIds: (fields[2] as List).cast<String>(),
      currentLessonId: fields[3] as String?,
      totalXpEarned: fields[4] as int,
      unlockedRewardIds: (fields[5] as List).cast<String>(),
    );
  }

  @override
  void write(BinaryWriter writer, UserProgressModel obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.userId)
      ..writeByte(1)
      ..write(obj.journeyId)
      ..writeByte(2)
      ..write(obj.completedLessonIds)
      ..writeByte(3)
      ..write(obj.currentLessonId)
      ..writeByte(4)
      ..write(obj.totalXpEarned)
      ..writeByte(5)
      ..write(obj.unlockedRewardIds);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UserProgressModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
