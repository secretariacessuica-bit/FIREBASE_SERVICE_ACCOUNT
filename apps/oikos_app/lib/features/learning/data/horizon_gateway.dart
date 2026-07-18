import '../../profiles/domain/entities/member_identity.dart';
import '../../profiles/domain/entities/profile_theme.dart';

class DailyMission {
  final String id;
  final String title;
  final String type; // ex: 'vocabulary', 'grammar', 'speaking'
  final int xpReward;

  const DailyMission({
    required this.id,
    required this.title,
    required this.type,
    required this.xpReward,
  });

  factory DailyMission.fromJson(Map<String, dynamic> json) {
    return DailyMission(
      id: json['id'] as String,
      title: json['title'] as String,
      type: json['type'] as String,
      xpReward: json['xpReward'] as int,
    );
  }
}

abstract class HorizonGateway {
  Future<List<DailyMission>> getDailyPacing(String memberId, {ProfileTheme theme = ProfileTheme.gamified});
}
