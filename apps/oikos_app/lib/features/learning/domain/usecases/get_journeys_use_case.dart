import '../entities/journey.dart';
import '../repositories/journey_repository.dart';

class GetJourneysUseCase {
  final JourneyRepository repository;

  GetJourneysUseCase(this.repository);

  Future<List<Journey>> call() async {
    return await repository.getJourneys();
  }
}
