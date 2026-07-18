import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/entities/member_identity.dart';
import '../../data/content/profile_demo_data.dart';

class ProfileNotifier extends Notifier<MemberIdentity?> {
  @override
  MemberIdentity? build() {
    // Load Pedro's identity for the demo
    return ProfileDemoData.getPedroIdentity();
  }

  // Future methods to update expression, add memory, etc.
}

final profileProvider = NotifierProvider<ProfileNotifier, MemberIdentity?>(() {
  return ProfileNotifier();
});

// A derived provider to calculate the favorite journey based on mock analytics/progress
final favoriteJourneyProvider = Provider<String>((ref) {
  final profile = ref.watch(profileProvider);
  if (profile == null) return 'Nenhuma';
  
  if (profile.interests.contains('Matemática')) {
    return 'Matemática Exploradora';
  }
  return 'Jornada Inicial';
});
