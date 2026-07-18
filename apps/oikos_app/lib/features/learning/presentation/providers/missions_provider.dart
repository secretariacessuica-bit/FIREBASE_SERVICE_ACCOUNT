import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/horizon_gateway.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../../presentation/providers/di_providers.dart';
import '../../../data/models/family_member_model.dart';

final missionsProvider = FutureProvider.family<List<DailyMission>, String>((ref, memberId) async {
  final gateway = ref.watch(horizonGatewayProvider);
  final memberBox = await ref.read(familyLocalDataSourceProvider).getFamilyMembers();
  final member = memberBox.firstWhere((m) => m.id == memberId);
  final theme = ProfileTheme.gamified; // TODO: Adicionar theme ao FamilyMemberModel no futuro
  
  return await gateway.getDailyPacing(memberId, theme: theme);
});
