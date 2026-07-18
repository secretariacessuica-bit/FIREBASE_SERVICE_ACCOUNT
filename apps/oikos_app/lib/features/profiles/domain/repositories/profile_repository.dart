import 'member_identity.dart';

abstract class ProfileRepository {
  Future<MemberIdentity?> getIdentity(String memberId);
  Future<void> saveIdentity(MemberIdentity identity);
}
