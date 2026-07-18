import '../../../shared/creature_engine/creature_appearance.dart';
import 'entities/personal_pet.dart';

enum PetExpression {
  neutral,
  happy,
  hungry,
}

/// Factory que transforma um [PersonalPet] numa [CreatureAppearance] renderizável.
/// Mapeia: species + evolutionStage + expression → caminhos SVG corretos.
class PetAppearanceFactory {
  static CreatureAppearance build(
    PersonalPet pet, {
    PetExpression expression = PetExpression.neutral,
  }) {
    final bodyPath = _bodyPath(pet.species, pet.evolutionStage);
    final eyesPath = expression == PetExpression.happy
        ? 'assets/guardians/pets/shared/pet_eyes_happy.svg'
        : 'assets/guardians/pets/shared/pet_eyes_neutral.svg';

    return CreatureAppearance(
      body: CreaturePart(assetPath: bodyPath),
      eyes: CreaturePart(assetPath: eyesPath),
    );
  }

  static String _bodyPath(String species, PetEvolutionStage stage) {
    final stageName = switch (stage) {
      PetEvolutionStage.egg    => 'egg',
      PetEvolutionStage.baby   => 'baby',
      PetEvolutionStage.child  => 'baby', // até ter o svg child
      PetEvolutionStage.teen   => 'baby', // idem
      PetEvolutionStage.adult  => 'baby', // idem
      PetEvolutionStage.mythic => 'baby', // idem
    };

    final speciesFolder = switch (species.toLowerCase()) {
      'dragon'  => 'dragon',
      'monster' => 'monster',
      'robot'   => 'robot',
      _         => 'dragon', // fallback
    };

    return 'assets/guardians/pets/$speciesFolder/${speciesFolder}_${stageName}_body.svg';
  }
}
