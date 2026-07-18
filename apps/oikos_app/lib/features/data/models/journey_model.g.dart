// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'journey_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class JourneyModelAdapter extends TypeAdapter<JourneyModel> {
  @override
  final int typeId = 5;

  @override
  JourneyModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return JourneyModel(
      id: fields[0] as String,
      userId: fields[1] as String,
      title: fields[2] as String,
      currentLessonName: fields[3] as String,
      progressPercentage: fields[4] as double,
    );
  }

  @override
  void write(BinaryWriter writer, JourneyModel obj) {
    writer
      ..writeByte(5)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.userId)
      ..writeByte(2)
      ..write(obj.title)
      ..writeByte(3)
      ..write(obj.currentLessonName)
      ..writeByte(4)
      ..write(obj.progressPercentage);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is JourneyModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
