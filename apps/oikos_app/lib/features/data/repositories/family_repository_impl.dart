import '../../domain/entities/family.dart';
import '../../domain/entities/family_member.dart';
import '../../domain/repositories/family_repository.dart';
import '../datasources/family_local_data_source.dart';
import '../models/family_model.dart';
import '../models/family_member_model.dart';

class FamilyRepositoryImpl implements FamilyRepository {
  final FamilyLocalDataSource localDataSource;

  FamilyRepositoryImpl(this.localDataSource);

  /// Normaliza o avatarAsset: se estiver URL-encoded (%7B...), converte para JSON puro ({...).
  /// Isso resolve dados legados que foram armazenados com URL encoding incorreto.
  FamilyMember _normalizeMember(FamilyMember member) {
    final asset = member.avatarAsset;
    if (asset == null || asset.isEmpty) return member;
    // Se começa com % (URL-encoded JSON), decodifica para JSON puro
    if (asset.startsWith('%7B') || asset.startsWith('%7b')) {
      try {
        final decoded = Uri.decodeComponent(asset);
        return member.copyWith(avatarAsset: decoded);
      } catch (_) {
        // Falhou ao decodificar — retorna como está
      }
    }
    return member;
  }

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
    return models.map((m) => _normalizeMember(m.toEntity())).toList();
  }

  @override
  Future<void> saveFamilyMember(FamilyMember member) async {
    // Também normaliza ao salvar para garantir dados limpos no banco
    final normalized = _normalizeMember(member);
    final model = FamilyMemberModel.fromEntity(normalized);
    await localDataSource.saveFamilyMember(model);
  }

  @override
  Future<void> saveFamilyMembers(List<FamilyMember> members) async {
    final normalized = members.map(_normalizeMember).toList();
    final models = normalized.map((m) => FamilyMemberModel.fromEntity(m)).toList();
    await localDataSource.saveFamilyMembers(models);
  }

  @override
  Future<FamilyMember?> getFamilyMemberById(String id) async {
    final model = await localDataSource.getFamilyMemberById(id);
    if (model == null) return null;
    return _normalizeMember(model.toEntity());
  }
}
