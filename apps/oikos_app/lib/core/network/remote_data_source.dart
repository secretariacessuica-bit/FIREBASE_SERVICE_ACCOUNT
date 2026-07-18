abstract class RemoteDataSource {
  Future<void> connect();
  Future<void> disconnect();
  Future<void> pushMutations(List<Map<String, dynamic>> mutations);
  Stream<Map<String, dynamic>> watchCollection(String collectionName, String householdId);
  Future<Map<String, dynamic>?> getEntity(String collectionName, String entityId);
}
