import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../../shared/widgets/action_card.dart';
import '../../features/missions/presentation/widgets/daily_mission_list.dart';
import '../../features/family/presentation/widgets/family_progress_card.dart';
import '../../features/family/presentation/widgets/family_timeline_view.dart';
import '../../features/family/presentation/providers/family_provider.dart';
import '../../features/profiles/presentation/pages/profile_page.dart';
import '../../features/storybook/presentation/pages/storybook_page.dart';
import '../../features/memories/presentation/pages/memories_page.dart';
import '../../features/companion/presentation/pages/lumos_corner_page.dart';
import '../../features/guardian/presentation/pages/pet_battle_arena_page.dart';
import '../../features/profiles/domain/entities/profile_theme.dart';
import '../../shared/widgets/glass_card.dart';
import 'presentation/pages/living_scene_page.dart';

const bool useLivingSceneHome = true;

class HomePage extends StatelessWidget {
  final String userId;
  final String userName;
  
  const HomePage({super.key, required this.userId, required this.userName});

  @override
  Widget build(BuildContext context) {
    if (useLivingSceneHome) {
      return LivingScenePage(userId: userId, userName: userName);
    }
    return LegacyDashboardPage(userId: userId, userName: userName);
  }
}

class LegacyDashboardPage extends ConsumerWidget {
  final String userId;
  final String userName;
  
  const LegacyDashboardPage({super.key, required this.userId, required this.userName});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final greeting = "Olá";
    final motivation = "Vamos lá"; 

    return Scaffold(
      body: Stack(
        children: [
          // 1. DYNAMIC GRADIENT BACKGROUND
          Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  Color(0xFFE0E7FF), // Soft Indigo
                  Color(0xFFF3E8FF), // Soft Purple
                  Color(0xFFFDF4FF), // Soft Pink
                  Color(0xFFF0FDF4), // Soft Green
                ],
                stops: [0.0, 0.4, 0.7, 1.0],
              ),
            ),
          ),
          
          // 2. BACKGROUND BUBBLES
          Positioned(
            top: -50,
            left: -50,
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withOpacity(0.3),
              ),
            ).animate(onPlay: (controller) => controller.repeat(reverse: true))
             .scaleXY(begin: 1.0, end: 1.1, duration: 4.seconds, curve: Curves.easeInOut),
          ),
          Positioned(
            top: 300,
            right: -100,
            child: Container(
              width: 350,
              height: 350,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withOpacity(0.2),
              ),
            ).animate(onPlay: (controller) => controller.repeat(reverse: true))
             .moveY(begin: 0, end: -30, duration: 6.seconds, curve: Curves.easeInOut),
          ),
          
          // 3. MAIN CONTENT
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 16),
                  Text(
                    '$greeting $userName.',
                    style: AppTypography.heading1.copyWith(
                      color: AppColors.primary,
                      fontSize: 32,
                    ),
                  ).animate().fadeIn(duration: 500.ms).slideX(begin: -0.2),
                  const SizedBox(height: 8),
                  
                  // Lumo's Thought (GlassCard)
                  GestureDetector(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const LumosCornerPage()),
                      );
                    },
                    child: GlassCard(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          const Text('🌱', style: TextStyle(fontSize: 24)),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Text(
                              'Hoje lembrei da primeira semana da família. Algumas histórias merecem ser guardadas para sempre.',
                              style: TextStyle(
                                fontSize: 16,
                                color: AppColors.textPrimary,
                                fontWeight: FontWeight.w500,
                                height: 1.4,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1),
                  const SizedBox(height: 32),
                  
                  Text(
                    'Hoje você possui:',
                    style: AppTypography.bodyLarge.copyWith(fontWeight: FontWeight.w800, color: AppColors.primary),
                  ).animate().fadeIn(delay: 300.ms),
                  const SizedBox(height: 16),
                  
                  // Actions
                  const ActionCard(
                    title: 'Conversar com Lumo',
                    emoji: '💬',
                    backgroundColor: AppColors.lumoGreen,
                  ),
                  GestureDetector(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const ProfilePage()),
                      );
                    },
                    child: const ActionCard(
                      title: 'Meu Espaço',
                      emoji: '👤',
                      backgroundColor: AppColors.primary,
                    ),
                  ),
                  const SizedBox(height: 8),

                  // Nossa História e Tesouros
                  Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => const StorybookPage()),
                            );
                          },
                          child: GlassCard(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Text('📖', style: TextStyle(fontSize: 20)),
                                const SizedBox(width: 8),
                                const Text(
                                  'História',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ).animate().fadeIn(delay: 400.ms).scaleXY(begin: 0.8),
                      const SizedBox(width: 16),
                      Expanded(
                        child: GestureDetector(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => const MemoriesPage()),
                            );
                          },
                          child: GlassCard(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Text('💎', style: TextStyle(fontSize: 20)),
                                const SizedBox(width: 8),
                                const Text(
                                  'Tesouros',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ).animate().fadeIn(delay: 500.ms).scaleXY(begin: 0.8),
                    ],
                  ),
                  const SizedBox(height: 32),
                  
                  // Dynamic Journey Card
                  Consumer(
                    builder: (context, ref, child) {
                      return GestureDetector(
                        onTap: () {
                          Navigator.pushNamed(context, '/learning');
                        },
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 16),
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)], // Indigo to Purple
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFF6366F1).withOpacity(0.4),
                                blurRadius: 20,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      '📚 Matemática',
                                      style: TextStyle(
                                        fontSize: 22,
                                        fontWeight: FontWeight.w900,
                                        color: Colors.white,
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      'Capítulo 1\nLição 2',
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w600,
                                        color: Colors.white.withOpacity(0.9),
                                        height: 1.4,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                      decoration: BoxDecoration(
                                        color: Colors.white.withOpacity(0.25),
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      child: const Text(
                                        'Continue de onde você parou',
                                        style: TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w800,
                                          color: Colors.white,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Stack(
                                alignment: Alignment.center,
                                children: [
                                  SizedBox(
                                    width: 70,
                                    height: 70,
                                    child: CircularProgressIndicator(
                                      value: 0.38,
                                      strokeWidth: 8,
                                      backgroundColor: Colors.white.withOpacity(0.2),
                                      valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                                      strokeCap: StrokeCap.round,
                                    ),
                                  ),
                                  const Text(
                                    '38%',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w900,
                                      color: Colors.white,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ).animate().fadeIn(delay: 600.ms).slideY(begin: 0.1);
                    },
                  ),
                  const SizedBox(height: 16),
                  
                  // Arena Card
                  GestureDetector(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const PetBattleArenaPage(
                            theme: ProfileTheme.playful, // Set to playful to show visual mode!
                            petLevel: 1, // Example pet level
                          ),
                        ),
                      );
                    },
                    child: const ActionCard(
                      title: 'Entrar na Arena',
                      emoji: '⚔️',
                      backgroundColor: Colors.deepPurpleAccent,
                    ),
                  ),
                  const SizedBox(height: 32),
                  
                  // Daily Missions
                  DailyMissionList(userId: userId).animate().fadeIn(delay: 800.ms),
                  const SizedBox(height: 32),
                  
                  // Family Progress
                  Consumer(
                    builder: (context, ref, child) {
                      final familyState = ref.watch(familyProvider);
                      return FamilyProgressCard(
                        daysTogether: familyState.daysLearningTogether,
                        todaysJourneys: 3, 
                        todaysMissions: 2,
                        todaysReadings: 1,
                      );
                    },
                  ).animate().fadeIn(delay: 900.ms),
                  const SizedBox(height: 32),

                  // Family Timeline
                  const FamilyTimelineView().animate().fadeIn(delay: 1000.ms),
                  const SizedBox(height: 32),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}



