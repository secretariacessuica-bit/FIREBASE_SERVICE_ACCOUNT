import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';

class ShoeLayer extends StatelessWidget {
  final ShoeType shoeType;
  final AvatarTheme theme;

  const ShoeLayer({super.key, required this.shoeType, required this.theme});

  @override
  Widget build(BuildContext context) {
    if (shoeType == ShoeType.none) return const SizedBox.shrink();
    
    final path = 'assets/avatars/shoes/shoe_sneaker.svg';
    return SvgPicture.asset(
      path,
      theme: SvgTheme(currentColor: theme.shoeColor),
    );
  }
}
