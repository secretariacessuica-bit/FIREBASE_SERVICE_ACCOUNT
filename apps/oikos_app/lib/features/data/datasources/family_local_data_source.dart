import 'package:hive/hive.dart';
import '../models/family_model.dart';
import '../models/family_member_model.dart';

class FamilyLocalDataSource {
  final Box<FamilyModel> familyBox;
  final Box<FamilyMemberModel> membersBox;

  FamilyLocalDataSource(this.familyBox, this.membersBox);

  Future<FamilyModel?> getFamily() async {
    if (familyBox.isEmpty) return null;
    return familyBox.getAt(0);
  }

  Future<void> saveFamily(FamilyModel family) async {
    await familyBox.put(0, family);
  }

  Future<List<FamilyMemberModel>> getFamilyMembers() async {
    return membersBox.values.toList();
  }

  Future<void> saveFamilyMember(FamilyMemberModel member) async {
    await membersBox.put(member.id, member);
  }

  Future<void> saveFamilyMembers(List<FamilyMemberModel> members) async {
    final Map<String, FamilyMemberModel> map = {};
    for (var m in members) {
      map[m.id] = m;
    }
    await membersBox.putAll(map);
  }

  Future<FamilyMemberModel?> getFamilyMemberById(String id) async {
    return membersBox.get(id);
  }
}
