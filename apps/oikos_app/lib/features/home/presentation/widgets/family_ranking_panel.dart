import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../../domain/entities/family_member.dart';
import '../../../domain/entities/age_experience_mode.dart';
import '../providers/xp_provider.dart';
import '../../domain/xp_repository.dart';
import '../../../../app/theme/app_colors.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Painel de Ranking Familiar
// ─────────────────────────────────────────────────────────────────────────────
class FamilyRankingPanel extends ConsumerWidget {
  final List<FamilyMember> members;
  final String currentUserId;

  const FamilyRankingPanel({
    super.key,
    required this.members,
    required this.currentUserId,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Coleta XP de todos e ordena
    final ranked = members.map((m) {
      final xp = ref.watch(xpProvider(m.id)).xp;
      return _RankedMember(member: m, xp: xp);
    }).toList()
      ..sort((a, b) => b.xp.compareTo(a.xp));

    final maxXp = ranked.isEmpty ? 1 : (ranked.first.xp > 0 ? ranked.first.xp : 1);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.92),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.07),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              const Text('🏆', style: TextStyle(fontSize: 18)),
              const SizedBox(width: 8),
              const Text(
                'Família em XP',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w900,
                  color: Color(0xFF1F2937),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ...ranked.asMap().entries.map((e) {
            final idx = e.key;
            final r = e.value;
            final isCurrentUser = r.member.id == currentUserId;
            final barWidth = r.xp / maxXp;

            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _RankRow(
                rank: idx + 1,
                member: r.member,
                xp: r.xp,
                barFraction: barWidth,
                isHighlighted: isCurrentUser,
              ),
            ).animate(delay: (idx * 80).ms).fadeIn(duration: 350.ms).slideX(begin: -0.1);
          }),
        ],
      ),
    );
  }
}

class _RankedMember {
  final FamilyMember member;
  final int xp;
  _RankedMember({required this.member, required this.xp});
}

class _RankRow extends ConsumerWidget {
  final int rank;
  final FamilyMember member;
  final int xp;
  final double barFraction;
  final bool isHighlighted;

  const _RankRow({
    required this.rank,
    required this.member,
    required this.xp,
    required this.barFraction,
    required this.isHighlighted,
  });

  String get _medal {
    switch (rank) {
      case 1: return '🥇';
      case 2: return '🥈';
      case 3: return '🥉';
      default: return '$rank.';
    }
  }

  String get _companionEmoji {
    switch (member.experienceMode) {
      case AgeExperienceMode.earlyChildhood: return '🐣';
      case AgeExperienceMode.explorer:       return '🦊';
      case AgeExperienceMode.teen:           return '🐺';
      case AgeExperienceMode.youngMentor:    return '🦉';
      case AgeExperienceMode.adult:          return '🦌';
      case AgeExperienceMode.senior:         return '🐢';
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final level = XpRepository.levelFromXp(xp);

    return AnimatedContainer(
      duration: 300.ms,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: isHighlighted
            ? AppColors.achievementOrange.withOpacity(0.08)
            : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isHighlighted
              ? AppColors.achievementOrange.withOpacity(0.4)
              : Colors.transparent,
          width: 1.5,
        ),
      ),
      child: Row(
        children: [
          // Medalha / posição
          SizedBox(
            width: 28,
            child: Text(
              _medal,
              style: const TextStyle(fontSize: 16),
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(width: 6),
          // Companion emoji
          Text(_companionEmoji, style: const TextStyle(fontSize: 20)),
          const SizedBox(width: 8),
          // Nome + barra
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      member.name,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: isHighlighted ? FontWeight.w800 : FontWeight.w600,
                        color: isHighlighted
                            ? AppColors.achievementOrange
                            : const Color(0xFF374151),
                      ),
                    ),
                    Text(
                      'Nv.$level · $xp XP',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: isHighlighted
                            ? AppColors.achievementOrange
                            : const Color(0xFF9CA3AF),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                ClipRRect(
                  borderRadius: BorderRadius.circular(3),
                  child: TweenAnimationBuilder<double>(
                    tween: Tween(begin: 0.0, end: barFraction.clamp(0.0, 1.0)),
                    duration: 700.ms,
                    curve: Curves.easeOutCubic,
                    builder: (_, val, __) => LinearProgressIndicator(
                      value: val,
                      minHeight: 5,
                      backgroundColor: const Color(0xFFF3F4F6),
                      valueColor: AlwaysStoppedAnimation(
                        isHighlighted
                            ? AppColors.achievementOrange
                            : const Color(0xFF6B7280).withOpacity(0.5),
                      ),
                    ),
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

// ─────────────────────────────────────────────────────────────────────────────
// Badge de Conquista
// ─────────────────────────────────────────────────────────────────────────────
class AchievementBadge {
  final String id;
  final String emoji;
  final String title;
  final String description;
  final int xpRequired;

  const AchievementBadge({
    required this.id,
    required this.emoji,
    required this.title,
    required this.description,
    required this.xpRequired,
  });

  bool isUnlocked(int xp) => xp >= xpRequired;
}

const List<AchievementBadge> kAllBadges = [
  AchievementBadge(id: 'first_step', emoji: '🌱', title: 'Primeiro Passo', description: 'Completou sua primeira atividade', xpRequired: 15),
  AchievementBadge(id: 'reader', emoji: '📚', title: 'Leitor', description: 'Ganhou 40 XP lendo histórias', xpRequired: 40),
  AchievementBadge(id: 'explorer', emoji: '🗺️', title: 'Explorador', description: 'Alcançou 75 XP', xpRequired: 75),
  AchievementBadge(id: 'level2', emoji: '⭐', title: 'Nível 2', description: 'Chegou ao nível 2', xpRequired: 100),
  AchievementBadge(id: 'streak', emoji: '🔥', title: 'Em Chamas', description: 'Alcançou 150 XP', xpRequired: 150),
  AchievementBadge(id: 'level3', emoji: '🌟', title: 'Nível 3', description: 'Chegou ao nível 3', xpRequired: 200),
];

// ─────────────────────────────────────────────────────────────────────────────
// Grid de Conquistas
// ─────────────────────────────────────────────────────────────────────────────
class AchievementsGrid extends StatelessWidget {
  final int xp;

  const AchievementsGrid({super.key, required this.xp});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.92),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.07),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              const Text('🏅', style: TextStyle(fontSize: 18)),
              const SizedBox(width: 8),
              const Text(
                'Conquistas',
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Color(0xFF1F2937)),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: kAllBadges.asMap().entries.map((e) {
              final idx = e.key;
              final badge = e.value;
              final unlocked = badge.isUnlocked(xp);
              return _BadgeTile(badge: badge, unlocked: unlocked, index: idx);
            }).toList(),
          ),
        ],
      ),
    );
  }
}

class _BadgeTile extends StatelessWidget {
  final AchievementBadge badge;
  final bool unlocked;
  final int index;

  const _BadgeTile({required this.badge, required this.unlocked, required this.index});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => _showTooltip(context),
      child: AnimatedContainer(
        duration: 400.ms,
        width: 68,
        height: 80,
        decoration: BoxDecoration(
          color: unlocked
              ? AppColors.achievementOrange.withOpacity(0.12)
              : const Color(0xFFF9FAFB),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: unlocked
                ? AppColors.achievementOrange.withOpacity(0.5)
                : const Color(0xFFE5E7EB),
            width: 1.5,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              unlocked ? badge.emoji : '🔒',
              style: TextStyle(fontSize: unlocked ? 28 : 22),
            ).animate(target: unlocked ? 1 : 0)
             .scaleXY(begin: 0.8, end: 1.0, duration: 300.ms),
            const SizedBox(height: 4),
            Text(
              badge.title,
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w700,
                color: unlocked ? AppColors.achievementOrange : const Color(0xFF9CA3AF),
              ),
              textAlign: TextAlign.center,
              maxLines: 2,
            ),
          ],
        ),
      ).animate(delay: (index * 60).ms).fadeIn(duration: 300.ms).scaleXY(begin: 0.85),
    );
  }

  void _showTooltip(BuildContext context) {
    ScaffoldMessenger.of(context).clearSnackBars();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: unlocked ? AppColors.achievementOrange : const Color(0xFF374151),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        content: Row(
          children: [
            Text(badge.emoji, style: const TextStyle(fontSize: 20)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(badge.title,
                      style: const TextStyle(fontWeight: FontWeight.w800, color: Colors.white)),
                  Text(
                    unlocked ? badge.description : 'Precisa de ${badge.xpRequired} XP para desbloquear',
                    style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }
}
