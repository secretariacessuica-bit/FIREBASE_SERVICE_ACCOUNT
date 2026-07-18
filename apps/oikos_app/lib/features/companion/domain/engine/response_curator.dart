import 'lumo_narrative_policy.dart';
import 'narrative_metrics.dart';

class ResponseCurator {
  final LumoNarrativePolicy policy;

  const ResponseCurator({this.policy = const LumoNarrativePolicy()});

  String curate(String rawResponse) {
    if (rawResponse.trim() == '[SILENCE]') {
      return ''; // Silêncio real
    }

    String finalResponse = rawResponse;

    // Checagem de palavras proibidas (jargões)
    for (final word in policy.forbiddenVocabulary) {
      if (finalResponse.toLowerCase().contains(word.toLowerCase())) {
        return "Algumas palavras precisam de mais cuidado antes de nascer."; 
      }
    }

    // Heurística simples de checagem de alucinação (não invenção)
    // Se o modelo responder algo típico de IA pedindo mais dados
    if (finalResponse.toLowerCase().contains("não tenho acesso") || 
        finalResponse.toLowerCase().contains("como sou um modelo")) {
      return "Estou guardando este momento com cuidado. Podemos voltar a ele quando a casa estiver mais tranquila.";
    }

    return finalResponse;
  }
}
