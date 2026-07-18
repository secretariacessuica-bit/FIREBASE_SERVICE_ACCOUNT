import 'dart:async';
import 'package:flutter/foundation.dart';

enum SyncTrigger {
  appOpen,
  networkRestored,
  periodic,
  manual,
}

class SyncScheduler {
  final VoidCallback onSyncRequested;
  Timer? _periodicTimer;

  SyncScheduler({required this.onSyncRequested});

  void start() {
    _periodicTimer = Timer.periodic(const Duration(minutes: 15), (timer) {
      trigger(SyncTrigger.periodic);
    });
  }

  void stop() {
    _periodicTimer?.cancel();
  }

  void trigger(SyncTrigger trigger) {
    debugPrint('SyncScheduler: Sincronização disparada por ${trigger.name}');
    onSyncRequested();
  }

  void notifyNetworkRestored() {
    trigger(SyncTrigger.networkRestored);
  }
}
