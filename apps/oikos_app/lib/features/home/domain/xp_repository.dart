import 'package:hive/hive.dart';

/// Repositório simples de XP por membro — usa Box<int> sem geração de código.
class XpRepository {
  final Box<int> _box;

  XpRepository(this._box);

  /// XP atual do membro [memberId].
  int getXp(String memberId) => _box.get(memberId, defaultValue: 0)!;

  /// Adiciona [amount] XP ao membro e retorna o novo total.
  Future<int> addXp(String memberId, int amount) async {
    final current = getXp(memberId);
    final updated = current + amount;
    await _box.put(memberId, updated);
    return updated;
  }

  /// Nível calculado: a cada 100 XP o usuário sobe um nível.
  static int levelFromXp(int xp) => (xp ~/ 100) + 1;

  /// Progresso dentro do nível atual (0.0 → 1.0).
  static double progressInLevel(int xp) => (xp % 100) / 100.0;

  /// XP necessário para o próximo nível.
  static int xpForNextLevel(int xp) => 100 - (xp % 100);
}
