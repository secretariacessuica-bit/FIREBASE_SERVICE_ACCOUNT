import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/memory_treasure.dart';
import '../pages/memory_viewer.dart';
import 'memory_cover.dart';

class MemoryCard extends StatelessWidget {
  final MemoryTreasure memory;

  const MemoryCard({
    super.key,
    required this.memory,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          PageRouteBuilder(
            pageBuilder: (context, animation, secondaryAnimation) => MemoryViewer(memory: memory),
            transitionsBuilder: (context, animation, secondaryAnimation, child) {
              return FadeTransition(opacity: animation, child: child);
            },
          ),
        );
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          MemoryCover(
            emoji: memory.coverEmoji,
            theme: memory.theme,
            size: 140,
          ),
          const SizedBox(height: 16),
          Text(
            memory.title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: AppColors.textPrimary,
              height: 1.2,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
