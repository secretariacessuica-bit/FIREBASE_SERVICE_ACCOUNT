import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/family_activity.dart';

class ActivityTile extends StatelessWidget {
  final FamilyActivity activity;

  const ActivityTile({
    super.key,
    required this.activity,
  });

  String _getTimeLabel(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);
    if (difference.inDays == 0 && now.day == date.day) {
      return 'Hoje';
    } else if (difference.inDays == 1 || (difference.inDays == 0 && now.day != date.day)) {
      return 'Ontem';
    } else {
      return 'Esta semana';
    }
  }

  @override
  Widget build(BuildContext context) {
    final memberName = activity.memberId;
    final timeLabel = _getTimeLabel(activity.date);

    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: AppColors.lumoGreen.withOpacity(0.15),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                memberName.substring(0, 1).toUpperCase(),
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: AppColors.lumoGreen,
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                RichText(
                  text: TextSpan(
                    style: const TextStyle(
                      fontSize: 16,
                      color: AppColors.textPrimary,
                      height: 1.4,
                    ),
                    children: [
                      TextSpan(
                        text: '$memberName ',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      TextSpan(
                        text: _getDescription(activity),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  timeLabel,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _getDescription(FamilyActivity activity) {
    if (activity.eventType == 'lesson_completed') {
      final subject = activity.metadata?['subject'] ?? 'uma lição';
      return 'terminou $subject.';
    } else if (activity.eventType == 'mission_completed') {
      final mission = activity.metadata?['mission'] ?? 'uma missão';
      if (mission == 'Leitura') return 'leu 15 minutos.';
      if (mission == 'Gentileza') return 'ajudou alguém.';
      return 'concluiu $mission.';
    }
    return 'fez uma atividade.';
  }
}
