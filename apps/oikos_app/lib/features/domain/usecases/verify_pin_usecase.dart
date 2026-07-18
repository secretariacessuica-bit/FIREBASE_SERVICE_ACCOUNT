import '../repositories/auth_repository.dart';

class VerifyPinUseCase {
  final AuthRepository repository;

  VerifyPinUseCase(this.repository);

  Future<bool> execute(String userId, String inputPin) async {
    return await repository.verifyPin(userId, inputPin);
  }
}
