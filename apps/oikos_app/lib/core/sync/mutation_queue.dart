enum MutationType {
  create,
  update,
  delete,
}

class Mutation {
  final String id;
  final String entityId;
  final String collectionName;
  final MutationType type;
  final Map<String, dynamic> payload;
  final DateTime timestamp;

  const Mutation({
    required this.id,
    required this.entityId,
    required this.collectionName,
    required this.type,
    required this.payload,
    required this.timestamp,
  });
}

abstract class MutationQueue {
  Future<void> enqueue(Mutation mutation);
  Future<List<Mutation>> peekPending({int limit = 50});
  Future<void> remove(List<String> mutationIds);
  Future<void> clear();
}
