import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';

class HeadLayer extends StatelessWidget {
  final HeadType headType;
  final AvatarTheme theme;

  const HeadLayer({super.key, required this.headType, required this.theme});

  @override
  Widget build(BuildContext context) {
    // In a real app, we map the enum to the specific SVG file path
    // For now we use the placeholder
    final path = 'assets/avatars/heads/head_01.svg';
    
    return SvgPicture.asset(
      path,
      theme: SvgTheme(currentColor: theme.skinColor),
    );
  }
}
