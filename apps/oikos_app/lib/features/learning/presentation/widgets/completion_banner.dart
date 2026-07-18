import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';

class CompletionBanner extends StatelessWidget {
  final int correctAnswers;
  final int totalQuestions;
  final int earnedXp;

  const CompletionBanner({
    super.key,
    required this.correctAnswers,
    required this.totalQuestions,
    required this.earnedXp,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: AppColors.primary.withOpacity(0.05),
        borderRadius: BorderRadius.circular(32),
      ),
      child: Column(
        children: [
          const Text(
            '🎉',
            style: TextStyle(fontSize: 64),
          ),
          const SizedBox(height: 24),
          const Text(
            'Lição concluída!',
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Você concluiu esta etapa.\nContinue assim.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 16,
              color: AppColors.textSecondary,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: AppColors.textPrimary.withOpacity(0.05),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.star_rounded, color: AppColors.achievementOrange),
                const SizedBox(width: 8),
                Text(
                  '+$earnedXp XP',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.achievementOrange,
                  ),
                ),
                const SizedBox(width: 24),
                const Icon(Icons.check_circle_rounded, color: AppColors.lumoGreen),
                const SizedBox(width: 8),
                Text(
                  '$correctAnswers/$totalQuestions corretas',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppColors.lumoGreen,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
