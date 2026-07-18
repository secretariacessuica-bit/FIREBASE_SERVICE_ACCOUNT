import '../domain/entities/interrogation_models.dart';

class InsightEngine {
  /// Simula o motor LLM (Java) que processa linguagem natural
  /// e devolve tags estruturadas para o vetor do usuário.
  Future<GuardianInsight> extractInsight(String rawText, InterrogationPrompt contextPrompt) async {
    await Future.delayed(const Duration(seconds: 1)); // Simula processamento NLP
    
    List<String> tags = [];
    String mood = "neutro";
    
    final lowerText = rawText.toLowerCase();

    // Mocks de Extração NLP Simples
    if (lowerText.contains("praia") || lowerText.contains("mar")) {
      tags.add("beach");
      tags.add("nature");
      mood = "relaxado";
    }
    
    if (lowerText.contains("montanha") || lowerText.contains("frio") || lowerText.contains("neve")) {
      tags.add("mountain");
      tags.add("adventure");
      mood = "aventureiro";
    }

    if (lowerText.contains("passado") || lowerText.contains("dinossauro")) {
      tags.add("history");
      tags.add("dinosaurs");
      mood = "curioso";
    }

    if (tags.isEmpty) {
      tags.add("general");
    }

    return GuardianInsight(
      originalAnswer: rawText,
      extractedTags: tags,
      detectedMood: mood,
    );
  }
}
