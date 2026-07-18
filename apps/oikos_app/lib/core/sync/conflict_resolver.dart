abstract class ConflictStrategy {
  Map<String, dynamic> resolve(Map<String, dynamic> local, Map<String, dynamic> remote);
}

class HeritageMergeStrategy implements ConflictStrategy {
  @override
  Map<String, dynamic> resolve(Map<String, dynamic> local, Map<String, dynamic> remote) {
    // Para Treasures (Patrimônio)
    // Idempotência: Se ambos promovem a mesma story, escolhemos a mais antiga para preservar a primeira reflexão
    final localTime = DateTime.parse(local['date']);
    final remoteTime = DateTime.parse(remote['date']);
    return localTime.isBefore(remoteTime) ? local : remote;
  }
}

class DailyFlowStrategy implements ConflictStrategy {
  @override
  Map<String, dynamic> resolve(Map<String, dynamic> local, Map<String, dynamic> remote) {
    // Para Activities (Cotidiano)
    // Eventual Consistency: Last-Write-Wins
    final localTime = DateTime.parse(local['updatedAt'] ?? local['timestamp']);
    final remoteTime = DateTime.parse(remote['updatedAt'] ?? remote['timestamp']);
    return localTime.isAfter(remoteTime) ? local : remote;
  }
}

class IdentityMergeStrategy implements ConflictStrategy {
  @override
  Map<String, dynamic> resolve(Map<String, dynamic> local, Map<String, dynamic> remote) {
    // Para perfis, fazemos merge semântico campo a campo.
    // Conflitos críticos devem gerar PendingMerge.
    final merged = Map<String, dynamic>.from(remote);
    local.forEach((key, value) {
      if (!remote.containsKey(key)) {
        merged[key] = value;
      } else if (remote[key] != value) {
        // Conflito no mesmo campo.
        if (key == 'name' || key == 'familyName') {
          // Marca para PendingMerge
          merged['__pendingMerge_$key'] = {'local': value, 'remote': remote[key]};
        } else {
          // Usa LWW para campos menos sensíveis
          merged[key] = remote[key]; 
        }
      }
    });
    return merged;
  }
}

class ConflictResolver {
  final Map<String, ConflictStrategy> _strategies = {
    'treasures': HeritageMergeStrategy(),
    'activities': DailyFlowStrategy(),
    'profiles': IdentityMergeStrategy(),
    'households': IdentityMergeStrategy(),
  };

  Map<String, dynamic> resolveConflict(String collection, Map<String, dynamic> local, Map<String, dynamic> remote) {
    final strategy = _strategies[collection];
    if (strategy != null) {
      return strategy.resolve(local, remote);
    }
    // Default fallback: Remote wins
    return remote;
  }
}
