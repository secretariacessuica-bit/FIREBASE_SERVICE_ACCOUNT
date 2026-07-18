import '../models/family_activity_model.dart';
import '../models/family_moment_model.dart';

abstract class FamilyLocalDataSource {
  Future<List<FamilyActivityModel>> getActivities();
  Future<List<FamilyMomentModel>> getMoments();
  Future<void> saveActivity(FamilyActivityModel activity);
  Future<void> saveMoment(FamilyMomentModel moment);
  Future<int> getDaysLearningTogether();
}
