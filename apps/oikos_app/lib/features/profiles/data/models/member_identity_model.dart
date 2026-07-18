import 'package:flutter/material.dart';
import 'package:hive/hive.dart';
import '../../domain/entities/identity_expression.dart';
import '../../domain/entities/member_identity.dart';
import 'memory_model.dart';
import 'recent_activity_model.dart';

part 'member_identity_model.g.dart';

@HiveType(typeId: 43)
class MemberIdentityModel {
  @HiveField(0)
  final String id;
  @HiveField(1)
  final String name;
  @HiveField(2)
  final String avatarUrl;
  @HiveField(3)
  final int favoriteColorValue;
  @HiveField(4)
  final DateTime firstAccessDate;
  @HiveField(5)
  final List<String> interests;
  @HiveField(6)
  final List<MemoryModel> memoryCollection;
  @HiveField(7)
  final List<RecentActivityModel> recentActivities;
  @HiveField(8)
  final String currentExpressionName;

  MemberIdentityModel({
    required this.id,
    required this.name,
    required this.avatarUrl,
    required this.favoriteColorValue,
    required this.firstAccessDate,
    required this.interests,
    required this.memoryCollection,
    required this.recentActivities,
    required this.currentExpressionName,
  });

  factory MemberIdentityModel.fromEntity(MemberIdentity entity) {
    return MemberIdentityModel(
      id: entity.id,
      name: entity.name,
      avatarUrl: entity.avatarUrl,
      favoriteColorValue: entity.favoriteColor.value,
      firstAccessDate: entity.firstAccessDate,
      interests: entity.interests,
      memoryCollection: entity.memoryCollection.map((m) => MemoryModel.fromEntity(m)).toList(),
      recentActivities: entity.recentActivities.map((a) => RecentActivityModel.fromEntity(a)).toList(),
      currentExpressionName: entity.currentExpression.name,
    );
  }

  MemberIdentity toEntity() {
    return MemberIdentity(
      id: id,
      name: name,
      avatarUrl: avatarUrl,
      favoriteColor: Color(favoriteColorValue),
      firstAccessDate: firstAccessDate,
      interests: interests,
      memoryCollection: memoryCollection.map((m) => m.toEntity()).toList(),
      recentActivities: recentActivities.map((a) => a.toEntity()).toList(),
      currentExpression: IdentityExpression.values.firstWhere(
        (e) => e.name == currentExpressionName,
        orElse: () => IdentityExpression.calm,
      ),
    );
  }
}
