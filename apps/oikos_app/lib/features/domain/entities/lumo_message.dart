class LumoMessage {
  final String text;
  final DateTime timestamp;
  final String type; // e.g. "greeting", "motivation", "warning"

  const LumoMessage({
    required this.text,
    required this.timestamp,
    required this.type,
  });
}
