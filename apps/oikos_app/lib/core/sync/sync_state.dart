enum SyncStatus {
  idle,
  preparing,
  syncing,
  waitingConnection,
  completed,
  error,
}

class SyncState {
  final SyncStatus status;
  final String userMessage;
  final DateTime lastSync;

  const SyncState({
    this.status = SyncStatus.idle,
    this.userMessage = 'Tudo está em seu lugar.',
    required this.lastSync,
  });

  SyncState copyWith({
    SyncStatus? status,
    String? userMessage,
    DateTime? lastSync,
  }) {
    return SyncState(
      status: status ?? this.status,
      userMessage: userMessage ?? this.userMessage,
      lastSync: lastSync ?? this.lastSync,
    );
  }
}
