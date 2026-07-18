import 'package:flutter/material.dart';
import '../../features/profiles/domain/entities/profile_theme.dart';

class AdaptiveTheme {
  final Color backgroundColor;
  final Color primaryColor;
  final Color onPrimaryColor;
  final Color surfaceColor;
  final Color onSurfaceColor;
  final Color highlightColor;
  
  final TextStyle headingStyle;
  final TextStyle bodyStyle;
  final BorderRadius buttonRadius;

  AdaptiveTheme({
    required this.backgroundColor,
    required this.primaryColor,
    required this.onPrimaryColor,
    required this.surfaceColor,
    required this.onSurfaceColor,
    required this.highlightColor,
    required this.headingStyle,
    required this.bodyStyle,
    required this.buttonRadius,
  });

  factory AdaptiveTheme.fromProfile(ProfileTheme theme) {
    switch (theme) {
      case ProfileTheme.playful: // Crianças (Sofia)
        return AdaptiveTheme(
          backgroundColor: const Color(0xFFFFF0F5),
          primaryColor: const Color(0xFFC55A7B),
          onPrimaryColor: Colors.white,
          surfaceColor: const Color(0xFFFFE4EE),
          onSurfaceColor: const Color(0xFFC55A7B),
          highlightColor: const Color(0xFF90D2A8),
          headingStyle: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, fontFamily: 'Comic Sans MS'), // Fonte arredondada
          bodyStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Color(0xFFC55A7B)),
          buttonRadius: BorderRadius.circular(32), // Botões Bolha
        );
      
      case ProfileTheme.gamified: // Jovens/Gamers (Lorenzo)
        return AdaptiveTheme(
          backgroundColor: const Color(0xFFE8F1FA),
          primaryColor: const Color(0xFF4A7DBC),
          onPrimaryColor: Colors.white,
          surfaceColor: const Color(0xFFE1EDFC),
          onSurfaceColor: const Color(0xFF4A7DBC),
          highlightColor: Colors.amber,
          headingStyle: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, letterSpacing: 1.5, fontFamily: 'Impact'), // Fonte display
          bodyStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF4A7DBC)),
          buttonRadius: BorderRadius.circular(8), // Bordas duras/Arcade
        );
      
      case ProfileTheme.formal: // Adultos (Papai)
      default:
        return AdaptiveTheme(
          backgroundColor: const Color(0xFFFBF8F1), // Off-white
          primaryColor: const Color(0xFF4A3E3D),
          onPrimaryColor: Colors.white,
          surfaceColor: const Color(0xFFE2EBE5),
          onSurfaceColor: const Color(0xFF4A3E3D),
          highlightColor: const Color(0xFF90D2A8), // Luma Green
          headingStyle: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, fontFamily: 'Georgia'), // Serifa
          bodyStyle: const TextStyle(fontSize: 16, color: Color(0xFF8C7E7C)),
          buttonRadius: BorderRadius.circular(12), // Utilitaŕio moderno
        );
    }
  }
}
