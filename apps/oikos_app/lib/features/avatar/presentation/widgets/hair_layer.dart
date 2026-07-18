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
    
    final path = 'assets/avatars/hairs/hair_short01.svg';
    return SvgPicture.asset(
      path,
      colorFilter: ColorFilter.mode(theme.hairColor, BlendMode.srcATop),
    );
  }
}
