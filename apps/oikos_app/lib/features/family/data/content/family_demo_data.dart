import '../../domain/entities/family_activity.dart';
import '../../domain/entities/family_moment.dart';
import '../../domain/entities/family_moment_type.dart';

class FamilyDemoData {
  static final Map<String, String> members = {
    'm1': 'Pedro',
    'm2': 'Maria',
    'm3': 'João',
    'm4': 'Sofia',
  };

  static List<FamilyActivity> getActivities() {
    final now = DateTime.now();
    return [
      FamilyActivity(
        id: 'a1',
        memberId: 'm1',
        eventType: 'lesson_completed',
        date: now.subtract(const Duration(hours: 1)),
        metadata: {'subject': 'Matemática'},
      ),
      FamilyActivity(
        id: 'a2',
        memberId: 'm2',
        eventType: 'mission_completed',
        date: now.subtract(const Duration(hours: 3)),
        metadata: {'mission': 'Gentileza'},
      ),
      FamilyActivity(
        id: 'a3',
        memberId: 'm3',
        eventType: 'mission_completed',
        date: now.subtract(const Duration(hours: 24)),
        metadata: {'mission': 'Leitura'},
      ),
    ];
  }

  static List<FamilyMoment> getMoments() {
    final now = DateTime.now();
    return [
      FamilyMoment(
        id: 'mom1',
        title: 'Nossa primeira semana juntos',
        description: 'Todos concluíram suas missões diárias.',
        type: FamilyMomentType.milestone,
        date: now.subtract(const Duration(days: 1)),
        emoji: '⭐',
      ),
      FamilyMoment(
        id: 'mom2',
        title: 'Primeiro Livro',
        description: 'Nossa família concluiu a primeira leitura juntos.',
        type: FamilyMomentType.celebration,
        date: now.subtract(const Duration(days: 3)),
        emoji: '📖',
      ),
    ];
  }
}
