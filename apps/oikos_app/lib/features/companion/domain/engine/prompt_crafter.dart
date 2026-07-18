import '../entities/narrative_context.dart';
import '../entities/household_atmosphere.dart';
import 'lumo_narrative_policy.dart';

class PromptCrafter {
  final LumoNarrativePolicy policy;

  const PromptCrafter({this.policy = const LumoNarrativePolicy()});

  String craft(String? userMessage, NarrativeContext context, HouseholdAtmosphere atmosphere) {
    final systemPrompt = '''
IDENTIDADE: ${policy.identity}
MISSÃO: ${policy.mission}
ATMOSFERA ATUAL: O lar encontra-se em um estado ${atmosphere.mood.name}. O ritmo de sua fala deve acompanhar isso sutilmente.

REGRAS ESTritas:
1. NUNCA invente memórias, datas ou tesouros.
2. Não rotule pessoas.
3. Termos PROIBIDOS: ${policy.forbiddenVocabulary.join(', ')}.
4. Princípios emocionais: ${policy.emotionalRules.join(', ')}.

CONTEXTO NARRATIVO RECENTE:
${context.condensedNarrative}
''';

    if (userMessage == null) {
      return '$systemPrompt\n\nA família está em silêncio no Canto do Lumo. Se houver um Insight muito forte, faça um sussurro suave (Whisper). Caso contrário, responda apenas: [SILENCE]';
    }

    return '$systemPrompt\n\nA família diz: "$userMessage". Qual a reflexão do Guardião?';
  }
}
