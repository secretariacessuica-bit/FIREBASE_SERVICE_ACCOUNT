import 'dart:ui';
import 'identity_expression.dart';
import 'memory.dart';
import 'recent_activity.dart';
import 'profile_theme.dart';

class MemberIdentity {
  final String id;
  final String name;
  final String avatarUrl; // or local asset path
  final Color favoriteColor;
  final DateTime firstAccessDate;
  final List<String> interests;
  final List<Memory> memoryCollection;
  final List<RecentActivity> recentActivities;
  final IdentityExpression currentExpression;
  final ProfileTheme theme; // Define se a UI é formal, playful ou gamified

  const MemberIdentity({
    required this.id,
    required this.name,
    required this.avatarUrl,
    required this.favoriteColor,
    required this.firstAccessDate,
    required this.interests,
    required this.memoryCollection,
    required this.recentActivities,
    this.currentExpression = IdentityExpression.calm,
    this.theme = ProfileTheme.formal,
  });

  int get daysLearning {
    return DateTime.now().difference(firstAccessDate).inDays;
  }
}
