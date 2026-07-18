import '../../domain/entities/mission.dart';
import '../../domain/entities/mission_category.dart';

class MissionSeeds {
  static List<Mission> getDailySeeds() {
    return [
      const Mission(
        id: 'm1',
        title: 'Ler 15 minutos',
        description: 'Leia um livro que você gosta.',
        category: MissionCategory.ler,
        xpReward: 15,
      ),
      const Mission(
        id: 'm2',
        title: 'Ajudar alguém',
        description: 'Faça um pequeno favor para alguém da sua família.',
        category: MissionCategory.gentileza,
        xpReward: 20,
      ),
      const Mission(
        id: 'm3',
        title: 'Completar uma lição',
        description: 'Avance na sua jornada de aprendizado.',
        category: MissionCategory.aprender,
        xpReward: 25,
      ),
    ];
  }
}
