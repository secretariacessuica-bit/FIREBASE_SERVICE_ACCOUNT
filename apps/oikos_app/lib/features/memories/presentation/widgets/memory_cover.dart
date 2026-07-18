import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/memory_theme.dart';

class MemoryCover extends StatelessWidget {
  final String emoji;
  final MemoryTheme theme;
  final double size;

  const MemoryCover({
    super.key,
    required this.emoji,
    required this.theme,
    this.size = 120,
  });

  Color _getThemeColor() {
    switch (theme) {
      case MemoryTheme.gratitude: return Colors.orange;
      case MemoryTheme.discovery: return Colors.blue;
      case MemoryTheme.courage: return Colors.redAccent;
      case MemoryTheme.family: return Colors.purple;
      case MemoryTheme.kindness: return Colors.pink;
      case MemoryTheme.learning: return Colors.green;
    }
  }

  @override
  Widget build(BuildContext context) {
    final themeColor = _getThemeColor();
    
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: themeColor.withOpacity(0.15),
        borderRadius: BorderRadius.circular(size * 0.2),
        border: Border.all(color: themeColor.withOpacity(0.4), width: 2),
        boxShadow: [
          BoxShadow(
            color: themeColor.withOpacity(0.1),
            blurRadius: size * 0.2,
            offset: Offset(0, size * 0.1),
          ),
        ],
      ),
      child: Center(
        child: Text(
          emoji,
          style: TextStyle(fontSize: size * 0.45),
        ),
      ),
    );
  }
}
