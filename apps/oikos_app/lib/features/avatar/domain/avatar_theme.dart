import 'package:flutter/material.dart';

class AvatarTheme {
  final Color skinColor;
  final Color hairColor;
  final Color shirtColor;
  final Color pantsColor;
  final Color shoeColor;

  const AvatarTheme({
    required this.skinColor,
    required this.hairColor,
    required this.shirtColor,
    required this.pantsColor,
    required this.shoeColor,
  });

  factory AvatarTheme.defaultTheme() {
    return const AvatarTheme(
      skinColor: Color(0xFFFFE0BD), // Tom de pele claro padrão
      hairColor: Color(0xFF4A3B32), // Castanho escuro
      shirtColor: Color(0xFF88B04B), // Verde Oikos
      pantsColor: Color(0xFF333333), // Cinza escuro
      shoeColor: Color(0xFF8B4513), // Marrom
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'skinColor': skinColor.value.toRadixString(16).padLeft(8, '0'),
      'hairColor': hairColor.value.toRadixString(16).padLeft(8, '0'),
      'shirtColor': shirtColor.value.toRadixString(16).padLeft(8, '0'),
      'pantsColor': pantsColor.value.toRadixString(16).padLeft(8, '0'),
      'shoeColor': shoeColor.value.toRadixString(16).padLeft(8, '0'),
    };
  }

  factory AvatarTheme.fromJson(Map<String, dynamic> json) {
    return AvatarTheme(
      skinColor: Color(int.parse(json['skinColor'] as String, radix: 16)),
      hairColor: Color(int.parse(json['hairColor'] as String, radix: 16)),
      shirtColor: Color(int.parse(json['shirtColor'] as String, radix: 16)),
      pantsColor: Color(int.parse(json['pantsColor'] as String, radix: 16)),
      shoeColor: Color(int.parse(json['shoeColor'] as String, radix: 16)),
    );
  }
}
