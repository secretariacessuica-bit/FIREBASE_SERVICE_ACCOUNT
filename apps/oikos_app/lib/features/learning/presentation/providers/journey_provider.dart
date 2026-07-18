import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/journey.dart';
import '../../domain/usecases/get_journeys_use_case.dart';
import '../../data/content/french_seed.dart';

// Assuming we have a provider for GetJourneysUseCase somewhere in the app, 
// for now we'll create a placeholder or inject it.
// This provider holds the list of available journeys.

class JourneyNotifier extends AsyncNotifier<List<Journey>> {
  @override
  Future<List<Journey>> build() async {
    // Retorna a Journey de Francês inicial
    return [frenchSeed];
  }

  Future<void> loadJourneys(GetJourneysUseCase useCase) async {
    state = const AsyncValue.loading();
    try {
      final journeys = await useCase();
      state = AsyncValue.data(journeys);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}

final journeyProvider = AsyncNotifierProvider<JourneyNotifier, List<Journey>>(() {
  return JourneyNotifier();
});
