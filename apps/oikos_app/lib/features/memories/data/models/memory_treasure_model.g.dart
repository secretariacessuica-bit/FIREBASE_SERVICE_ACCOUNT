// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'memory_treasure_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class MemoryTreasureModelAdapter extends TypeAdapter<MemoryTreasureModel> {
  @override
  final int typeId = 61;

  @override
  MemoryTreasureModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return MemoryTreasureModel(
      id: fields[0] as String,
      storyId: fields[1] as String,
      title: fields[2] as String,
      narrative: fields[3] as String,
      coverEmoji: fields[4] as String,
      themeName: fields[5] as String,
      reflectionText: fields[6] as String,
      date: fields[7] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, MemoryTreasureModel obj) {
    writer
      ..writeByte(8)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.storyId)
      ..writeByte(2)
      ..write(obj.title)
      ..writeByte(3)
      ..write(obj.narrative)
      ..writeByte(4)
      ..write(obj.coverEmoji)
      ..writeByte(5)
      ..write(obj.themeName)
      ..writeByte(6)
      ..write(obj.reflectionText)
      ..writeByte(7)
      ..write(obj.date);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MemoryTreasureModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
