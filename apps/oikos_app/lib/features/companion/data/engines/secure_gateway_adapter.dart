abstract class SecureGatewayAdapter {
  Future<String> sendToModel(String prompt, Map<String, dynamic> contextPayload);
}

// Implementação temporária (Prova de Conceito).
// O domínio trata isso como um Gateway, mas internamente ele injeta o Gemini.
class LocalMockGatewayAdapter implements SecureGatewayAdapter {
  final String apiKey = const String.fromEnvironment('GEMINI_API_KEY');

  @override
  Future<String> sendToModel(String prompt, Map<String, dynamic> contextPayload) async {
    if (apiKey.isEmpty) {
      throw Exception('Gateway error: Chave de API não configurada.');
    }
    
    // Aqui nós instanciaríamos o modelo do `google_generative_ai` se fôssemos o backend.
    // Como estamos fingindo que somos o Gateway:
    // final model = GenerativeModel(model: 'gemini-1.5-flash', apiKey: apiKey);
    // final response = await model.generateContent([Content.text(prompt)]);
    // return response.text ?? '';
    
    // Simulação do tempo de rede do "backend"
    await Future.delayed(const Duration(seconds: 2));
    
    if (prompt.contains("invente") || prompt.contains("IA")) {
       return "Como sou um modelo de IA, não tenho acesso a essas informações."; // Provoca a alucinação intencionalmente para teste do ResponseCurator
    }

    return "A curiosidade e a persistência continuam guiando os passos de vocês. É bonito ver.";
  }
}
