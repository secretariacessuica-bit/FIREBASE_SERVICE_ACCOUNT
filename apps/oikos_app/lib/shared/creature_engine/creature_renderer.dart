import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'creature_appearance.dart';

/// Motor genérico de renderização por camadas SVG para criaturas do universo Oikos.
/// Utilizado pelo Lumo (companion) e pelos Personal Pets (guardians).
///
/// A ordem Z é: body → accessory2 (cauda) → eyes → mouth → accessory1 (folha/chifre) → expression
class CreatureRenderer extends StatelessWidget {
  final CreatureAppearance appearance;
  final double size;
  final bool animated; // Animação flutuante padrão

  const CreatureRenderer({
    super.key,
    required this.appearance,
    this.size = 120.0,
    this.animated = true,
  });

  @override
  Widget build(BuildContext context) {
    Widget creature = SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        fit: StackFit.expand,
        children: [
          // 1. Corpo base (sempre presente)
          _layer(appearance.body),

          // 2. Acessório traseiro (ex: cauda, asas de trás)
          if (appearance.accessory2 != null)
            _layer(appearance.accessory2!),

          // 3. Olhos base (só renderiza se não há expression override)
          if (appearance.expression == null && appearance.eyes != null)
            _layer(appearance.eyes!),

          // 4. Boca base (só renderiza se não há expression override)
          if (appearance.expression == null && appearance.mouth != null)
            _layer(appearance.mouth!),

          // 5. Override de expressão emocional (substitui olhos + boca)
          if (appearance.expression != null)
            _layer(appearance.expression!),

          // 6. Acessório frontal (ex: folha do Lumo, chifre, chapéu)
          if (appearance.accessory1 != null)
            _layer(appearance.accessory1!),
        ],
      ),
    );

    if (animated) {
      creature = creature
          .animate(onPlay: (c) => c.repeat(reverse: true))
          .moveY(begin: -4, end: 4, duration: 2200.ms, curve: Curves.easeInOut);
    }

    return creature;
  }

  Widget _layer(CreaturePart part) {
    return Positioned(
      left: size / 2 + part.offsetX - (size * part.scale / 2),
      top: size / 2 + part.offsetY - (size * part.scale / 2),
      width: size * part.scale,
      height: size * part.scale,
      child: SvgPicture.asset(
        part.assetPath,
        fit: BoxFit.contain,
      ),
    );
  }
}
