import 'package:flutter/material.dart';

class AppTheme {
  // Cores de Design System conforme protótipo oficial
  static const Color primaryGreen = Color(0xFF1E7E34); // Verde institucional
  static const Color institutionalBlue = Color(0xFF1E7E34); // Alias para compatibilidade
  static const Color mathGreen = Color(0xFF1E7E34); // Alias para compatibilidade
  static const Color darkSidebar = Color(0xFF111827); // Dark Blue / Slate da Sidebar
  static const Color backgroundLight = Color(0xFFF9FAFB); // Fundo cinza ultraleve
  static const Color textDark = Color(0xFF1F2937); // Texto principal
  static const Color textMuted = Color(0xFF6B7280); // Texto secundário
  static const Color cardBorder = Color(0xFFE5E7EB); // Bordas sutis
  static const Color excludeRed = Color(0xFFDC2626);

  static ThemeData get lightTheme {
    return ThemeData(
      brightness: Brightness.light,
      primaryColor: primaryGreen,
      colorScheme: const ColorScheme.light(
        primary: primaryGreen,
        secondary: primaryGreen,
        surface: Colors.white,
      ),
      scaffoldBackgroundColor: backgroundLight,
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: textDark,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        titleTextStyle: TextStyle(
          color: textDark,
          fontSize: 18,
          fontWeight: FontWeight.bold,
        ),
        iconTheme: IconThemeData(color: textDark),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryGreen,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
          textStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: cardBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: cardBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: primaryGreen, width: 2),
        ),
        labelStyle: const TextStyle(color: textMuted),
      ),
      textTheme: const TextTheme(
        titleLarge: TextStyle(fontWeight: FontWeight.bold, color: textDark),
        bodyLarge: TextStyle(color: textDark),
      ),
    );
  }
}
