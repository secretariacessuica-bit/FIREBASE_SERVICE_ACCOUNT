import 'household.dart';
import 'family_invite.dart';

abstract class HouseholdRepository {
  Future<Household?> getHousehold();
  Future<void> saveHousehold(Household household);
  Future<FamilyInvite> generateInvite();
  Future<void> joinWithCode(String shortCode);
  Future<void> joinWithLink(String secureLink);
  Future<void> resolvePendingMerge(String fieldName, dynamic chosenValue);
}
