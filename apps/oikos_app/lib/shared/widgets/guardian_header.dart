import 'package:flutter/material.dart';
import '../../app/theme/app_typography.dart';
import '../../app/theme/app_colors.dart';

class GuardianHeader extends StatelessWidget {
  const GuardianHeader({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      decoration: BoxDecoration(
        color: AppColors.lumoGreen.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text('🌱', style: TextStyle(fontSize: 24)),
              const SizedBox(width: 8),
              Text(
                'Lumo',
                style: AppTypography.heading2.copyWith(color: AppColors.lumoGreen),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            'Bom dia!',
            style: AppTypography.bodyLarge.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            'Preparei algo especial para vocês hoje.',
            style: AppTypography.bodyMedium,
          ),
        ],
      ),
    );
  }
}
