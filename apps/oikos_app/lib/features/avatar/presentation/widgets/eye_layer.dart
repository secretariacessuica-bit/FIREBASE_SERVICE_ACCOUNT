import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/avatar_parts.dart';

class EyeLayer extends StatelessWidget {
  final EyeType eyeType;

  const EyeLayer({super.key, required this.eyeType});

  @override
  Widget build(BuildContext context) {
    // We map enum to asset. Placeholder for now.
    final path = 'assets/avatars/eyes/eye_default.svg';
    
    return SvgPicture.asset(path);
  }
}
