import 'enduring_value.dart';

class HouseholdLegacy {
  final String householdId;
  final List<EnduringValue> enduringValues;
  final String legacyReflection;
  final DateTime lastReviewedAt;
  final DateTime createdAt;

  HouseholdLegacy({
    required this.householdId,
    required this.enduringValues,
    required this.legacyReflection,
    required this.lastReviewedAt,
    required this.createdAt,
  });

  HouseholdLegacy addOrUpdateValue(EnduringValue value) {
    final existingIndex = enduringValues.indexWhere((v) => v.valueId == value.valueId);
    final newValues = List<EnduringValue>.from(enduringValues);
    
    if (existingIndex >= 0) {
      newValues[existingIndex] = value;
    } else {
      newValues.add(value);
    }

    return HouseholdLegacy(
      householdId: householdId,
      enduringValues: newValues,
      legacyReflection: legacyReflection,
      lastReviewedAt: DateTime.now(),
      createdAt: createdAt,
    );
  }
}
