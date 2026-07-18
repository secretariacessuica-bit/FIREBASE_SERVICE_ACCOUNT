// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'member_identity_model.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class MemberIdentityModelAdapter extends TypeAdapter<MemberIdentityModel> {
  @override
  final int typeId = 43;

  @override
  MemberIdentityModel read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return MemberIdentityModel(
      id: fields[0] as String,
      name: fields[1] as String,
      avatarUrl: fields[2] as String,
      favoriteColorValue: fields[3] as int,
      firstAccessDate: fields[4] as DateTime,
      interests: (fields[5] as List).cast<String>(),
      memoryCollection: (fields[6] as List).cast<MemoryModel>(),
      recentActivities: (fields[7] as List).cast<RecentActivityModel>(),
      currentExpressionName: fields[8] as String,
    );
  }

  @override
  void write(BinaryWriter writer, MemberIdentityModel obj) {
    writer
      ..writeByte(9)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.name)
      ..writeByte(2)
      ..write(obj.avatarUrl)
      ..writeByte(3)
      ..write(obj.favoriteColorValue)
      ..writeByte(4)
      ..write(obj.firstAccessDate)
      ..writeByte(5)
      ..write(obj.interests)
      ..writeByte(6)
      ..write(obj.memoryCollection)
      ..writeByte(7)
      ..write(obj.recentActivities)
      ..writeByte(8)
      ..write(obj.currentExpressionName);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MemberIdentityModelAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
