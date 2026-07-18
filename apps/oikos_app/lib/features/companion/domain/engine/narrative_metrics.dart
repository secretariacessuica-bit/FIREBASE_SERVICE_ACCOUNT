class OperationalMetrics {
  int fallbackCount = 0;
  int hallucinationBlocks = 0;
  int rejectedResponses = 0;
  int timeouts = 0;

  void recordFallback() => fallbackCount++;
  void recordHallucination() => hallucinationBlocks++;
  void recordRejection() => rejectedResponses++;
  void recordTimeout() => timeouts++;
}

class RelationshipMetrics {
  int conversationContinuationRate = 0;
  int insightAcceptanceRate = 0;
  int whisperOpenRate = 0;
  int averageMemoryReferences = 0;
  int returnVisitsAfterWhisper = 0;
  int reflectionReadTime = 0;

  void recordContinuation() => conversationContinuationRate++;
  void recordInsightAccepted() => insightAcceptanceRate++;
  void recordWhisperOpened() => whisperOpenRate++;
}

class NarrativeMetrics {
  final OperationalMetrics operational = OperationalMetrics();
  final RelationshipMetrics relationship = RelationshipMetrics();
  
  static final NarrativeMetrics instance = NarrativeMetrics._internal();
  NarrativeMetrics._internal();
}
