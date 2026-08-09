import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';

class ShirtLayer extends StatelessWidget {
  final ShirtType shirtType;
  final AvatarTheme theme;

  const ShirtLayer({super.key, required this.shirtType, required this.theme});

  @override
  Widget build(BuildContext context) {
    if (shirtType == ShirtType.none) return const SizedBox.shrink();
    
    final path = 'assets/avatars/shirts/shirt_basic.svg';
    return SvgPicture.asset(
      path,
      theme: SvgTheme(currentColor: theme.shirtColor),
    );
  }
}
