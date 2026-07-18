import 'family_activity.dart';
import 'family_moment.dart';

abstract class FamilyRepository {
  Future<List<FamilyActivity>> getActivities();
  Future<List<FamilyMoment>> getMoments();
  Future<void> saveActivity(FamilyActivity activity);
  Future<void> saveMoment(FamilyMoment moment);
  Future<int> getDaysLearningTogether();
}
