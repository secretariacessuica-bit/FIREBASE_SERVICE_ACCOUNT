import '../../../shared/creature_engine/creature_appearance.dart';

enum LumoExpression {
  neutral,
  happy,
  thinking,
  cheering,
  sad,
}

/// Factory que mapeia uma expressão emocional do Lumo às partes de SVG corretas.
class LumoAppearanceFactory {
  static CreatureAppearance build({
    LumoExpression expression = LumoExpression.neutral,
    int level = 1,
  }) {
    final String bodyAsset;
    final String leafAsset;

    if (level < 5) {
      bodyAsset = 'assets/companion/lumo/bodies/lumo_baby.svg';
      leafAsset = 'assets/companion/lumo/leaves/lumo_leaf_single.svg';
    } else if (level < 10) {
      bodyAsset = 'assets/companion/lumo/bodies/lumo_teen.svg';
      leafAsset = 'assets/companion/lumo/leaves/lumo_leaf_double.svg';
    } else {
      bodyAsset = 'assets/companion/lumo/bodies/lumo_adult.svg';
      leafAsset = 'assets/companion/lumo/leaves/lumo_flower.svg';
    }

    final body = CreaturePart(
      assetPath: bodyAsset,
    );

    final leaf = CreaturePart(
      assetPath: leafAsset,
      offsetY: -36,
      scale: 0.45,
    );

    switch (expression) {
      case LumoExpression.happy:
      case LumoExpression.cheering:
        return CreatureAppearance(
          body: body,
          accessory1: leaf,
          // Expression override: olhos felizes + sorriso (um único SVG combinado)
          expression: const CreaturePart(
            assetPath: 'assets/companion/lumo/eyes/lumo_eyes_happy.svg',
          ),
          mouth: const CreaturePart(
            assetPath: 'assets/companion/lumo/mouths/lumo_mouth_smile.svg',
          ),
        );

      case LumoExpression.thinking:
        return CreatureAppearance(
          body: body,
          accessory1: leaf,
          eyes: const CreaturePart(
            assetPath: 'assets/companion/lumo/eyes/lumo_eyes_neutral.svg',
          ),
          // boca reta (sem SVG ainda, omite)
        );

      case LumoExpression.sad:
        return CreatureAppearance(
          body: body,
          accessory1: leaf,
          eyes: const CreaturePart(
            assetPath: 'assets/companion/lumo/eyes/lumo_eyes_neutral.svg',
          ),
        );

      case LumoExpression.neutral:
      default:
        return CreatureAppearance(
          body: body,
          accessory1: leaf,
          eyes: const CreaturePart(
            assetPath: 'assets/companion/lumo/eyes/lumo_eyes_neutral.svg',
          ),
          mouth: const CreaturePart(
            assetPath: 'assets/companion/lumo/mouths/lumo_mouth_smile.svg',
          ),
        );
    }
  }
}
