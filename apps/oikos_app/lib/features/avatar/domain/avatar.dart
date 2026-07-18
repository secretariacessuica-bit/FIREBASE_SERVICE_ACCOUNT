import 'dart:convert';
import 'avatar_parts.dart';
import 'avatar_expression.dart';
import 'avatar_theme.dart';

class OikosAvatar {
  final String id;
  
  // Peças base escolhidas pelo usuário
  final HeadType head;
  final EyeType eyes;
  final EyebrowType eyebrow;
  final MouthType mouth;
  final HairType hair;
  final ShirtType shirt;
  final PantsType pants;
  final ShoeType shoes;
  final AccessoryType accessory;

  // Cores
  final AvatarTheme theme;

  // Oikos Species Scaling (1.0 = Adult, 0.6 = Child 4-7)
  final double heightScale;

  // Estado efêmero (não persistido, gerenciado dinamicamente)
  final AvatarExpression currentExpression;

  const OikosAvatar({
    required this.id,
    this.head = HeadType.round01,
    this.eyes = EyeType.default01,
    this.eyebrow = EyebrowType.neutral,
    this.mouth = MouthType.smile,
    this.hair = HairType.short01,
    this.shirt = ShirtType.basic,
    this.pants = PantsType.basic,
    this.shoes = ShoeType.sneaker,
    this.accessory = AccessoryType.none,
    required this.theme,
    this.heightScale = 1.0,
    this.currentExpression = const AvatarExpression(type: AvatarExpressionType.neutral),
  });

  /// Factory para criar um avatar default (usado quando o perfil ainda não personalizou)
  factory OikosAvatar.defaultAvatar(String id, {double scale = 1.0}) {
    return OikosAvatar(
      id: id,
      theme: AvatarTheme.defaultTheme(),
      heightScale: scale,
    );
  }

  /// Resolve a peça facial real a ser renderizada (base vs override de expressão)
  EyeType get activeEyes => currentExpression.eyeOverride ?? eyes;
  EyebrowType get activeEyebrow => currentExpression.eyebrowOverride ?? eyebrow;
  MouthType get activeMouth => currentExpression.mouthOverride ?? mouth;

  OikosAvatar copyWithExpression(AvatarExpressionType newExpressionType) {
    return OikosAvatar(
      id: id,
      head: head,
      eyes: eyes,
      eyebrow: eyebrow,
      mouth: mouth,
      hair: hair,
      shirt: shirt,
      pants: pants,
      shoes: shoes,
      accessory: accessory,
      theme: theme,
      heightScale: heightScale,
      currentExpression: AvatarExpression.fromType(newExpressionType),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'head': head.name,
      'eyes': eyes.name,
      'eyebrow': eyebrow.name,
      'mouth': mouth.name,
      'hair': hair.name,
      'shirt': shirt.name,
      'pants': pants.name,
      'shoes': shoes.name,
      'accessory': accessory.name,
      'theme': theme.toJson(),
      'heightScale': heightScale,
    };
  }

  factory OikosAvatar.fromJson(Map<String, dynamic> json) {
    return OikosAvatar(
      id: json['id'] as String,
      head: HeadType.values.firstWhere((e) => e.name == json['head'], orElse: () => HeadType.round01),
      eyes: EyeType.values.firstWhere((e) => e.name == json['eyes'], orElse: () => EyeType.default01),
      eyebrow: EyebrowType.values.firstWhere((e) => e.name == json['eyebrow'], orElse: () => EyebrowType.neutral),
      mouth: MouthType.values.firstWhere((e) => e.name == json['mouth'], orElse: () => MouthType.smile),
      hair: HairType.values.firstWhere((e) => e.name == json['hair'], orElse: () => HairType.short01),
      shirt: ShirtType.values.firstWhere((e) => e.name == json['shirt'], orElse: () => ShirtType.basic),
      pants: PantsType.values.firstWhere((e) => e.name == json['pants'], orElse: () => PantsType.basic),
      shoes: ShoeType.values.firstWhere((e) => e.name == json['shoes'], orElse: () => ShoeType.sneaker),
      accessory: AccessoryType.values.firstWhere((e) => e.name == json['accessory'], orElse: () => AccessoryType.none),
      theme: AvatarTheme.fromJson(json['theme'] as Map<String, dynamic>),
      heightScale: (json['heightScale'] as num?)?.toDouble() ?? 1.0,
    );
  }

  String toJsonString() => jsonEncode(toJson());

  factory OikosAvatar.fromJsonString(String source) => OikosAvatar.fromJson(jsonDecode(source) as Map<String, dynamic>);

  /// Retorna true se o valor de avatarAsset é um avatar dinâmico JSON (puro ou URL-encoded).
  static bool isAvatarJson(String? value) {
    if (value == null || value.isEmpty) return false;
    return value.startsWith('{') || value.startsWith('%7B') || value.startsWith('%7b');
  }

  /// Tenta parsear um avatarAsset (JSON puro ou URL-encoded) para OikosAvatar.
  /// Retorna null se falhar ou se não for um JSON de avatar.
  static OikosAvatar? tryFromAvatarAsset(String? value) {
    if (!isAvatarJson(value)) return null;
    try {
      final decoded = (value!.startsWith('{')) ? value : Uri.decodeComponent(value);
      return OikosAvatar.fromJsonString(decoded);
    } catch (_) {
      return null;
    }
  }
}
