import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/current_journey_provider.dart';
import '../widgets/chapter_card.dart';
import 'lesson_page.dart';

class ChapterPage extends ConsumerWidget {
  const ChapterPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentState = ref.watch(currentJourneyProvider);
    final journey = currentState.journey;

    if (journey == null) {
      return const Scaffold(body: Center(child: Text('Nenhuma jornada selecionada')));
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
      ),
      body: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16),
              child: Hero(
                tag: 'journey_${journey.id}',
                child: Material(
                  color: Colors.transparent,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        journey.title,
                        style: const TextStyle(
                          fontSize: 36,
                          fontWeight: FontWeight.w900,
                          color: AppColors.textPrimary,
                          height: 1.2,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        journey.description,
                        style: const TextStyle(
                          fontSize: 16,
                          color: AppColors.textSecondary,
                          height: 1.4,
                        ),
                      ),
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  final chapter = journey.chapters[index];
                  // Simple logic to lock chapters beyond the first for demonstration
                  final isLocked = index > 0;
                  return ChapterCard(
                    chapter: chapter,
                    progressPercentage: index == 0 ? 0.3 : 0.0,
                    isLocked: isLocked,
                    onTap: () {
                      ref.read(currentJourneyProvider.notifier).setChapter(chapter);
                      ref.read(currentJourneyProvider.notifier).setLesson(chapter.lessons.first);
                      
                      Navigator.of(context).push(
                        PageRouteBuilder(
                          pageBuilder: (context, animation, secondaryAnimation) => const LessonPage(),
                          transitionsBuilder: (context, animation, secondaryAnimation, child) {
                            const begin = Offset(0.0, 0.1);
                            const end = Offset.zero;
                            const curve = Curves.easeOutCubic;
                            var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
                            return SlideTransition(
                              position: animation.drive(tween),
                              child: FadeTransition(opacity: animation, child: child),
                            );
                          },
                        ),
                      );
                    },
                  );
                },
                childCount: journey.chapters.length,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
