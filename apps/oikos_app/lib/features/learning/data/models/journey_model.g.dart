// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'journey_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class JourneyModelAdapter extends TypeAdapter<JourneyModel> {
  @override
  final int typeId = 10;

  @override
  JourneyModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return JourneyModel(
      id: fields[0] as String,
      title: fields[1] as String,
      description: fields[2] as String,
      colorHex: fields[3] as String,
      iconPath: fields[4] as String,
      chapters: (fields[5] as List).cast<ChapterModel>(),
    );
  }

  @override
  void write(BinaryWriter writer, JourneyModel obj) {
    writer
      ..writeByte(6)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.title)
      ..writeByte(2)
      ..write(obj.description)
      ..writeByte(3)
      ..write(obj.colorHex)
      ..writeByte(4)
      ..write(obj.iconPath)
      ..writeByte(5)
      ..write(obj.chapters);
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
