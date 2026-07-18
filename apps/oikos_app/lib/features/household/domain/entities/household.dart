class HouseholdProfile {
  final String name;
  final String? coverImageUrl;
  final DateTime establishedAt;

  const HouseholdProfile({
    required this.name,
    this.coverImageUrl,
    required this.establishedAt,
  });
}

class HouseholdMembers {
  final List<String> memberIds;
  final String ownerId;

  const HouseholdMembers({
    required this.memberIds,
    required this.ownerId,
  });
}

class HouseholdSettings {
  final String primaryLanguage;
  final String timezone;

  const HouseholdSettings({
    this.primaryLanguage = 'pt-BR',
    this.timezone = 'America/Sao_Paulo',
  });
}

class Household {
  final String id;
  final HouseholdProfile profile;
  final HouseholdMembers members;
  final HouseholdSettings settings;
  final DateTime lastUpdated;

  const Household({
    required this.id,
    required this.profile,
    required this.members,
    required this.settings,
    required this.lastUpdated,
  });
}
