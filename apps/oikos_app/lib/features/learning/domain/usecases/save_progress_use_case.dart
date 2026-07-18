import '../entities/user_progress.dart';
import '../repositories/progress_repository.dart';

class SaveProgressUseCase {
  final ProgressRepository repository;

  SaveProgressUseCase(this.repository);

  Future<void> call(UserProgress progress) async {
    await repository.saveUserProgress(progress);
  }
}
