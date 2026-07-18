import '../../domain/entities/memory_treasure.dart';
import '../../domain/repositories/memory_repository.dart';
import '../datasources/memories_local_data_source.dart';
import '../models/memory_treasure_model.dart';

class MemoriesRepositoryImpl implements MemoryRepository {
  final MemoriesLocalDataSource localDataSource;

  MemoriesRepositoryImpl(this.localDataSource);

  @override
  Future<List<MemoryTreasure>> getTreasures() async {
    final models = await localDataSource.getTreasures();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<void> saveTreasure(MemoryTreasure treasure) async {
    final model = MemoryTreasureModel.fromEntity(treasure);
    await localDataSource.saveTreasure(model);
  }

  @override
  Future<void> deleteTreasure(String id, {required bool confirmed}) async {
    if (!confirmed) {
      throw Exception('Exclusão de tesouro negada. É necessária confirmação explícita.');
    }
    await localDataSource.deleteTreasure(id);
  }
}
