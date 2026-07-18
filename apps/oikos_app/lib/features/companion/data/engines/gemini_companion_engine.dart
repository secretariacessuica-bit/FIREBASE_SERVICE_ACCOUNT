import '../../domain/engine/language_engine.dart';
import 'secure_gateway_adapter.dart';
import '../../domain/entities/narrative_context.dart';

class GeminiCompanionEngine implements LanguageEngine {
  final SecureGatewayAdapter gateway;
  final NarrativeContext currentNarrative;

  GeminiCompanionEngine({
    required this.gateway,
    required this.currentNarrative,
  });

  @override
  Future<String> generate(String prompt) async {
    try {
      final payload = {'narrative': currentNarrative.condensedNarrative};
      final response = await gateway.sendToModel(prompt, payload);
      return response;
    } catch (e) {
      rethrow;
    }
  }
}
