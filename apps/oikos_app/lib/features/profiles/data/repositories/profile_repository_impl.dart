import '../../domain/entities/member_identity.dart';
import '../../domain/repositories/profile_repository.dart';
import '../datasources/profile_local_data_source.dart';
import '../models/member_identity_model.dart';

class ProfileRepositoryImpl implements ProfileRepository {
  final ProfileLocalDataSource localDataSource;

  ProfileRepositoryImpl(this.localDataSource);

  @override
  Future<MemberIdentity?> getIdentity(String memberId) async {
    final model = await localDataSource.getIdentity(memberId);
    return model?.toEntity();
  }

  @override
  Future<void> saveIdentity(MemberIdentity identity) async {
    final model = MemberIdentityModel.fromEntity(identity);
    await localDataSource.saveIdentity(model);
  }
}
