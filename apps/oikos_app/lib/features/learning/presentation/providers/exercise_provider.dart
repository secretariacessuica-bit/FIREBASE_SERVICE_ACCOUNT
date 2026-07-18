import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/answer.dart';

class ExerciseState {
  final List<Exercise> exercises;
  final int currentIndex;
  final int correctAnswers;
  final int wrongAnswers;
  final Answer? selectedAnswer;
  final bool hasSubmitted;

  const ExerciseState({
    this.exercises = const [],
    this.currentIndex = 0,
    this.correctAnswers = 0,
    this.wrongAnswers = 0,
    this.selectedAnswer,
    this.hasSubmitted = false,
  });

  Exercise? get currentExercise {
    if (currentIndex >= 0 && currentIndex < exercises.length) {
      return exercises[currentIndex];
    }
    return null;
  }
  
  bool get isFinished => currentIndex >= exercises.length && exercises.isNotEmpty;

  ExerciseState copyWith({
    List<Exercise>? exercises,
    int? currentIndex,
    int? correctAnswers,
    int? wrongAnswers,
    Answer? selectedAnswer,
    bool? hasSubmitted,
  }) {
    return ExerciseState(
      exercises: exercises ?? this.exercises,
      currentIndex: currentIndex ?? this.currentIndex,
      correctAnswers: correctAnswers ?? this.correctAnswers,
      wrongAnswers: wrongAnswers ?? this.wrongAnswers,
      selectedAnswer: selectedAnswer ?? this.selectedAnswer,
      hasSubmitted: hasSubmitted ?? this.hasSubmitted,
    );
  }
}

class ExerciseNotifier extends Notifier<ExerciseState> {
  @override
  ExerciseState build() {
    return const ExerciseState();
  }

  void startExercises(List<Exercise> exercises) {
    state = ExerciseState(exercises: exercises);
  }

  void selectAnswer(Answer answer) {
    if (state.hasSubmitted) return;
    state = state.copyWith(selectedAnswer: answer);
  }

  void submitAnswer() {
    if (state.selectedAnswer == null || state.hasSubmitted) return;
    
    final isCorrect = state.selectedAnswer!.isCorrect;
    state = state.copyWith(
      hasSubmitted: true,
      correctAnswers: state.correctAnswers + (isCorrect ? 1 : 0),
      wrongAnswers: state.wrongAnswers + (isCorrect ? 0 : 1),
    );
  }

  void nextExercise() {
    if (!state.hasSubmitted) return;
    state = state.copyWith(
      currentIndex: state.currentIndex + 1,
      selectedAnswer: null,
      hasSubmitted: false,
    );
  }
}

final exerciseProvider = NotifierProvider<ExerciseNotifier, ExerciseState>(() {
  return ExerciseNotifier();
});
