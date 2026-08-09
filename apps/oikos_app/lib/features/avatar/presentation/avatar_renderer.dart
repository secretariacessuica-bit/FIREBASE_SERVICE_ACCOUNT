import 'package:flutter/material.dart';
import '../domain/avatar.dart';
import '../domain/avatar_parts.dart';
import 'avatar_composer.dart';

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
    final List<AvatarPart> parts = [];

    // 1. Base (Corpo) - Mapeia a cor da pele selecionada para a base correspondente (1 a 4)
    int skinIndex = 1;
    final int skinColorValue = avatar.theme.skinColor.value;
    if (skinColorValue == const Color(0xFFFFCD94).value) {
      skinIndex = 1; // Clara
    } else if (skinColorValue == const Color(0xFFEAC086).value) {
      skinIndex = 2; // Média
    } else if (skinColorValue == const Color(0xFF8D5524).value) {
      skinIndex = 3; // Média/Escura
    } else if (skinColorValue == const Color(0xFF4A3B32).value) {
      skinIndex = 4; // Escura
    } else {
      skinIndex = 1; // Fallback
    }

    parts.add(AvatarPart(
      type: PartType.base,
      assetPath: 'assets/images/avatar/bases/base_$skinIndex.png',
      layerIndex: 1,
    ));

    // 2. Calças/Pants - Mapeia PantsType
    String pantsFile = '';
    if (avatar.pants != PantsType.none) {
      final indexStr = avatar.pants.name.replaceAll('pants', '');
      pantsFile = 'pants_$indexStr.png';
    }
    if (pantsFile.isNotEmpty) {
      parts.add(AvatarPart(
        type: PartType.pants,
        assetPath: 'assets/images/avatar/pants/$pantsFile',
        layerIndex: 2,
      ));
    }

    // 3. Camisas/Shirts - Mapeia ShirtType
    String shirtFile = 'shirt_01.png';
    if (avatar.shirt == ShirtType.basic) {
      shirtFile = 'shirt_01.png';
    } else if (avatar.shirt == ShirtType.hoodie) {
      shirtFile = 'shirt_02.png';
    } else if (avatar.shirt == ShirtType.jacket) {
      shirtFile = 'shirt_03.png';
    } else if (avatar.shirt == ShirtType.none) {
      shirtFile = '';
    }
    if (shirtFile.isNotEmpty) {
      parts.add(AvatarPart(
        type: PartType.shirt,
        assetPath: 'assets/images/avatar/shirts/$shirtFile',
        layerIndex: 3,
      ));
    }

    // 4. Cabelos/Hairs - Mapeia HairType e cor para a fatia correspondente (1 a 7)
    int colorIndex = 3; // Padrão Black
    final int hairColorValue = avatar.theme.hairColor.value;
    if (hairColorValue == const Color(0xFF000000).value) {
      colorIndex = 3; // Black
    } else if (hairColorValue == const Color(0xFF4A3B32).value) {
      colorIndex = 2; // Dark Brown
    } else if (hairColorValue == const Color(0xFF8B4513).value) {
      colorIndex = 1; // Light Brown
    } else if (hairColorValue == const Color(0xFFF5DEB3).value) {
      colorIndex = 4; // Blonde
    } else if (hairColorValue == const Color(0xFFFF69B4).value) {
      colorIndex = 6; // Pink
    } else if (hairColorValue == const Color(0xFF2196F3).value) {
      colorIndex = 5; // Blue/Ginger
    } else {
      colorIndex = 7; // White
    }

    String hairStyle = '';
    switch (avatar.hair) {
      case HairType.short01:
        hairStyle = 'short01';
        break;
      case HairType.short02:
        hairStyle = 'short02';
        break;
      case HairType.long01:
        hairStyle = 'long01';
        break;
      case HairType.long02:
      case HairType.bun:
        hairStyle = 'long02';
        break;
      case HairType.none:
        hairStyle = '';
        break;
    }

    if (hairStyle.isNotEmpty) {
      parts.add(AvatarPart(
        type: PartType.hair,
        assetPath: 'assets/images/avatar/hairs/hair_${hairStyle}_$colorIndex.png',
        layerIndex: 4,
      ));
    }

    // 5. Rostos/Faces - Mapeia MouthType para as fatias de face correspondentes
    String indexStr = avatar.activeMouth.name.replaceAll('face', '');
    String faceFile = 'face_$indexStr.png';

    parts.add(AvatarPart(
      type: PartType.eyes,
      assetPath: 'assets/images/avatar/faces/$faceFile',
      layerIndex: 5,
    ));

    return Transform.scale(
      scale: avatar.heightScale,
      alignment: Alignment.bottomCenter, // Escala pelo chão (pé)
      child: AvatarComposer(
        selectedParts: parts,
        size: size,
      ),
    );
  }
}
