import 'package:flutter/material.dart';
import 'mood_hint.dart';

class FamilyContext {
  final bool otherMembersActive;
  final int familyStreakDays;

  const FamilyContext({
    this.otherMembersActive = false,
    this.familyStreakDays = 0,
  });
}

class LearningContext {
  final DateTime today;
  final TimeOfDay currentTime;
  final String locale;
  final int minutesAvailable;
  final MoodHint moodHint;
  final int sessionNumber;
  final bool isWeekend;
  final FamilyContext? familyContext;

  const LearningContext({
    required this.today,
    required this.currentTime,
    required this.locale,
    required this.minutesAvailable,
    required this.moodHint,
    required this.sessionNumber,
    required this.isWeekend,
    this.familyContext,
  });
}
