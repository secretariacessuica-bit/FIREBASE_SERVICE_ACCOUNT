import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/family_member.dart';
import 'di_providers.dart';

final familyMembersProvider = FutureProvider<List<FamilyMember>>((ref) async {
  final useCase = ref.watch(getFamilyMembersUseCaseProvider);
  return await useCase.execute();
});
