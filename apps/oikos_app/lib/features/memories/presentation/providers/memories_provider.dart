import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/memory_treasure.dart';
import '../../data/content/memories_demo_data.dart';

final memoriesProvider = Provider<List<MemoryTreasure>>((ref) {
  return MemoriesDemoData.getTreasures();
});
