import '../../domain/entities/pin_data.dart';
import '../../domain/repositories/auth_repository.dart';
import '../datasources/auth_local_data_source.dart';
import '../models/pin_data_model.dart';

class AuthRepositoryImpl implements AuthRepository {
  final AuthLocalDataSource localDataSource;

  AuthRepositoryImpl(this.localDataSource);

  @override
  Future<PinData?> getPinData(String userId) async {
    final model = await localDataSource.getPinData(userId);
    return model?.toEntity();
  }

  @override
  Future<void> savePinData(PinData data) async {
    final model = PinDataModel.fromEntity(data);
    await localDataSource.savePinData(model);
  }

  @override
  Future<bool> verifyPin(String userId, String inputPin) async {
    final pinData = await getPinData(userId);
    if (pinData == null) return false;
    
    // In a real app we would hash inputPin and compare. For now we compare strings.
    if (pinData.hashedPin == inputPin) {
      return true;
    } else {
      // Increase failed attempts
      final newData = PinData(
        userId: pinData.userId,
        hashedPin: pinData.hashedPin,
        failedAttempts: pinData.failedAttempts + 1,
        // lock logic here if needed
      );
      await savePinData(newData);
      return false;
    }
  }
}
