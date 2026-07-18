enum MissionCategory {
  aprender,
  organizar,
  ler,
  saude,
  gentileza,
  familia,
}

extension MissionCategoryExtension on MissionCategory {
  String get name {
    switch (this) {
      case MissionCategory.aprender:
        return 'Aprender';
      case MissionCategory.organizar:
        return 'Organizar';
      case MissionCategory.ler:
        return 'Ler';
      case MissionCategory.saude:
        return 'Saúde';
      case MissionCategory.gentileza:
        return 'Gentileza';
      case MissionCategory.familia:
        return 'Família';
    }
  }

  String get emoji {
    switch (this) {
      case MissionCategory.aprender:
        return '📚';
      case MissionCategory.organizar:
        return '🧸';
      case MissionCategory.ler:
        return '📖';
      case MissionCategory.saude:
        return '🍎';
      case MissionCategory.gentileza:
        return '❤️';
      case MissionCategory.familia:
        return '🤝';
    }
  }
}
