import 'package:flutter/material.dart';
import '../../domain/entities/memory_treasure.dart';
import 'memory_card.dart';

class TreasureGrid extends StatelessWidget {
  final List<MemoryTreasure> treasures;

  const TreasureGrid({
    super.key,
    required this.treasures,
  });

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: treasures.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 32,
        crossAxisSpacing: 16,
        childAspectRatio: 0.75,
      ),
      itemBuilder: (context, index) {
        return MemoryCard(memory: treasures[index]);
      },
    );
  }
}
