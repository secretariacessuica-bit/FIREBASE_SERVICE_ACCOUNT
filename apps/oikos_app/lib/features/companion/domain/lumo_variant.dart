enum LumoVariant {
  idle,
  happy,
  listening,
  thinking,
  celebrating,
  sad,
  confused,
  sleeping,
  excited,
  proud,
}

class LumoVariantProperties {
  final double bodyRotation;
  final double leftArmRotation;
  final double rightArmRotation;
  final double glowOpacity;
  final String eyeAsset;
  final String mouthAsset;
  final double yOffset;

  const LumoVariantProperties({
    this.bodyRotation = 0.0,
    this.leftArmRotation = 0.0,
    this.rightArmRotation = 0.0,
    this.glowOpacity = 0.4,
    this.eyeAsset = 'assets/images/lumo/eyes/eye_normal.svg',
    this.mouthAsset = 'assets/images/lumo/mouths/mouth_smile.svg',
    this.yOffset = 0.0,
  });
}

extension LumoVariantExtension on LumoVariant {
  LumoVariantProperties get properties {
    switch (this) {
      case LumoVariant.idle:
        return const LumoVariantProperties(
          leftArmRotation: -14.0,
          rightArmRotation: 14.0,
          glowOpacity: 0.4,
        );
      case LumoVariant.happy:
        return const LumoVariantProperties(
          leftArmRotation: -30.0,
          rightArmRotation: 30.0,
          glowOpacity: 0.6,
          eyeAsset: 'assets/images/lumo/eyes/eye_happy.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_big_smile.svg',
          yOffset: -5.0, // little jump
        );
      case LumoVariant.listening:
        return const LumoVariantProperties(
          bodyRotation: 5.0,
          leftArmRotation: -10.0,
          rightArmRotation: 20.0,
          glowOpacity: 0.5,
          mouthAsset: 'assets/images/lumo/mouths/mouth_neutral.svg',
        );
      case LumoVariant.thinking:
        return const LumoVariantProperties(
          bodyRotation: -5.0,
          leftArmRotation: -45.0, // scratching head
          rightArmRotation: 10.0,
          eyeAsset: 'assets/images/lumo/eyes/eye_big.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_neutral.svg',
        );
      case LumoVariant.celebrating:
        return const LumoVariantProperties(
          leftArmRotation: -60.0,
          rightArmRotation: 60.0,
          glowOpacity: 0.8,
          eyeAsset: 'assets/images/lumo/eyes/eye_star.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_big_smile.svg',
          yOffset: -10.0, // big jump
        );
      case LumoVariant.sad:
        return const LumoVariantProperties(
          bodyRotation: 5.0,
          leftArmRotation: 10.0, // Arms down
          rightArmRotation: -10.0,
          glowOpacity: 0.2,
          eyeAsset: 'assets/images/lumo/eyes/eye_closed.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_sad.svg',
          yOffset: 2.0, // slouched
        );
      case LumoVariant.confused:
        return const LumoVariantProperties(
          bodyRotation: 10.0,
          leftArmRotation: -20.0,
          rightArmRotation: 20.0,
          eyeAsset: 'assets/images/lumo/eyes/eye_normal.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_wavy.svg',
        );
      case LumoVariant.sleeping:
        return const LumoVariantProperties(
          bodyRotation: 15.0,
          leftArmRotation: 20.0,
          rightArmRotation: -20.0,
          glowOpacity: 0.1,
          eyeAsset: 'assets/images/lumo/eyes/eye_closed.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_neutral.svg',
          yOffset: 5.0, // resting
        );
      case LumoVariant.excited:
        return const LumoVariantProperties(
          leftArmRotation: -45.0,
          rightArmRotation: 45.0,
          glowOpacity: 0.7,
          eyeAsset: 'assets/images/lumo/eyes/eye_big.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_big_smile.svg',
          yOffset: -5.0,
        );
      case LumoVariant.proud:
        return const LumoVariantProperties(
          bodyRotation: -5.0,
          leftArmRotation: 15.0, // arms on "hips"
          rightArmRotation: -15.0,
          glowOpacity: 0.6,
          eyeAsset: 'assets/images/lumo/eyes/eye_happy.svg',
          mouthAsset: 'assets/images/lumo/mouths/mouth_smile.svg',
        );
    }
  }
}
