import 'package:flutter/material.dart';
import '../domain/avatar.dart';
import 'widgets/head_layer.dart';
import 'widgets/eye_layer.dart';
import 'widgets/eyebrow_layer.dart';
import 'widgets/mouth_layer.dart';
import 'widgets/hair_layer.dart';
import 'widgets/shirt_layer.dart';
import 'widgets/pants_layer.dart';

class OikosAvatarRenderer extends StatelessWidget {
  final OikosAvatar avatar;
  final double size;

  const OikosAvatarRenderer({
    super.key,
    required this.avatar,
    this.size = 200.0,
  });

  @override
  Widget build(BuildContext context) {
    // O sistema Z-Index é vital aqui: do mais profundo pro mais alto.
    return SizedBox(
      width: size,
      height: size,
      child: Transform.scale(
        scale: avatar.heightScale,
        alignment: Alignment.bottomCenter, // Escala pelo chão (pé)
        child: Stack(
          alignment: Alignment.center,
          fit: StackFit.expand,
          children: [
            // 1. Cabelo (parte de trás se houver, no futuro)
            
            // 2. Corpo Base (Pernas, Tronco, Braços)
            // Shirt e Pants por enquanto
            PantsLayer(pantsType: avatar.pants, theme: avatar.theme),
            ShirtLayer(shirtType: avatar.shirt, theme: avatar.theme),
            
            // 3. Cabeça (Oikos Species 35% base)
            HeadLayer(headType: avatar.head, theme: avatar.theme),
            
            // 4. Cabelo (Frente/Topo)
            HairLayer(hairType: avatar.hair, theme: avatar.theme),
            
            // 5. Rosto (As expressões que a IA manipula dinamicamente)
            EyeLayer(eyeType: avatar.activeEyes),
            EyebrowLayer(eyebrowType: avatar.activeEyebrow),
            MouthLayer(mouthType: avatar.activeMouth),
            
            // 6. Acessórios
            // AccessoryLayer(accessoryType: avatar.accessory),
          ],
        ),
      ),
    );
  }
}
