enum CommunityType {
  family,
  work,
  friends,
  school
}

class CommunityGuardian {
  final String guardianId;
  final String communityId;
  final String name; // Ex: Luma, Atlas, Bolt, Nori
  final CommunityType type;
  final int collectiveLevel;
  final int collectiveXp;
  final double communityHunger; // Média da alimentação de todos os membros

  const CommunityGuardian({
    required this.guardianId,
    required this.communityId,
    required this.name,
    required this.type,
    this.collectiveLevel = 1,
    this.collectiveXp = 0,
    this.communityHunger = 0.5,
  });

  CommunityGuardian addCollectiveEffort(int effortPoints) {
    final newXp = collectiveXp + effortPoints;
    final newLevel = collectiveLevel + (newXp ~/ 500); // Luma precisa de muito XP da comunidade para upar

    return CommunityGuardian(
      guardianId: guardianId,
      communityId: communityId,
      name: name,
      type: type,
      collectiveLevel: newLevel,
      collectiveXp: newXp % 500,
      communityHunger: (communityHunger + 0.1).clamp(0.0, 1.0),
    );
  }
}
