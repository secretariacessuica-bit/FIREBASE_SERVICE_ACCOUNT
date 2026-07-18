import 'memory_treasure.dart';

abstract class MemoryRepository {
  Future<List<MemoryTreasure>> getTreasures();
  Future<void> saveTreasure(MemoryTreasure treasure);
  Future<void> deleteTreasure(String id, {required bool confirmed});
}
