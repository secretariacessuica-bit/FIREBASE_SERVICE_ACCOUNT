import '../entities/user_progress.dart';
import '../services/progress_engine.dart';

class AwardXpUseCase {
  final ProgressEngine progressEngine;

  AwardXpUseCase(this.progressEngine);

  Future<UserProgress> call({
    required String userId,
    required String journeyId,
    required int xpAmount,
  }) async {
    return await progressEngine.awardXp(userId, journeyId, xpAmount);
  }
}
