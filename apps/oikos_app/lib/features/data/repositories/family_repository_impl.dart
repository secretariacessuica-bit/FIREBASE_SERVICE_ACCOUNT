import '../../domain/entities/family.dart';
import '../../domain/entities/family_member.dart';
import '../../domain/repositories/family_repository.dart';
import '../datasources/family_local_data_source.dart';
import '../models/family_model.dart';
import '../models/family_member_model.dart';

class FamilyRepositoryImpl implements FamilyRepository {
  final FamilyLocalDataSource localDataSource;

  FamilyRepositoryImpl(this.localDataSource);

  @override
  Future<Family?> getFamily() async {
    final model = await localDataSource.getFamily();
    return model?.toEntity();
  }

  @override
  Future<void> saveFamily(Family family) async {
    final model = FamilyModel.fromEntity(family);
    await localDataSource.saveFamily(model);
  }

  @override
  Future<List<FamilyMember>> getFamilyMembers() async {
    final models = await localDataSource.getFamilyMembers();
    return models.map((m) => m.toEntity()).toList();
  }

  @override
  Future<void> saveFamilyMember(FamilyMember member) async {
    final model = FamilyMemberModel.fromEntity(member);
    await localDataSource.saveFamilyMember(model);
  }

  @override
  Future<void> saveFamilyMembers(List<FamilyMember> members) async {
    final models = members.map((m) => FamilyMemberModel.fromEntity(m)).toList();
    await localDataSource.saveFamilyMembers(models);
  }

  @override
  Future<FamilyMember?> getFamilyMemberById(String id) async {
    final model = await localDataSource.getFamilyMemberById(id);
    return model?.toEntity();
  }
}
