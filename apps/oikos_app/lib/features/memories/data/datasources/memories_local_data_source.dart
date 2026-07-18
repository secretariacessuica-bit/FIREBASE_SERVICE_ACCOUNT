import '../models/memory_treasure_model.dart';

abstract class MemoriesLocalDataSource {
  Future<List<MemoryTreasureModel>> getTreasures();
  Future<void> saveTreasure(MemoryTreasureModel treasure);
  Future<void> deleteTreasure(String id);
}
