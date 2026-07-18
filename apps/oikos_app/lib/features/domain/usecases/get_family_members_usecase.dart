import '../entities/family_member.dart';
import '../repositories/family_repository.dart';

class GetFamilyMembersUseCase {
  final FamilyRepository repository;

  GetFamilyMembersUseCase(this.repository);

  Future<List<FamilyMember>> execute() async {
    return await repository.getFamilyMembers();
  }
}
