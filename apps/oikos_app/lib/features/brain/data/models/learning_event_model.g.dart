// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_event_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class LearningEventModelAdapter extends TypeAdapter<LearningEventModel> {
  @override
  final int typeId = 20;

  @override
  LearningEventModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return LearningEventModel(
      eventId: fields[0] as String,
      sessionId: fields[1] as String,
      userId: fields[2] as String,
      timestamp: fields[3] as DateTime,
      toolId: fields[4] as String,
      topic: fields[5] as String,
      difficultyStr: fields[6] as String,
      device: fields[7] as String,
      locale: fields[8] as String,
      eventType: fields[9] as String,
      payloadJson: fields[10] as String,
    );
  }

  @override
  void write(BinaryWriter writer, LearningEventModel obj) {
    writer
      ..writeByte(11)
      ..writeByte(0)
      ..write(obj.eventId)
      ..writeByte(1)
      ..write(obj.sessionId)
      ..writeByte(2)
      ..write(obj.userId)
      ..writeByte(3)
      ..write(obj.timestamp)
      ..writeByte(4)
      ..write(obj.toolId)
      ..writeByte(5)
      ..write(obj.topic)
      ..writeByte(6)
      ..write(obj.difficultyStr)
      ..writeByte(7)
      ..write(obj.device)
      ..writeByte(8)
      ..write(obj.locale)
      ..writeByte(9)
      ..write(obj.eventType)
      ..writeByte(10)
      ..write(obj.payloadJson);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningEventModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
