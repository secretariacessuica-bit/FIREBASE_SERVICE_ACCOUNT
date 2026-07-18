import 'difficulty_level.dart';

abstract class LearningEvent {
  final String eventId;
  final String sessionId;
  final String userId;
  final DateTime timestamp;
  
  // Metadados úteis para IA e reconstrução de contexto
  final String toolId;
  final String topic;
  final DifficultyLevel difficulty;
  final String device;
  final String locale;

  const LearningEvent({
    required this.eventId,
    required this.sessionId,
    required this.userId,
    required this.timestamp,
    required this.toolId,
    required this.topic,
    required this.difficulty,
    this.device = 'unknown',
    this.locale = 'unknown',
  });
}

class SessionStarted extends LearningEvent {
  const SessionStarted({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    super.device,
    super.locale,
  });
}

class SessionPaused extends LearningEvent {
  const SessionPaused({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
  });
}

class SessionResumed extends LearningEvent {
  const SessionResumed({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
  });
}

class HintRequested extends LearningEvent {
  final String challengeId;

  const HintRequested({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.challengeId,
  });
}

class ExerciseAnswered extends LearningEvent {
  final String challengeId;
  final bool isCorrect;
  final int timeTakenSeconds;

  const ExerciseAnswered({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.challengeId,
    required this.isCorrect,
    required this.timeTakenSeconds,
  });
}

class CorrectionShown extends LearningEvent {
  final String challengeId;

  const CorrectionShown({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.challengeId,
  });
}

class ExerciseCompleted extends LearningEvent {
  final String challengeId;

  const ExerciseCompleted({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.challengeId,
  });
}

class SessionFinished extends LearningEvent {
  final int totalDurationSeconds;
  final double accuracy;
  final int errorCount;

  const SessionFinished({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.totalDurationSeconds,
    required this.accuracy,
    required this.errorCount,
  });
}

class SessionAbandoned extends LearningEvent {
  final int durationBeforeAbandonSeconds;
  final String reason;

  const SessionAbandoned({
    required super.eventId,
    required super.sessionId,
    required super.userId,
    required super.timestamp,
    required super.toolId,
    required super.topic,
    required super.difficulty,
    required this.durationBeforeAbandonSeconds,
    this.reason = 'user_exit',
  });
}
