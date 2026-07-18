import 'package:flutter/material.dart';
import '../../../domain/entities/age_experience_mode.dart';
import '../../../../../shared/creature_engine/creature_renderer.dart';
import '../../../companion/domain/lumo_appearance_factory.dart';
import '../../../brain/domain/entities/learning_decision.dart';

class PersonalCompanion extends StatelessWidget {
  final AgeExperienceMode experienceMode;
  final VoidCallback? onTap;
  final LumoExpression expression;
  final double size;
  final int level;

  const PersonalCompanion({
    super.key,
    required this.experienceMode,
    this.onTap,
    this.expression = LumoExpression.neutral,
    this.size = 120.0,
    this.level = 1,
  });

  @override
  Widget build(BuildContext context) {
    final appearance = LumoAppearanceFactory.build(
      expression: expression,
      level: level,
    );

    return GestureDetector(
      onTap: onTap,
      child: CreatureRenderer(
        appearance: appearance,
        size: size,
        animated: true,
      ),
    );
  }
}
