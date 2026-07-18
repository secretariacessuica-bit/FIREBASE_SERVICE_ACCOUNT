import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive/hive.dart';
import '../../domain/xp_repository.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Estado de XP de um único membro
// ─────────────────────────────────────────────────────────────────────────────
class XpState {
  final int xp;
  final int level;
  final double progress; // dentro do nível (0.0–1.0)
  final bool justLeveledUp;

  const XpState({
    required this.xp,
    required this.level,
    required this.progress,
    this.justLeveledUp = false,
  });

  XpState copyWith({int? xp, int? level, double? progress, bool? justLeveledUp}) => XpState(
        xp: xp ?? this.xp,
        level: level ?? this.level,
        progress: progress ?? this.progress,
        justLeveledUp: justLeveledUp ?? this.justLeveledUp,
      );
}

// ─────────────────────────────────────────────────────────────────────────────
// Notifier
// ─────────────────────────────────────────────────────────────────────────────
class XpNotifier extends StateNotifier<XpState> {
  final XpRepository _repo;
  final String memberId;

  XpNotifier(this._repo, this.memberId)
      : super(_buildState(_repo.getXp(memberId)));

  static XpState _buildState(int xp) => XpState(
        xp: xp,
        level: XpRepository.levelFromXp(xp),
        progress: XpRepository.progressInLevel(xp),
      );

  Future<void> addXp(int amount) async {
    final oldLevel = state.level;
    final newXp = await _repo.addXp(memberId, amount);
    final newLevel = XpRepository.levelFromXp(newXp);
    state = _buildState(newXp).copyWith(justLeveledUp: newLevel > oldLevel);
  }

  /// Limpa o flag após mostrar a animação de level-up
  void clearLevelUpFlag() {
    state = state.copyWith(justLeveledUp: false);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Provider paramétrico por memberId
// ─────────────────────────────────────────────────────────────────────────────
final xpRepositoryProvider = Provider<XpRepository>((ref) {
  return XpRepository(Hive.box<int>('xpBox'));
});

final xpProvider = StateNotifierProvider.family<XpNotifier, XpState, String>(
  (ref, memberId) {
    final repo = ref.watch(xpRepositoryProvider);
    return XpNotifier(repo, memberId);
  },
);
