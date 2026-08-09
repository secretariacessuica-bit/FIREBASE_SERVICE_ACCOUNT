import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive/hive.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/answer.dart';
import '../../../companion/domain/lumo_variant.dart';

enum MissionStage {
  introduction,
  awaitingAction,
  showingHelp,
  completed,
}

class ExerciseState {
  final List<Exercise> exercises;
  final int currentIndex;
  final int correctAnswers;
  final int wrongAnswers;
  final Answer? selectedAnswer;
  final bool hasSubmitted;

  // ── Mission State Extensions ──────────────────────────────────────────────
  final MissionStage stage;
  final int attempts;
  final String? selectedOptionId;
  final LumoVariant lumoEmotion;
  final String? feedbackMessage;

  const ExerciseState({
    this.exercises = const [],
    this.currentIndex = 0,
    this.correctAnswers = 0,
    this.wrongAnswers = 0,
    this.selectedAnswer,
    this.hasSubmitted = false,
    this.stage = MissionStage.introduction,
    this.attempts = 0,
    this.selectedOptionId,
    this.lumoEmotion = LumoVariant.listening,
    this.feedbackMessage,
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
    MissionStage? stage,
    int? attempts,
    String? selectedOptionId,
    LumoVariant? lumoEmotion,
    String? feedbackMessage,
  }) {
    return ExerciseState(
      exercises: exercises ?? this.exercises,
      currentIndex: currentIndex ?? this.currentIndex,
      correctAnswers: correctAnswers ?? this.correctAnswers,
      wrongAnswers: wrongAnswers ?? this.wrongAnswers,
      selectedAnswer: selectedAnswer ?? this.selectedAnswer,
      hasSubmitted: hasSubmitted ?? this.hasSubmitted,
      stage: stage ?? this.stage,
      attempts: attempts ?? this.attempts,
      selectedOptionId: selectedOptionId ?? this.selectedOptionId,
      lumoEmotion: lumoEmotion ?? this.lumoEmotion,
      feedbackMessage: feedbackMessage ?? this.feedbackMessage,
    );
  }
}

class ExerciseNotifier extends Notifier<ExerciseState> {
  @override
  ExerciseState build() {
    return const ExerciseState();
  }

  void startExercises(List<Exercise> exercises) {
    if (exercises.isNotEmpty && exercises.first.mission != null) {
      // É uma missão rica do Oikos
      final mission = exercises.first.mission!;
      state = ExerciseState(
        exercises: exercises,
        stage: MissionStage.introduction,
        lumoEmotion: LumoVariant.listening,
        feedbackMessage: mission.contextDescription,
      );
    } else {
      // Exercício padrão
      state = ExerciseState(exercises: exercises);
    }
  }

  void startAwaitingAction() {
    state = state.copyWith(
      stage: MissionStage.awaitingAction,
      lumoEmotion: LumoVariant.idle,
      feedbackMessage: state.currentExercise?.mission?.promptPhrase,
    );
  }

  void selectAnswer(Answer answer) {
    if (state.hasSubmitted || state.stage == MissionStage.completed) return;
    state = state.copyWith(selectedAnswer: answer);
  }

  // ── Ações da Missão Contextual ──────────────────────────────────────────────
  void selectMissionOption(String optionId) {
    if (state.stage == MissionStage.completed) return;
    state = state.copyWith(selectedOptionId: optionId);
  }

  Future<void> submitMissionAnswer() {
    final mission = state.currentExercise?.mission;
    if (mission == null || state.selectedOptionId == null) return Future.value();

    final selectedOption = mission.options!.firstWhere((o) => o.id == state.selectedOptionId);
    
    if (selectedOption.isCorrect) {
      // Alternativa Correta
      state = state.copyWith(
        stage: MissionStage.completed,
        lumoEmotion: LumoVariant.proud,
        feedbackMessage: 'Correto! ${mission.practicedCompetency}',
      );
    } else {
      // Alternativa Incorreta
      final newAttempts = state.attempts + 1;
      state = state.copyWith(
        attempts: newAttempts,
        stage: MissionStage.showingHelp,
        lumoEmotion: LumoVariant.confused,
        feedbackMessage: mission.helpExplanation,
      );
    }
    return Future.value();
  }

  void retryMission() {
    final mission = state.currentExercise?.mission;
    state = state.copyWith(
      stage: MissionStage.awaitingAction,
      lumoEmotion: LumoVariant.idle,
      selectedOptionId: null,
      feedbackMessage: mission?.promptPhrase,
    );
  }

  Future<void> concludeMission() async {
    final mission = state.currentExercise?.mission;
    if (mission == null) return;

    // Gravar localmente na persistência utilizando Hive
    final box = await Hive.openBox<Map>('missionProgressBox');
    await box.put(mission.id, {
      'missionId': mission.id,
      'completed': true,
      'attempts': state.attempts,
      'practicedCompetency': mission.practicedCompetency,
      'completedAt': DateTime.now().toIso8601String(),
    });

    // Avançar estado de forma limpa
    state = state.copyWith(
      currentIndex: state.currentIndex + 1,
      selectedOptionId: null,
    );
  }

  // ── Métodos legados para compatibilidade de Exercícios ──────────────────────
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
