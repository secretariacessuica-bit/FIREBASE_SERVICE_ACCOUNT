import '../models/member_identity_model.dart';

abstract class ProfileLocalDataSource {
  Future<MemberIdentityModel?> getIdentity(String memberId);
  Future<void> saveIdentity(MemberIdentityModel identity);
}
