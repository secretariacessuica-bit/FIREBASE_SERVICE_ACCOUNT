import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';

class CurrentJourneyState {
  final Journey? journey;
  final Chapter? chapter;
  final Lesson? lesson;

  const CurrentJourneyState({
    this.journey,
    this.chapter,
    this.lesson,
  });

  CurrentJourneyState copyWith({
    Journey? journey,
    Chapter? chapter,
    Lesson? lesson,
  }) {
    return CurrentJourneyState(
      journey: journey ?? this.journey,
      chapter: chapter ?? this.chapter,
      lesson: lesson ?? this.lesson,
    );
  }
}

class CurrentJourneyNotifier extends Notifier<CurrentJourneyState> {
  @override
  CurrentJourneyState build() {
    return const CurrentJourneyState();
  }

  void setJourney(Journey journey) {
    state = state.copyWith(journey: journey, chapter: null, lesson: null);
  }

  void setChapter(Chapter chapter) {
    state = state.copyWith(chapter: chapter, lesson: null);
  }

  void setLesson(Lesson lesson) {
    state = state.copyWith(lesson: lesson);
  }
  
  void clear() {
    state = const CurrentJourneyState();
  }
}

final currentJourneyProvider = NotifierProvider<CurrentJourneyNotifier, CurrentJourneyState>(() {
  return CurrentJourneyNotifier();
});
