import '../entities/family.dart';
import '../entities/family_member.dart';

abstract class FamilyRepository {
  Future<Family?> getFamily();
  Future<void> saveFamily(Family family);
  
  Future<List<FamilyMember>> getFamilyMembers();
  Future<void> saveFamilyMember(FamilyMember member);
  Future<void> saveFamilyMembers(List<FamilyMember> members);
  
  Future<FamilyMember?> getFamilyMemberById(String id);
}
