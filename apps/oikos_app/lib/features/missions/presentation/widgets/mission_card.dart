import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/mission.dart';
import 'mission_category_badge.dart';

class MissionCard extends StatelessWidget {
  final Mission mission;
  final VoidCallback onComplete;

  const MissionCard({
    super.key,
    required this.mission,
    required this.onComplete,
  });

  @override
  Widget build(BuildContext context) {
    final isCompleted = mission.isCompleted;

    return GestureDetector(
      onTap: isCompleted ? null : onComplete,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: isCompleted ? AppColors.lumoGreen.withOpacity(0.05) : AppColors.surface,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(
            color: isCompleted ? AppColors.lumoGreen.withOpacity(0.3) : Colors.transparent,
            width: 2,
          ),
          boxShadow: [
            if (!isCompleted)
              BoxShadow(
                color: AppColors.textPrimary.withOpacity(0.05),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
          ],
        ),
        child: Row(
          children: [
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 300),
              child: isCompleted
                  ? Container(
                      key: const ValueKey('completed'),
                      width: 48,
                      height: 48,
                      decoration: const BoxDecoration(
                        color: AppColors.lumoGreen,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.check_rounded, color: Colors.white),
                    )
                  : Container(
                      key: const ValueKey('pending'),
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.textSecondary.withOpacity(0.2), width: 2),
                      ),
                    ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  MissionCategoryBadge(category: mission.category),
                  const SizedBox(height: 8),
                  Text(
                    mission.title,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isCompleted ? AppColors.textSecondary : AppColors.textPrimary,
                      decoration: isCompleted ? TextDecoration.lineThrough : null,
                    ),
                  ),
                ],
              ),
            ),
            if (!isCompleted)
              Text(
                '+${mission.xpReward} XP',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: AppColors.achievementOrange,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
