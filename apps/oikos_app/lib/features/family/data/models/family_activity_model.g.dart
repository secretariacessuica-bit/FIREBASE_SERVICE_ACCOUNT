// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_activity_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class FamilyActivityModelAdapter extends TypeAdapter<FamilyActivityModel> {
  @override
  final int typeId = 31;

  @override
  FamilyActivityModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return FamilyActivityModel(
      id: fields[0] as String,
      memberId: fields[1] as String,
      eventType: fields[2] as String,
      date: fields[3] as DateTime,
      metadata: (fields[4] as Map?)?.cast<String, dynamic>(),
    );
  }

  @override
  void write(BinaryWriter writer, FamilyActivityModel obj) {
    writer
      ..writeByte(5)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.memberId)
      ..writeByte(2)
      ..write(obj.eventType)
      ..writeByte(3)
      ..write(obj.date)
      ..writeByte(4)
      ..write(obj.metadata);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyActivityModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
