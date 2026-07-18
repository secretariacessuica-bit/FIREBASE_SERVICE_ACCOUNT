import '../entities/pin_data.dart';

abstract class AuthRepository {
  Future<PinData?> getPinData(String userId);
  Future<void> savePinData(PinData data);
  Future<bool> verifyPin(String userId, String inputPin);
}
