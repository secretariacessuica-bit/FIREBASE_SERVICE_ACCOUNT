import 'package:cloud_firestore/cloud_firestore.dart';
import 'remote_data_source.dart';

class FirestoreRemoteDataSource implements RemoteDataSource {
  final FirebaseFirestore _firestore;

  FirestoreRemoteDataSource(this._firestore);

  @override
  Future<void> connect() async {
    // Firestore gerencia a conexão automaticamente, mas podemos forçar reabilitação
    await _firestore.enableNetwork();
  }

  @override
  Future<void> disconnect() async {
    await _firestore.disableNetwork();
  }

  @override
  Future<void> pushMutations(List<Map<String, dynamic>> mutations) async {
    final batch = _firestore.batch();
    
    for (final mutation in mutations) {
      final docRef = _firestore
          .collection(mutation['collection'])
          .doc(mutation['entityId']);

      if (mutation['type'] == 'delete') {
        batch.delete(docRef);
      } else {
        batch.set(mutation['payload'], SetOptions(merge: true));
      }
    }
    
    await batch.commit();
  }

  @override
  Stream<Map<String, dynamic>> watchCollection(String collectionName, String householdId) {
    return _firestore
        .collection(collectionName)
        .where('householdId', isEqualTo: householdId)
        .snapshots()
        .map((snapshot) {
          // Aqui retornaríamos uma compilação das mudanças, 
          // simplificado para o exemplo para retornar o primeiro documento alterado.
          if (snapshot.docChanges.isNotEmpty) {
            final doc = snapshot.docChanges.first.doc;
            final data = doc.data() ?? {};
            data['id'] = doc.id;
            return data;
          }
          return {};
    });
  }

  @override
  Future<Map<String, dynamic>?> getEntity(String collectionName, String entityId) async {
    final doc = await _firestore.collection(collectionName).doc(entityId).get();
    if (doc.exists) {
      final data = doc.data() ?? {};
      data['id'] = doc.id;
      return data;
    }
    return null;
  }
}
