import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'di_providers.dart';

enum AppBootstrapState {
  loading,
  needsOnboarding,
  needsAvatarSelection,
  needsPin,
  authenticated,
}

class AppBootstrapNotifier extends StateNotifier<AppBootstrapState> {
  final Ref ref;

  AppBootstrapNotifier(this.ref) : super(AppBootstrapState.loading) {
    _init();
  }

  Future<void> _init() async {
    print("BOOTSTRAP: _init started");
    try {
      final bootstrapUseCase = ref.read(bootstrapAppUseCaseProvider);
      final seedService = ref.read(seedServiceProvider);

      print("BOOTSTRAP: calling isFirstLaunch");
      final isFirstLaunch = await bootstrapUseCase.isFirstLaunch();
      print("BOOTSTRAP: isFirstLaunch = \$isFirstLaunch");
      
      if (isFirstLaunch) {
        // The populate method checks USE_SEED internally
        print("BOOTSTRAP: calling seedIfEmpty");
        await seedService.seedIfEmpty();
        print("BOOTSTRAP: seedIfEmpty finished");
      }
      
      print("BOOTSTRAP: calling hasFamily");
      final hasFamily = await bootstrapUseCase.hasFamily();
      print("BOOTSTRAP: hasFamily = \$hasFamily");
      if (!hasFamily) {
        print("BOOTSTRAP: changing state to needsOnboarding");
        state = AppBootstrapState.needsOnboarding;
      } else {
        print("BOOTSTRAP: changing state to needsAvatarSelection");
        state = AppBootstrapState.needsAvatarSelection;
      }
    } catch (e, stack) {
      print("BOOTSTRAP ERROR: \$e\\n\$stack");
    }
  }

  void completeOnboarding() {
    state = AppBootstrapState.needsAvatarSelection;
  }

  void selectAvatar() {
    state = AppBootstrapState.needsPin;
  }

  void authenticate() {
    state = AppBootstrapState.authenticated;
  }
}

final appBootstrapProvider = StateNotifierProvider<AppBootstrapNotifier, AppBootstrapState>((ref) {
  return AppBootstrapNotifier(ref);
});
