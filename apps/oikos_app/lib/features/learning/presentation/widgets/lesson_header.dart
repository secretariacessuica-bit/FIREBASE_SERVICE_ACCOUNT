import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';

class LessonHeader extends StatelessWidget {
  final String journeyTitle;
  final String chapterTitle;
  final String lessonTitle;
  final double progressPercentage;

  const LessonHeader({
    super.key,
    required this.journeyTitle,
    required this.chapterTitle,
    required this.lessonTitle,
    required this.progressPercentage,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '$journeyTitle • $chapterTitle',
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: AppColors.primary,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          lessonTitle,
          style: const TextStyle(
            fontSize: 32,
            fontWeight: FontWeight.w900,
            color: AppColors.textPrimary,
            height: 1.2,
          ),
        ),
        const SizedBox(height: 24),
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: LinearProgressIndicator(
            value: progressPercentage,
            minHeight: 8,
            backgroundColor: AppColors.primary.withOpacity(0.1),
            valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
          ),
        ),
      ],
    );
  }
}
