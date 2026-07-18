import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';

class MouthLayer extends StatelessWidget {
  final MouthType mouthType;

  const MouthLayer({super.key, required this.mouthType});

  @override
  Widget build(BuildContext context) {
    final path = 'assets/avatars/mouths/mouth_smile.svg';
    return SvgPicture.asset(path);
  }
}
