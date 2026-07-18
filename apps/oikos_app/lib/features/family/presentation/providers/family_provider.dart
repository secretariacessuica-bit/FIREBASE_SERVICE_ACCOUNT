import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/family_activity.dart';
import '../../domain/entities/family_moment.dart';
import '../../data/content/family_demo_data.dart';

class FamilyState {
  final List<FamilyActivity> activities;
  final List<FamilyMoment> moments;
  final int daysLearningTogether;

  const FamilyState({
    this.activities = const [],
    this.moments = const [],
    this.daysLearningTogether = 0,
  });

  FamilyState copyWith({
    List<FamilyActivity>? activities,
    List<FamilyMoment>? moments,
    int? daysLearningTogether,
  }) {
    return FamilyState(
      activities: activities ?? this.activities,
      moments: moments ?? this.moments,
      daysLearningTogether: daysLearningTogether ?? this.daysLearningTogether,
    );
  }
}

class FamilyNotifier extends Notifier<FamilyState> {
  @override
  FamilyState build() {
    return FamilyState(
      activities: FamilyDemoData.getActivities(),
      moments: FamilyDemoData.getMoments(),
      daysLearningTogether: 8, // Demo value
    );
  }

  void addActivity(FamilyActivity activity) {
    state = state.copyWith(activities: [activity, ...state.activities]);
  }

  void addMoment(FamilyMoment moment) {
    state = state.copyWith(moments: [moment, ...state.moments]);
  }
}

final familyProvider = NotifierProvider<FamilyNotifier, FamilyState>(() {
  return FamilyNotifier();
});
