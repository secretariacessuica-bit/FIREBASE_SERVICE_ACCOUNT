import 'package:flutter/material.dart';

class AvatarGrid extends StatelessWidget {
  final String? selectedEmoji;
  final Function(String emoji, String colorHex) onAvatarSelected;

  const AvatarGrid({
    super.key,
    this.selectedEmoji,
    required this.onAvatarSelected,
  });

  static const List<Map<String, String>> _availableAvatars = [
    {'emoji': '😀', 'color': '#2196F3'},
    {'emoji': '😊', 'color': '#E91E63'},
    {'emoji': '😎', 'color': '#FF9800'},
    {'emoji': '👧', 'color': '#9C27B0'},
    {'emoji': '👦', 'color': '#4CAF50'},
    {'emoji': '👵', 'color': '#795548'},
    {'emoji': '👴', 'color': '#607D8B'},
    {'emoji': '🦊', 'color': '#FF5722'},
    {'emoji': '🐱', 'color': '#FFC107'},
    {'emoji': '🐶', 'color': '#8D6E63'},
    {'emoji': '🤖', 'color': '#9E9E9E'},
    {'emoji': '👽', 'color': '#00BCD4'},
  ];

  Color _hexToColor(String hexString) {
    final buffer = StringBuffer();
    if (hexString.length == 6 || hexString.length == 7) buffer.write('ff');
    buffer.write(hexString.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
      ),
      itemCount: _availableAvatars.length,
      itemBuilder: (context, index) {
        final avatar = _availableAvatars[index];
        final emoji = avatar['emoji']!;
        final colorHex = avatar['color']!;
        final bgColor = _hexToColor(colorHex);
        
        final isSelected = selectedEmoji == emoji;

        return GestureDetector(
          onTap: () => onAvatarSelected(emoji, colorHex),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            decoration: BoxDecoration(
              color: bgColor.withValues(alpha: isSelected ? 0.4 : 0.15),
              shape: BoxShape.circle,
              border: isSelected ? Border.all(color: bgColor, width: 3) : null,
              boxShadow: isSelected ? [
                BoxShadow(
                  color: bgColor.withValues(alpha: 0.4),
                  blurRadius: 12,
                  spreadRadius: 2,
                )
              ] : null,
            ),
            child: Center(
              child: Text(
                emoji,
                style: const TextStyle(fontSize: 32),
              ),
            ),
          ),
        );
      },
    );
  }
}
