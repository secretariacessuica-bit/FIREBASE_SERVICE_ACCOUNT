import 'package:flutter/material.dart';

/// Tipos de peças do sistema modular de avatares
enum PartType {
  skin,
  base,
  shirt,
  pants,
  hair,
  eyes,
  mouth,
  accessory,
  shoes
}

/// Enums específicos para personalização conforme especificação de Design System
enum SkinTone { fair, light, medium, tan, dark }
enum HairStyle { short, long, curly, afro, bun, braids, ponytail, none }
enum EyeShape { friendly, happy, excited, calm }
enum MouthShape { smile, open, laugh, neutral }
enum TopCloth { tShirt, hoodie, jacket, shirt, none }
enum BottomCloth { jeans, shorts, skirt, pants, none }
enum Shoes { sneakers, boots, flats, none }
enum Accessory { glasses, cap, hat, headphones, none }

/// Modelo para representar uma peça/camada individual do avatar
class AvatarPart {
  final PartType type;
  final String assetPath;
  final int layerIndex; // 0 (fundo) a 9 (topo)

  AvatarPart({
    required this.type,
    required this.assetPath,
    required this.layerIndex,
  });
}

/// Motor de Renderização de Avatar por Camadas Centralizadas
class AvatarComposer extends StatelessWidget {
  final List<AvatarPart> selectedParts;
  final double size;

  const AvatarComposer({
    super.key,
    required this.selectedParts,
    this.size = 250.0,
  });

  @override
  Widget build(BuildContext context) {
    // Ordena as peças pelo index de camada antes de renderizar
    final sortedParts = List<AvatarPart>.from(selectedParts)
      ..sort((a, b) => a.layerIndex.compareTo(b.layerIndex));

    return SizedBox(
      width: size,
      height: size,
      child: AspectRatio(
        aspectRatio: 1.0, // Mantém o quadrado perfeito
        child: AnimatedSwitcher(
          duration: const Duration(milliseconds: 250),
          transitionBuilder: (Widget child, Animation<double> animation) {
            return FadeTransition(opacity: animation, child: child);
          },
          child: Stack(
            key: ValueKey(sortedParts.map((e) => '${e.type.name}_${e.assetPath}').join('__')),
            alignment: Alignment.center,
            fit: StackFit.expand,
            children: sortedParts.map((part) {
              return Image.asset(
                part.assetPath,
                fit: BoxFit.contain,
                width: size,
                height: size,
                errorBuilder: (context, error, stackTrace) {
                  // Fallback visual silencioso caso a imagem física não exista
                  return const SizedBox.shrink();
                },
              );
            }).toList(),
          ),
        ),
      ),
    );
  }
}
