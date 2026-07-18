import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/identity_expression.dart';
import '../../domain/entities/member_identity.dart';
import '../../domain/entities/memory.dart';
import '../../domain/entities/recent_activity.dart';

class ProfileDemoData {
  static MemberIdentity getPedroIdentity() {
    final now = DateTime.now();
    return MemberIdentity(
      id: 'm1',
      name: 'Pedro',
      avatarUrl: 'assets/avatars/pedro.png',
      favoriteColor: AppColors.learningBlue,
      firstAccessDate: now.subtract(const Duration(days: 12)),
      interests: ['Matemática', 'Dinossauros', 'Espaço'],
      currentExpression: IdentityExpression.happy,
      memoryCollection: [
        Memory(
          id: 'mem1',
          title: 'Meu primeiro livro',
          description: 'Você leu uma história inteira sobre dinossauros.',
          date: now.subtract(const Duration(days: 10)),
          emoji: '📖',
        ),
        Memory(
          id: 'mem2',
          title: 'Primeira missão de gentileza',
          description: 'Você ajudou a organizar os brinquedos.',
          date: now.subtract(const Duration(days: 5)),
          emoji: '❤️',
        ),
      ],
      recentActivities: [
        RecentActivity(
          id: 'ra1',
          description: 'Você terminou Matemática.',
          date: now.subtract(const Duration(hours: 1)),
        ),
        RecentActivity(
          id: 'ra2',
          description: 'Você ajudou alguém.',
          date: now.subtract(const Duration(days: 1)),
        ),
        RecentActivity(
          id: 'ra3',
          description: 'Você completou sua primeira leitura.',
          date: now.subtract(const Duration(days: 7)),
        ),
      ],
    );
  }
}
