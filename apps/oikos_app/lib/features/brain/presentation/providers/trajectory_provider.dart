import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive/hive.dart';
import '../../data/models/learning_event_model.dart';
import '../../domain/repositories/trajectory_repository.dart';
import '../../data/repositories/hive_trajectory_repository.dart';

final trajectoryRepositoryProvider = Provider<TrajectoryRepository>((ref) {
  // O box deve ter sido aberto no main()
  final box = Hive.box<LearningEventModel>(HiveTrajectoryRepository.boxName);
  return HiveTrajectoryRepository(box);
});
