import '../entities/user_progress.dart';
import '../services/progress_engine.dart';
import '../repositories/progress_repository.dart';

class ProgressEngineImpl implements ProgressEngine {
  final ProgressRepository progressRepository;

  ProgressEngineImpl(this.progressRepository);

  @override
  Future<UserProgress> awardXp(String userId, String journeyId, int xpAmount) async {
    // 1. Fetch current progress
    UserProgress current = await progressRepository.getUserProgress(userId, journeyId);

    // 2. Add XP
    final newTotalXp = current.totalXpEarned + xpAmount;

    // 3. Update entity
    final updated = current.copyWith(totalXpEarned: newTotalXp);

    // 4. Save
    await progressRepository.saveUserProgress(updated);

    return updated;
  }

  @override
  Future<UserProgress> getProgress(String userId, String journeyId) async {
    return await progressRepository.getUserProgress(userId, journeyId);
  }
}
