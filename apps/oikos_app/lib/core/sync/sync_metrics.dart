class SyncMetrics {
  final int mutationsPending;
  final int mutationsProcessed;
  final int conflictsResolved;
  final Duration averageSyncTime;
  final int successfulSyncs;
  final int failedSyncs;

  const SyncMetrics({
    this.mutationsPending = 0,
    this.mutationsProcessed = 0,
    this.conflictsResolved = 0,
    this.averageSyncTime = Duration.zero,
    this.successfulSyncs = 0,
    this.failedSyncs = 0,
  });

  SyncMetrics recordSuccess(int mutationsCount, int conflictsCount, Duration duration) {
    return SyncMetrics(
      mutationsPending: 0,
      mutationsProcessed: mutationsProcessed + mutationsCount,
      conflictsResolved: conflictsResolved + conflictsCount,
      averageSyncTime: _calculateNewAverage(duration),
      successfulSyncs: successfulSyncs + 1,
      failedSyncs: failedSyncs,
    );
  }

  SyncMetrics recordFailure() {
    return SyncMetrics(
      mutationsPending: mutationsPending,
      mutationsProcessed: mutationsProcessed,
      conflictsResolved: conflictsResolved,
      averageSyncTime: averageSyncTime,
      successfulSyncs: successfulSyncs,
      failedSyncs: failedSyncs + 1,
    );
  }

  Duration _calculateNewAverage(Duration newDuration) {
    if (successfulSyncs == 0) return newDuration;
    final totalMicroseconds = (averageSyncTime.inMicroseconds * successfulSyncs) + newDuration.inMicroseconds;
    return Duration(microseconds: totalMicroseconds ~/ (successfulSyncs + 1));
  }
}
