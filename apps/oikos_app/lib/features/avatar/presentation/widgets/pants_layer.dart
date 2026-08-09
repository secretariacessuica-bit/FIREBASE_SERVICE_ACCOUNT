import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';

class PantsLayer extends StatelessWidget {
  final PantsType pantsType;
  final AvatarTheme theme;

  const PantsLayer({super.key, required this.pantsType, required this.theme});

  @override
  Widget build(BuildContext context) {
    if (pantsType == PantsType.none) return const SizedBox.shrink();
    
    final path = 'assets/avatars/pants/pants_basic.svg';
    return SvgPicture.asset(
      path,
      theme: SvgTheme(currentColor: theme.pantsColor),
    );
  }
}
