import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/current_journey_provider.dart';
import '../providers/exercise_provider.dart';
import '../widgets/lesson_header.dart';
import 'exercise_page.dart';

class LessonPage extends ConsumerWidget {
  const LessonPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentState = ref.watch(currentJourneyProvider);
    final journey = currentState.journey;
    final chapter = currentState.chapter;
    final lesson = currentState.lesson;

    if (journey == null || chapter == null || lesson == null) {
      return const Scaffold(body: Center(child: Text('Erro ao carregar lição')));
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 16),
              LessonHeader(
                journeyTitle: journey.title,
                chapterTitle: chapter.title,
                lessonTitle: lesson.title,
                progressPercentage: 0.1, // This can be dynamic
              ),
              const SizedBox(height: 48),
              Expanded(
                child: SingleChildScrollView(
                  physics: const BouncingScrollPhysics(),
                  child: Text(
                    lesson.content.text,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w500,
                      color: AppColors.textPrimary,
                      height: 1.6,
                    ),
                  ),
                ),
              ),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 24),
                child: ElevatedButton(
                  onPressed: () {
                    // Start exercises
                    ref.read(exerciseProvider.notifier).startExercises(lesson.exercises);
                    Navigator.of(context).pushReplacement(
                      PageRouteBuilder(
                        pageBuilder: (context, animation, secondaryAnimation) => const ExercisePage(),
                        transitionsBuilder: (context, animation, secondaryAnimation, child) {
                          return FadeTransition(opacity: animation, child: child);
                        },
                      ),
                    );
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 20),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(24),
                    ),
                    elevation: 0,
                  ),
                  child: const Text(
                    'Começar Exercícios',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
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
