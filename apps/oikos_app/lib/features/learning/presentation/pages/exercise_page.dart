import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../features/companion/presentation/widgets/lumo_renderer.dart';
import '../../domain/entities/exercise.dart';
import '../providers/exercise_provider.dart';
import '../widgets/question_card.dart';
import '../widgets/answer_option.dart';
import '../../../companion/domain/lumo_variant.dart';

class ExercisePage extends ConsumerWidget {
  const ExercisePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(exerciseProvider);
    final notifier = ref.read(exerciseProvider.notifier);
    
    final currentExercise = state.currentExercise;

    // Redireciona para o final se a lição/missão terminou
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (state.isFinished) {
        Navigator.of(context).pop(); // Volta para a LivingScenePage (ou tela de origem)
      }
    });

    if (currentExercise == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    // Se for uma missão rica do Oikos
    if (currentExercise.mission != null) {
      return _buildMissionView(context, ref, state, notifier, currentExercise.mission!);
    }

    // Caso contrário, usa a visualização de exercício tradicional
    return _buildLegacyExerciseView(context, ref, state, notifier, currentExercise);
  }

  // ── Cenário de Missão Rica (Lausanne) ──────────────────────────────────────
  Widget _buildMissionView(
    BuildContext context,
    WidgetRef ref,
    ExerciseState state,
    ExerciseNotifier notifier,
    dynamic mission, // Mission classe
  ) {
    return Scaffold(
      backgroundColor: const Color(0xFFFFF8F0),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 20),
              // Cabeçalho / Título da Missão
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textPrimary),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                  Text(
                    mission.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(width: 48), // espaçador simétrico
                ],
              ),
              const SizedBox(height: 16),

              // Mascote Lumo e Balão de fala reativo
              Center(
                child: Column(
                  children: [
                    _buildSpeechBubble(
                      state.feedbackMessage ?? (state.stage == MissionStage.introduction ? "Vamos ouvir quanto ficou a compra?" : mission.promptPhrase),
                      state.stage == MissionStage.completed,
                    ),
                    const SizedBox(height: 16),
                    LumoRenderer(
                      variant: state.lumoEmotion,
                      size: 140,
                      loop: state.lumoEmotion != LumoVariant.celebrating &&
                            state.lumoEmotion != LumoVariant.proud &&
                            state.lumoEmotion != LumoVariant.happy,
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Corpo da Missão / Contexto / Pergunta
              if (state.stage == MissionStage.introduction) ...[
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.04),
                          blurRadius: 16,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          mission.contextDescription,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 17,
                            color: AppColors.textPrimary,
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(bottom: 24.0, top: 16.0),
                  child: ElevatedButton(
                    onPressed: () => notifier.startAwaitingAction(),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      padding: const EdgeInsets.symmetric(vertical: 20),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                      elevation: 0,
                    ),
                    child: const Text(
                      'Ouvir Atendente',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                  ),
                ),
              ] else ...[
                // Pergunta visual e alternativas
                const Text(
                  'Quanto você precisa pagar?',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 20),
                Expanded(
                  child: ListView.builder(
                    physics: const BouncingScrollPhysics(),
                    itemCount: mission.options.length,
                    itemBuilder: (context, index) {
                      final option = mission.options[index];
                      final isSelected = state.selectedOptionId == option.id;
                      final isCompleted = state.stage == MissionStage.completed;

                      return GestureDetector(
                        onTap: isCompleted ? null : () => notifier.selectMissionOption(option.id),
                        child: Container(
                          margin: const EdgeInsets.symmetric(vertical: 8),
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: isSelected ? AppColors.primary.withOpacity(0.08) : Colors.white,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: isSelected ? AppColors.primary : Colors.black.withOpacity(0.08),
                              width: 2,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 24,
                                height: 24,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: isSelected ? AppColors.primary : Colors.grey,
                                    width: 2,
                                  ),
                                  color: isSelected ? AppColors.primary : Colors.transparent,
                                ),
                                child: isSelected
                                    ? const Icon(Icons.check, size: 16, color: Colors.white)
                                    : null,
                              ),
                              const SizedBox(width: 16),
                              Text(
                                option.label,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),

                // Área de feedback/botões
                Padding(
                  padding: const EdgeInsets.only(bottom: 24.0, top: 16.0),
                  child: _buildActionButtonArea(context, state, notifier),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSpeechBubble(String message, bool isSuccess) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      constraints: const BoxConstraints(maxWidth: 320),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(24),
          topRight: Radius.circular(24),
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(4),
        ),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 15, offset: const Offset(0, 5)),
        ],
      ),
      child: isSuccess
          ? Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: const [
                Text(
                  'Ótimo.',
                  style: TextStyle(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                SizedBox(height: 6),
                Text(
                  'Quando ouvir nonante-cinq, você já vai reconhecer esse valor no caixa.',
                  style: TextStyle(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                    height: 1.4,
                  ),
                ),
              ],
            )
          : Text(
              message,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 15,
                height: 1.4,
              ),
            ),
    );
  }

  Widget _buildActionButtonArea(BuildContext context, ExerciseState state, ExerciseNotifier notifier) {
    if (state.stage == MissionStage.completed) {
      return ElevatedButton(
        onPressed: () => notifier.concludeMission(),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.green,
          padding: const EdgeInsets.symmetric(vertical: 20),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          elevation: 0,
        ),
        child: const Text(
          'Concluir Missão',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
        ),
      );
    }

    if (state.stage == MissionStage.showingHelp) {
      return ElevatedButton(
        onPressed: () => notifier.retryMission(),
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          padding: const EdgeInsets.symmetric(vertical: 20),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          elevation: 0,
        ),
        child: const Text(
          'Tentar Novamente',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
        ),
      );
    }

    return ElevatedButton(
      onPressed: state.selectedOptionId == null ? null : () => notifier.submitMissionAnswer(),
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.textPrimary,
        disabledBackgroundColor: AppColors.textPrimary.withOpacity(0.1),
        padding: const EdgeInsets.symmetric(vertical: 20),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        elevation: 0,
      ),
      child: Text(
        'Confirmar Resposta',
        style: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: state.selectedOptionId == null ? AppColors.textSecondary : Colors.white,
        ),
      ),
    );
  }

  // ── Cenário de Exercício Tradicional (Mapeamento/Fallback Legado) ─────────
  Widget _buildLegacyExerciseView(
    BuildContext context,
    WidgetRef ref,
    ExerciseState state,
    ExerciseNotifier notifier,
    Exercise currentExercise,
  ) {
    final question = currentExercise.question;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 24),
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: LinearProgressIndicator(
                  value: (state.currentIndex) / state.exercises.length,
                  minHeight: 8,
                  backgroundColor: AppColors.textPrimary.withOpacity(0.05),
                  valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                ),
              ),
              const SizedBox(height: 48),
              
              QuestionCard(
                key: ValueKey(question.id),
                question: question,
              ),
              const SizedBox(height: 40),

              Expanded(
                child: ListView(
                  key: ValueKey(question.id),
                  physics: const BouncingScrollPhysics(),
                  children: question.options.map((answer) {
                    final isSelected = state.selectedAnswer?.id == answer.id;
                    return AnswerOption(
                      answer: answer,
                      isSelected: isSelected,
                      hasSubmitted: state.hasSubmitted,
                      onTap: () => notifier.selectAnswer(answer),
                    );
                  }).toList(),
                ),
              ),

              Padding(
                padding: const EdgeInsets.only(bottom: 24.0, top: 16.0),
                child: state.hasSubmitted
                    ? ElevatedButton(
                        key: const ValueKey('continue'),
                        onPressed: () => notifier.nextExercise(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          padding: const EdgeInsets.symmetric(vertical: 20),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                          elevation: 0,
                        ),
                        child: const Text('Avançar', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                      )
                    : ElevatedButton(
                        key: const ValueKey('submit'),
                        onPressed: state.selectedAnswer == null ? null : () => notifier.submitAnswer(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.textPrimary,
                          disabledBackgroundColor: AppColors.textPrimary.withOpacity(0.1),
                          padding: const EdgeInsets.symmetric(vertical: 20),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                          elevation: 0,
                        ),
                        child: Text('Confirmar', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: state.selectedAnswer == null ? AppColors.textSecondary : Colors.white)),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
