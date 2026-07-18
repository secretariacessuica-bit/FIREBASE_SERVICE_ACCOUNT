import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/journey_provider.dart';
import '../providers/current_journey_provider.dart';
import '../widgets/journey_card.dart';
import 'chapter_page.dart';

class JourneyPage extends ConsumerWidget {
  const JourneyPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final journeysAsync = ref.watch(journeyProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
        title: const Text(
          'Jornadas',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontWeight: FontWeight.w800,
          ),
        ),
        centerTitle: true,
      ),
      body: journeysAsync.when(
        data: (journeys) {
          if (journeys.isEmpty) {
            return const Center(
              child: Text(
                'Nenhuma jornada disponível no momento.',
                style: TextStyle(color: AppColors.textSecondary),
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(24),
            itemCount: journeys.length,
            itemBuilder: (context, index) {
              final journey = journeys[index];
              return Hero(
                tag: 'journey_${journey.id}',
                child: Material(
                  color: Colors.transparent,
                  child: JourneyCard(
                    journey: journey,
                    progressPercentage: 0.0, // This would be fetched from ProgressProvider
                    onTap: () {
                      ref.read(currentJourneyProvider.notifier).setJourney(journey);
                      Navigator.of(context).push(
                        PageRouteBuilder(
                          pageBuilder: (context, animation, secondaryAnimation) => const ChapterPage(),
                          transitionsBuilder: (context, animation, secondaryAnimation, child) {
                            return FadeTransition(opacity: animation, child: child);
                          },
                        ),
                      );
                    },
                  ),
                ),
              );
            },
          );
        },
        loading: () => const Center(child: CircularProgressIndicator(color: AppColors.primary)),
        error: (err, stack) => Center(child: Text('Erro ao carregar jornadas: $err')),
      ),
    );
  }
}
