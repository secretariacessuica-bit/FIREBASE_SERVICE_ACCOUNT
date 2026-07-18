import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/family_provider.dart';
import 'activity_tile.dart';
import 'family_moment_card.dart';

class FamilyTimelineView extends ConsumerWidget {
  const FamilyTimelineView({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(familyProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Momentos de Hoje',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 24),
        if (state.moments.isNotEmpty)
          ...state.moments.map((m) => FamilyMomentCard(key: ValueKey(m.id), moment: m)),
        
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: AppColors.textPrimary.withOpacity(0.03),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            children: state.activities.map((a) => ActivityTile(key: ValueKey(a.id), activity: a)).toList(),
          ),
        ),
      ],
    );
  }
}
