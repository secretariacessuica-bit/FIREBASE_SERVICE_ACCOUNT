import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive/hive.dart';
import '../../data/datasources/family_local_data_source.dart';
import '../../data/datasources/settings_local_data_source.dart';
import '../../data/datasources/auth_local_data_source.dart';
import '../../data/repositories/family_repository_impl.dart';
import '../../data/repositories/settings_repository_impl.dart';
import '../../data/repositories/auth_repository_impl.dart';
import '../../domain/repositories/family_repository.dart';
import '../../domain/repositories/settings_repository.dart';
import '../../domain/repositories/auth_repository.dart';
import '../../domain/usecases/get_family_members_usecase.dart';
import '../../domain/usecases/verify_pin_usecase.dart';
import '../../domain/usecases/bootstrap_app_usecase.dart';
import '../../onboarding/domain/submit_onboarding_usecase.dart';
import '../../../core/services/seed_service.dart';
import '../../../core/services/lumo/lumo_service.dart';
import '../../../core/services/lumo/greeting_engine.dart';
import '../../../core/services/lumo/motivation_engine.dart';
import '../../data/models/family_model.dart';
import '../../data/models/family_member_model.dart';
import '../../data/models/settings_model.dart';
import '../../data/models/pin_data_model.dart';
import '../../learning/data/horizon_gateway.dart';
import '../../learning/data/horizon_gateway_http_impl.dart';

// Data Sources
final familyLocalDataSourceProvider = Provider<FamilyLocalDataSource>((ref) {
  return FamilyLocalDataSource(
    Hive.box<FamilyModel>('familyBox'),
    Hive.box<FamilyMemberModel>('familyMembersBox'),
  );
});

final settingsLocalDataSourceProvider = Provider<SettingsLocalDataSource>((ref) {
  return SettingsLocalDataSource(Hive.box<SettingsModel>('settingsBox'));
});

final authLocalDataSourceProvider = Provider<AuthLocalDataSource>((ref) {
  return AuthLocalDataSource(Hive.box<PinDataModel>('pinDataBox'));
});

// Repositories
final familyRepositoryProvider = Provider<FamilyRepository>((ref) {
  return FamilyRepositoryImpl(ref.watch(familyLocalDataSourceProvider));
});

final settingsRepositoryProvider = Provider<SettingsRepository>((ref) {
  return SettingsRepositoryImpl(ref.watch(settingsLocalDataSourceProvider));
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(ref.watch(authLocalDataSourceProvider));
});

// Use Cases
final getFamilyMembersUseCaseProvider = Provider<GetFamilyMembersUseCase>((ref) {
  return GetFamilyMembersUseCase(ref.watch(familyRepositoryProvider));
});

final submitOnboardingUseCaseProvider = Provider<SubmitOnboardingUseCase>((ref) {
  return SubmitOnboardingUseCase(
    familyRepository: ref.read(familyRepositoryProvider),
    authRepository: ref.read(authRepositoryProvider),
    settingsRepository: ref.read(settingsRepositoryProvider),
  );
});

final verifyPinUseCaseProvider = Provider<VerifyPinUseCase>((ref) {
  return VerifyPinUseCase(ref.read(authRepositoryProvider));
});

final bootstrapAppUseCaseProvider = Provider<BootstrapAppUseCase>((ref) {
  return BootstrapAppUseCase(
    ref.watch(settingsRepositoryProvider),
    ref.watch(familyRepositoryProvider),
  );
});

// Services
final lumoServiceProvider = Provider<LumoService>((ref) {
  return LumoService(GreetingEngine(), MotivationEngine());
});

final seedServiceProvider = Provider<SeedService>((ref) {
  return SeedService(
    ref.watch(familyRepositoryProvider),
    ref.watch(settingsRepositoryProvider),
    ref.watch(authRepositoryProvider),
  );
});

// Gateways
final horizonGatewayProvider = Provider<HorizonGateway>((ref) {
  return HorizonGatewayHttpImpl();
});

