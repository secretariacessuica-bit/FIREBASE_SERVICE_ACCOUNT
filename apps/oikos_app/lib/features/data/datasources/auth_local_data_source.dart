import 'package:hive/hive.dart';
import '../models/pin_data_model.dart';

class AuthLocalDataSource {
  final Box<PinDataModel> pinBox;

  AuthLocalDataSource(this.pinBox);

  Future<PinDataModel?> getPinData(String userId) async {
    return pinBox.get(userId);
  }

  Future<void> savePinData(PinDataModel data) async {
    await pinBox.put(data.userId, data);
  }
}
