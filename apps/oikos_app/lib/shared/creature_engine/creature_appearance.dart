/// Cada parte de uma criatura (Lumo, Pet, etc.).
/// O [assetPath] aponta para o SVG real. Se for null, usa o placeholder.
class CreaturePart {
  final String assetPath;
  final double offsetX;
  final double offsetY;
  final double scale;

  const CreaturePart({
    required this.assetPath,
    this.offsetX = 0.0,
    this.offsetY = 0.0,
    this.scale = 1.0,
  });
}

/// Define a composição visual de qualquer criatura do universo Oikos.
class CreatureAppearance {
  /// Partes obrigatórias
  final CreaturePart body;

  /// Partes opcionais (nullable = não renderizado)
  final CreaturePart? eyes;
  final CreaturePart? mouth;
  final CreaturePart? accessory1; // ex: folha do Lumo, chifre do Dragão
  final CreaturePart? accessory2; // ex: asas, cauda
  final CreaturePart? expression; // override emocional de olhos+boca combinado

  const CreatureAppearance({
    required this.body,
    this.eyes,
    this.mouth,
    this.accessory1,
    this.accessory2,
    this.expression,
  });

  CreatureAppearance copyWithExpression({CreaturePart? expression}) {
    return CreatureAppearance(
      body: body,
      eyes: eyes,
      mouth: mouth,
      accessory1: accessory1,
      accessory2: accessory2,
      expression: expression,
    );
  }
}
