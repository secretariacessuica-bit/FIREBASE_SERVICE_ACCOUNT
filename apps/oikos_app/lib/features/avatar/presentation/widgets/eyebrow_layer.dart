import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';

class EyebrowLayer extends StatelessWidget {
  final EyebrowType eyebrowType;

  const EyebrowLayer({super.key, required this.eyebrowType});

  @override
  Widget build(BuildContext context) {
    final path = 'assets/avatars/eyebrows/eyebrow_neutral.svg';
    return SvgPicture.asset(path);
  }
}
