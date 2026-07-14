import 'package:flutter/material.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../../shared/widgets/action_card.dart';

class HomePage extends StatelessWidget {
  final String userName;
  
  const HomePage({super.key, required this.userName});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 16),
              Text(
                'Bom dia $userName.',
                style: AppTypography.heading1,
              ),
              const SizedBox(height: 8),
              Text(
                'Hoje você possui:',
                style: AppTypography.bodyLarge,
              ),
              const SizedBox(height: 32),
              const ActionCard(
                title: 'Continue sua jornada',
                emoji: '📖',
                backgroundColor: AppColors.primary,
              ),
              const ActionCard(
                title: 'Conversar com Lumo',
                emoji: '💬',
                backgroundColor: AppColors.lumoGreen,
              ),
              const ActionCard(
                title: 'Missão da Família',
                emoji: '🏆',
                backgroundColor: AppColors.achievementOrange,
              ),
              const ActionCard(
                title: 'Praticar Inglês',
                emoji: '🌍',
                backgroundColor: AppColors.learningBlue,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
