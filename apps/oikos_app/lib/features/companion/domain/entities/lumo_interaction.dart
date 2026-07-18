class LumoInteraction {
  final String id;
  final String message;
  final DateTime timestamp;
  final bool isSpontaneous; // Identifica se foi um Whisper ou uma resposta

  const LumoInteraction({
    required this.id,
    required this.message,
    required this.timestamp,
    this.isSpontaneous = false,
  });
}
