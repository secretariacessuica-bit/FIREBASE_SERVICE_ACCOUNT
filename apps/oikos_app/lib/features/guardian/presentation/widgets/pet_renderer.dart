import 'package:flutter/material.dart';
import '../../domain/entities/personal_pet.dart';
import '../../domain/pet_appearance_factory.dart';
import '../../../../shared/creature_engine/creature_renderer.dart';

/// Renderiza o Pet pessoal do usuário usando o CreatureRenderer modular.
/// Chame com a instância de [PersonalPet] e o tamanho desejado.
class PetRenderer extends StatelessWidget {
  final PersonalPet pet;
  final double size;
  final PetExpression expression;
  final bool animated;

  const PetRenderer({
    super.key,
    required this.pet,
    this.size = 120.0,
    this.expression = PetExpression.neutral,
    this.animated = true,
  });

  @override
  Widget build(BuildContext context) {
    final appearance = PetAppearanceFactory.build(pet, expression: expression);

    return CreatureRenderer(
      appearance: appearance,
      size: size,
      animated: animated,
    );
  }
}
