import 'dart:async';
import 'mutation_queue.dart';
import 'conflict_resolver.dart';
import 'sync_state.dart';
import 'sync_metrics.dart';
import 'sync_scheduler.dart';
import '../network/remote_data_source.dart';

class SyncCoordinator {
  final MutationQueue mutationQueue;
  final ConflictResolver conflictResolver;
  final RemoteDataSource remoteDataSource;
  late final SyncScheduler scheduler;

  SyncState _currentState = SyncState(lastSync: DateTime.now());
  SyncMetrics _metrics = const SyncMetrics();
  
  final _stateController = StreamController<SyncState>.broadcast();
  Stream<SyncState> get stateStream => _stateController.stream;
  SyncState get currentState => _currentState;
  SyncMetrics get metrics => _metrics;

  SyncCoordinator({
    required this.mutationQueue,
    required this.conflictResolver,
    required this.remoteDataSource,
  }) {
    scheduler = SyncScheduler(onSyncRequested: _performSync);
    scheduler.start();
  }

  void _updateState(SyncStatus status, String message) {
    _currentState = _currentState.copyWith(
      status: status,
      userMessage: message,
    );
    _stateController.add(_currentState);
  }

  Future<void> _performSync() async {
    if (_currentState.status == SyncStatus.syncing) return;

    final stopwatch = Stopwatch()..start();
    _updateState(SyncStatus.preparing, 'Organizando as novidades da família...');

    try {
      final pendingMutations = await mutationQueue.peekPending();
      if (pendingMutations.isEmpty) {
        _updateState(SyncStatus.completed, 'Tudo está em seu lugar.');
        _currentState = _currentState.copyWith(lastSync: DateTime.now());
        return;
      }

      _updateState(SyncStatus.syncing, 'Guardando as últimas histórias...');
      
      // Converte mutations para payload do RemoteDataSource
      final payloads = pendingMutations.map((m) => {
        'id': m.id,
        'entityId': m.entityId,
        'collection': m.collectionName,
        'type': m.type.name,
        'payload': m.payload,
        'timestamp': m.timestamp.toIso8601String(),
      }).toList();

      await remoteDataSource.pushMutations(payloads);
      await mutationQueue.remove(pendingMutations.map((e) => e.id).toList());

      stopwatch.stop();
      _metrics = _metrics.recordSuccess(pendingMutations.length, 0, stopwatch.elapsed);
      
      _currentState = _currentState.copyWith(lastSync: DateTime.now());
      _updateState(SyncStatus.completed, 'Tudo está em seu lugar.');

    } catch (e) {
      stopwatch.stop();
      _metrics = _metrics.recordFailure();
      _updateState(SyncStatus.waitingConnection, 'Aguardando conexão para atualizar o lar...');
    }
  }

  void dispose() {
    scheduler.stop();
    _stateController.close();
  }
}
