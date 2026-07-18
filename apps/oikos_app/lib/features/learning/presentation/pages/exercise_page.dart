import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/exercise_provider.dart';
import '../widgets/question_card.dart';
import '../widgets/answer_option.dart';
import 'result_page.dart';

class ExercisePage extends ConsumerWidget {
  const ExercisePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(exerciseProvider);
    final notifier = ref.read(exerciseProvider.notifier);
    
    final currentExercise = state.currentExercise;

    // Check if finished
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (state.isFinished && !state.hasSubmitted) {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            pageBuilder: (context, animation, secondaryAnimation) => const ResultPage(),
            transitionsBuilder: (context, animation, secondaryAnimation, child) {
              return FadeTransition(opacity: animation, child: child);
            },
          ),
        );
      }
    });

    if (currentExercise == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

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
              // Progress Indicator
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
              
              // Question
              AnimatedSwitcher(
                duration: const Duration(milliseconds: 400),
                transitionBuilder: (Widget child, Animation<double> animation) {
                  return FadeTransition(
                    opacity: animation,
                    child: SlideTransition(
                      position: Tween<Offset>(
                        begin: const Offset(0.0, 0.1),
                        end: Offset.zero,
                      ).animate(animation),
                      child: child,
                    ),
                  );
                },
                child: QuestionCard(
                  key: ValueKey(question.id),
                  question: question,
                ),
              ),
              const SizedBox(height: 40),

              // Options
              Expanded(
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 400),
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
              ),

              // Button Area
              Padding(
                padding: const EdgeInsets.only(bottom: 24.0, top: 16.0),
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 200),
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
              ),
            ],
          ),
        ),
      ),
    );
  }
}
