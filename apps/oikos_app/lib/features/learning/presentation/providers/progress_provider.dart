import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/user_progress.dart';

class ProgressNotifier extends Notifier<UserProgress?> {
  @override
  UserProgress? build() {
    return null;
  }

  void loadProgress(UserProgress progress) {
    state = progress;
  }

  void updateProgress(UserProgress updated) {
    state = updated;
  }
}

final progressProvider = NotifierProvider<ProgressNotifier, UserProgress?>(() {
  return ProgressNotifier();
});
