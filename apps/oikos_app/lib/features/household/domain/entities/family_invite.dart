class FamilyInvite {
  final String householdId;
  final String shortCode; // Ex: 784-219
  final String secureLink; // Ex: https://oikos.app/join/xyz
  final DateTime expiresAt;

  const FamilyInvite({
    required this.householdId,
    required this.shortCode,
    required this.secureLink,
    required this.expiresAt,
  });

  bool get isExpired => DateTime.now().isAfter(expiresAt);
}
