import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/chapter.dart';
import 'progress_ring.dart';

class ChapterCard extends StatelessWidget {
  final Chapter chapter;
  final double progressPercentage;
  final bool isLocked;
  final VoidCallback? onTap;

  const ChapterCard({
    super.key,
    required this.chapter,
    required this.progressPercentage,
    this.isLocked = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: isLocked ? null : onTap,
      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 300),
        opacity: isLocked ? 0.6 : 1.0,
        child: Container(
          margin: const EdgeInsets.only(bottom: 20),
          decoration: BoxDecoration(
            color: isLocked ? AppColors.background : AppColors.surface,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: isLocked ? Colors.transparent : AppColors.primary.withOpacity(0.1),
              width: 1.5,
            ),
            boxShadow: isLocked
                ? []
                : [
                    BoxShadow(
                      color: AppColors.textPrimary.withOpacity(0.05),
                      blurRadius: 15,
                      offset: const Offset(0, 8),
                    ),
                  ],
          ),
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Row(
              children: [
                if (!isLocked)
                  ProgressRing(
                    percentage: progressPercentage,
                    size: 48,
                    strokeWidth: 4,
                  )
                else
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppColors.textSecondary.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.lock_rounded,
                      color: AppColors.textSecondary,
                    ),
                  ),
                const SizedBox(width: 20),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        chapter.title,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: isLocked ? AppColors.textSecondary : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        chapter.description,
                        style: const TextStyle(
                          fontSize: 14,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '${chapter.lessons.length} lições • ~${chapter.lessons.length * 5} min',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: isLocked ? AppColors.textSecondary : AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ),
                if (!isLocked)
                  const Icon(
                    Icons.chevron_right_rounded,
                    color: AppColors.primary,
                    size: 32,
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
