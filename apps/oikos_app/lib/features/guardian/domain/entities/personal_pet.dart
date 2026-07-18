enum PetEvolutionStage {
  egg,
  baby,
  child,
  teen,
  adult,
  mythic
}

class PersonalPet {
  final String petId;
  final String ownerId; // Relaciona com MemberIdentity.id
  final String name;
  final String species; // Ex: 'Dragon', 'Monster', 'Robot'
  final int level;
  final int xp;
  final double hunger; // 0.0 (faminto) a 1.0 (satisfeito)
  final PetEvolutionStage evolutionStage;

  const PersonalPet({
    required this.petId,
    required this.ownerId,
    required this.name,
    required this.species,
    this.level = 1,
    this.xp = 0,
    this.hunger = 0.5,
    this.evolutionStage = PetEvolutionStage.baby,
  });

  PersonalPet feed(int foodAmount) {
    // Lógica para aumentar XP e reduzir fome (aumentar para 1.0)
    final newXp = xp + foodAmount;
    final newHunger = (hunger + 0.2).clamp(0.0, 1.0);
    // Lógica simples de level up
    final newLevel = level + (newXp ~/ 100); 
    
    return PersonalPet(
      petId: petId,
      ownerId: ownerId,
      name: name,
      species: species,
      level: newLevel,
      xp: newXp % 100,
      hunger: newHunger,
      evolutionStage: _calculateStage(newLevel),
    );
  }

  PetEvolutionStage _calculateStage(int currentLevel) {
    if (currentLevel < 5) return PetEvolutionStage.baby;
    if (currentLevel < 15) return PetEvolutionStage.child;
    if (currentLevel < 30) return PetEvolutionStage.teen;
    if (currentLevel < 50) return PetEvolutionStage.adult;
    return PetEvolutionStage.mythic;
  }
}
