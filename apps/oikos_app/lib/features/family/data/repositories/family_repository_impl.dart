import '../../domain/entities/family_activity.dart';
import '../../domain/entities/family_moment.dart';
import '../../domain/repositories/family_repository.dart';
import '../datasources/family_local_data_source.dart';
import '../models/family_activity_model.dart';
import '../models/family_moment_model.dart';

class FamilyRepositoryImpl implements FamilyRepository {
  final FamilyLocalDataSource localDataSource;

  FamilyRepositoryImpl(this.localDataSource);

  @override
  Future<List<FamilyActivity>> getActivities() async {
    final models = await localDataSource.getActivities();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<List<FamilyMoment>> getMoments() async {
    final models = await localDataSource.getMoments();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<void> saveActivity(FamilyActivity activity) async {
    final model = FamilyActivityModel.fromEntity(activity);
    await localDataSource.saveActivity(model);
  }

  @override
  Future<void> saveMoment(FamilyMoment moment) async {
    final model = FamilyMomentModel.fromEntity(moment);
    await localDataSource.saveMoment(model);
  }

  @override
  Future<int> getDaysLearningTogether() async {
    return await localDataSource.getDaysLearningTogether();
  }
}
