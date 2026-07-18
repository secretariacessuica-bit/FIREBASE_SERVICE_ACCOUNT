import 'dart:convert';
import 'package:hive/hive.dart';
import '../../domain/entities/learning_event.dart';
import '../../domain/entities/difficulty_level.dart';

part 'learning_event_model.g.dart';

@HiveType(typeId: 20)
class LearningEventModel extends HiveObject {
  @HiveField(0)
  final String eventId;

  @HiveField(1)
  final String sessionId;

  @HiveField(2)
  final String userId;

  @HiveField(3)
  final DateTime timestamp;

  @HiveField(4)
  final String toolId;

  @HiveField(5)
  final String topic;

  @HiveField(6)
  final String difficultyStr;

  @HiveField(7)
  final String device;

  @HiveField(8)
  final String locale;

  @HiveField(9)
  final String eventType;

  @HiveField(10)
  final String payloadJson;

  LearningEventModel({
    required this.eventId,
    required this.sessionId,
    required this.userId,
    required this.timestamp,
    required this.toolId,
    required this.topic,
    required this.difficultyStr,
    required this.device,
    required this.locale,
    required this.eventType,
    required this.payloadJson,
  });

  factory LearningEventModel.fromEntity(LearningEvent event) {
    String eventType = event.runtimeType.toString();
    Map<String, dynamic> payload = {};

    if (event is HintRequested) {
      payload['challengeId'] = event.challengeId;
    } else if (event is ExerciseAnswered) {
      payload['challengeId'] = event.challengeId;
      payload['isCorrect'] = event.isCorrect;
      payload['timeTakenSeconds'] = event.timeTakenSeconds;
    } else if (event is CorrectionShown) {
      payload['challengeId'] = event.challengeId;
    } else if (event is ExerciseCompleted) {
      payload['challengeId'] = event.challengeId;
    } else if (event is SessionFinished) {
      payload['totalDurationSeconds'] = event.totalDurationSeconds;
      payload['accuracy'] = event.accuracy;
      payload['errorCount'] = event.errorCount;
    } else if (event is SessionAbandoned) {
      payload['durationBeforeAbandonSeconds'] = event.durationBeforeAbandonSeconds;
      payload['reason'] = event.reason;
    }

    return LearningEventModel(
      eventId: event.eventId,
      sessionId: event.sessionId,
      userId: event.userId,
      timestamp: event.timestamp,
      toolId: event.toolId,
      topic: event.topic,
      difficultyStr: event.difficulty.name,
      device: event.device,
      locale: event.locale,
      eventType: eventType,
      payloadJson: jsonEncode(payload),
    );
  }

  LearningEvent toEntity() {
    final diff = DifficultyLevel.values.firstWhere(
      (e) => e.name == difficultyStr,
      orElse: () => DifficultyLevel.medium,
    );
    final payload = jsonDecode(payloadJson) as Map<String, dynamic>;

    switch (eventType) {
      case 'SessionStarted':
        return SessionStarted(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff, device: device, locale: locale,
        );
      case 'SessionPaused':
        return SessionPaused(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
        );
      case 'SessionResumed':
        return SessionResumed(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
        );
      case 'HintRequested':
        return HintRequested(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          challengeId: payload['challengeId'] ?? '',
        );
      case 'ExerciseAnswered':
        return ExerciseAnswered(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          challengeId: payload['challengeId'] ?? '',
          isCorrect: payload['isCorrect'] ?? false,
          timeTakenSeconds: payload['timeTakenSeconds'] ?? 0,
        );
      case 'CorrectionShown':
        return CorrectionShown(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          challengeId: payload['challengeId'] ?? '',
        );
      case 'ExerciseCompleted':
        return ExerciseCompleted(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          challengeId: payload['challengeId'] ?? '',
        );
      case 'SessionFinished':
        return SessionFinished(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          totalDurationSeconds: payload['totalDurationSeconds'] ?? 0,
          accuracy: payload['accuracy']?.toDouble() ?? 0.0,
          errorCount: payload['errorCount'] ?? 0,
        );
      case 'SessionAbandoned':
        return SessionAbandoned(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff,
          durationBeforeAbandonSeconds: payload['durationBeforeAbandonSeconds'] ?? 0,
          reason: payload['reason'] ?? 'unknown',
        );
      default:
        // Fallback to a base event if type is unknown
        return SessionStarted(
          eventId: eventId, sessionId: sessionId, userId: userId, timestamp: timestamp,
          toolId: toolId, topic: topic, difficulty: diff, device: device, locale: locale,
        );
    }
  }
}
