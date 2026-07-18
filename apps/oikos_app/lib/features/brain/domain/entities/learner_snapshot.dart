import '../../../domain/entities/age_experience_mode.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import 'cognitive_profile.dart';

class LearnerSnapshot {
  final String userId;
  final AgeExperienceMode mode;
  final ProfileTheme theme;
  final List<String> interests;
  
  final double recentAccuracy; // 0.0 to 1.0
  final List<String> recentToolIds;
  final String? activeLanguageId;
  
  final bool isFirstSession;
  final int totalSessionCount;
  final Map<String, int> toolUsageCount;

  // Observable signals for MoodHint inference
  final double averageResponseTime; // in seconds
  final int recentErrors;
  final int recentAbandons;

  /// The materialised cognitive model for this learner.
  /// Null when the Cognitive Engine has not yet produced a profile (first run).
  final CognitiveProfile? cognitiveProfile;

  const LearnerSnapshot({
    required this.userId,
    required this.mode,
    required this.theme,
    required this.interests,
    this.recentAccuracy = 0.8,
    this.recentToolIds = const [],
    this.activeLanguageId,
    this.isFirstSession = false,
    this.totalSessionCount = 0,
    this.toolUsageCount = const {},
    this.averageResponseTime = 5.0,
    this.recentErrors = 0,
    this.recentAbandons = 0,
    this.cognitiveProfile,
  });
}

