// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pin_data_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class PinDataModelAdapter extends TypeAdapter<PinDataModel> {
  @override
  final int typeId = 2;

  @override
  PinDataModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return PinDataModel(
      userId: fields[0] as String,
      hashedPin: fields[1] as String,
      failedAttempts: fields[2] as int,
      lockedUntil: fields[3] as DateTime?,
    );
  }

  @override
  void write(BinaryWriter writer, PinDataModel obj) {
    writer
      ..writeByte(4)
      ..writeByte(0)
      ..write(obj.userId)
      ..writeByte(1)
      ..write(obj.hashedPin)
      ..writeByte(2)
      ..write(obj.failedAttempts)
      ..writeByte(3)
      ..write(obj.lockedUntil);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PinDataModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
