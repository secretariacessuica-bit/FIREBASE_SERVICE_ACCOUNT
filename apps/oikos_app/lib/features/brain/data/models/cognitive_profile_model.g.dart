// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cognitive_profile_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class CognitiveProfileModelAdapter
    extends TypeAdapter<CognitiveProfileModel> {
  @override
  final int typeId = 90;

  @override
  CognitiveProfileModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return CognitiveProfileModel(
      userId: fields[0] as String,
      traitsJson: fields[1] as String,
      recentChangesJson: fields[2] as String,
      cursorOccurredAt: fields[3] as DateTime?,
      cursorSequence: fields[4] as int?,
      engineVersion: fields[5] as int,
      status: fields[6] as String,
      revision: fields[7] as int,
      lastUpdated: fields[8] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, CognitiveProfileModel obj) {
    writer
      ..writeByte(9)
      ..writeByte(0)
      ..write(obj.userId)
      ..writeByte(1)
      ..write(obj.traitsJson)
      ..writeByte(2)
      ..write(obj.recentChangesJson)
      ..writeByte(3)
      ..write(obj.cursorOccurredAt)
      ..writeByte(4)
      ..write(obj.cursorSequence)
      ..writeByte(5)
      ..write(obj.engineVersion)
      ..writeByte(6)
      ..write(obj.status)
      ..writeByte(7)
      ..write(obj.revision)
      ..writeByte(8)
      ..write(obj.lastUpdated);
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CognitiveProfileModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;

  @override
  int get hashCode => typeId.hashCode;
}
