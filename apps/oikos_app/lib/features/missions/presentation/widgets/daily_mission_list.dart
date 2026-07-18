import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/daily_mission_provider.dart';
import 'mission_card.dart';
import '../../../learning/presentation/providers/missions_provider.dart';
import '../../domain/entities/mission.dart';
import '../../domain/entities/mission_category.dart';

class DailyMissionList extends ConsumerWidget {
  final String userId;

  const DailyMissionList({super.key, required this.userId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncMissions = ref.watch(missionsProvider(userId));

    return asyncMissions.when(
      data: (missions) {
        if (missions.isEmpty) return const SizedBox.shrink();
        
        // Simulating completed count for now since getDailyPacing doesn't track completion state
        final completed = 0;
        final total = missions.length;

        return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Missões de Hoje',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
            ),
            Text(
              '$completed de $total concluídas',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        ...missions.map((backendMission) {
          final mission = Mission(
            id: backendMission.id,
            title: backendMission.title,
            description: backendMission.type,
            category: MissionCategory.aprender,
            xpReward: backendMission.xpReward,
          );
          return MissionCard(
            key: ValueKey(mission.id),
            mission: mission,
            onComplete: () {
              // In a real app, this would trigger ProgressEngine via usecase
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Você completou uma missão! +${mission.xpReward} XP'),
                  backgroundColor: AppColors.lumoGreen,
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  margin: const EdgeInsets.only(bottom: 24, left: 24, right: 24),
                ),
              );
            },
          );
        }),
      ],
    );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, s) => Center(child: Text('Falha ao carregar missões: $e')),
    );
  }
}
