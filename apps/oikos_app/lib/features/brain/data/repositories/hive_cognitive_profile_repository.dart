import 'package:hive/hive.dart';
import '../../domain/entities/cognitive_profile.dart';
import '../../domain/repositories/cognitive_profile_repository.dart';
import '../models/cognitive_profile_model.dart';

/// Hive-backed implementation of [CognitiveProfileRepository].
///
/// Stores one record per user, keyed by [userId].
/// Optimistic locking is implemented in-memory via a lock-and-compare check.
/// This is safe for a local, single-device Hive store.
class HiveCognitiveProfileRepository implements CognitiveProfileRepository {
  static const String _boxName = 'cognitive_profiles';

  HiveCognitiveProfileRepository(this._box);

  final Box<CognitiveProfileModel> _box;

  static Future<HiveCognitiveProfileRepository> open() async {
    final box = await Hive.openBox<CognitiveProfileModel>(_boxName);
    return HiveCognitiveProfileRepository(box);
  }

  @override
  Future<CognitiveProfile?> getProfile(String userId) async {
    final model = _box.get(userId);
    return model?.toEntity();
  }

  /// Saves [profile] atomically.
  ///
  /// Throws [CognitiveProfileConflictException] if the stored revision differs
  /// from [expectedRevision] — signalling that another session wrote first.
  @override
  Future<CognitiveProfile> save(
    CognitiveProfile profile, {
    required int expectedRevision,
  }) async {
    // Optimistic locking check.
    final existing = _box.get(profile.userId);
    final storedRevision = existing?.revision ?? 0;

    if (storedRevision != expectedRevision) {
      throw CognitiveProfileConflictException(
        userId: profile.userId,
        expectedRevision: expectedRevision,
      );
    }

    // Repository is the sole incrementer of revision.
    final persisted = profile.copyWith(revision: expectedRevision + 1);
    final model = CognitiveProfileModel.fromEntity(persisted);
    await _box.put(profile.userId, model);
    return persisted;
  }
}
