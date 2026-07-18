import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';

class HairLayer extends StatelessWidget {
  final HairType hairType;
  final AvatarTheme theme;

  const HairLayer({super.key, required this.hairType, required this.theme});

  @override
  Widget build(BuildContext context) {
    if (hairType == HairType.none) return const SizedBox.shrink();
    
    String filename;
    switch (hairType) {
      case HairType.short01:
        filename = 'hair_short01.svg';
        break;
      case HairType.short02:
        filename = 'hair_short02.svg';
        break;
      case HairType.long01:
        filename = 'hair_long01.svg';
        break;
      case HairType.long02:
        filename = 'hair_long02.svg';
        break;
      case HairType.bun:
        filename = 'hair_bun.svg';
        break;
      default:
        filename = 'hair_short01.svg';
    }
    
    final path = 'assets/avatars/hairs/$filename';
    return SvgPicture.asset(
      path,
      colorFilter: ColorFilter.mode(theme.hairColor, BlendMode.srcATop),
    );
  }
}
