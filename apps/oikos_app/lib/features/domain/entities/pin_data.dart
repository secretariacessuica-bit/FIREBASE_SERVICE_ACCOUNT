class PinData {
  final String userId;
  final String hashedPin; // In a real app this would be hashed. For now we can just store the string.
  final int failedAttempts;
  final DateTime? lockedUntil;

  const PinData({
    required this.userId,
    required this.hashedPin,
    this.failedAttempts = 0,
    this.lockedUntil,
  });
}
