import 'avatar_parts.dart';

enum AvatarExpressionType {
  neutral,
  happy,
  thinking,
  studying,
  sad,
  excited,
}

class AvatarExpression {
  final AvatarExpressionType type;
  final EyeType? eyeOverride;
  final EyebrowType? eyebrowOverride;
  final MouthType? mouthOverride;

  const AvatarExpression({
    required this.type,
    this.eyeOverride,
    this.eyebrowOverride,
    this.mouthOverride,
  });

  /// Retorna as configurações faciais padrão para cada emoção.
  /// Isso permite que a IA (Brain) ou o UI mude a emoção do avatar
  /// com apenas um enum, substituindo as peças faciais originais.
  static AvatarExpression fromType(AvatarExpressionType type) {
    switch (type) {
      case AvatarExpressionType.happy:
      case AvatarExpressionType.excited:
        return const AvatarExpression(
          type: AvatarExpressionType.happy,
          eyebrowOverride: EyebrowType.happy,
          mouthOverride: MouthType.face02,
        );
      case AvatarExpressionType.thinking:
      case AvatarExpressionType.studying:
        return const AvatarExpression(
          type: AvatarExpressionType.thinking,
          eyebrowOverride: EyebrowType.angry, // Sobrancelhas franzidas de concentração
          mouthOverride: MouthType.face03,
        );
      case AvatarExpressionType.sad:
        return const AvatarExpression(
          type: AvatarExpressionType.sad,
          eyebrowOverride: EyebrowType.sad,
          mouthOverride: MouthType.face04,
        );
      case AvatarExpressionType.neutral:
      default:
        return const AvatarExpression(
          type: AvatarExpressionType.neutral,
          // Deixa nulo para usar as peças originais que o usuário escolheu
          eyeOverride: null,
          eyebrowOverride: null,
          mouthOverride: null,
        );
    }
  }
}
