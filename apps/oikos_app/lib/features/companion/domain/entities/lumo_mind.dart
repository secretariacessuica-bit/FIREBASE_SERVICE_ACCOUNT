import 'household_context.dart';
import 'insight.dart';
import 'lumo_interaction.dart';
import 'household_atmosphere.dart';
import '../engine/language_engine.dart';
import '../engine/memory_interpreter.dart';
import '../engine/context_builder.dart';
import '../engine/prompt_crafter.dart';
import '../engine/response_curator.dart';
import '../../data/engines/companion_engine_config.dart';
import '../../data/engines/narrative_mapper.dart';

class LumoMind {
  final CompanionEngineConfig config;
  final MemoryInterpreter memoryInterpreter;
  final ContextBuilder contextBuilder;
  final NarrativeMapper narrativeMapper;
  final PromptCrafter promptCrafter;
  final Map<CompanionEngineMode, LanguageEngine> engines;
  final ResponseCurator responseCurator;

  LumoMind({
    required this.config,
    required this.memoryInterpreter,
    required this.contextBuilder,
    required this.narrativeMapper,
    required this.promptCrafter,
    required this.engines,
    required this.responseCurator,
  });

  Future<HouseholdAtmosphere> get currentAtmosphere => memoryInterpreter.interpretRecentHistory();

  Future<LumoInteraction> ponder(String? userMessage) async {
    if (config.mode == CompanionEngineMode.disabled) {
      return LumoInteraction(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        message: "O Lumo está descansando.",
        timestamp: DateTime.now(),
      );
    }

    final atmosphere = await memoryInterpreter.interpretRecentHistory();
    final householdContext = await contextBuilder.build(atmosphere);
    final narrativeContext = narrativeMapper.map(householdContext);
    final prompt = promptCrafter.craft(userMessage, narrativeContext, atmosphere);
    
    LanguageEngine engine = engines[config.mode] ?? engines[CompanionEngineMode.fallback]!;
    String rawResponse;

    try {
      rawResponse = await engine.generate(prompt);
    } catch (e) {
      final fallbackEngine = engines[CompanionEngineMode.fallback]!;
      rawResponse = await fallbackEngine.generate(prompt);
    }
    
    final finalMessage = responseCurator.curate(rawResponse);
    
    return LumoInteraction(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      message: finalMessage,
      timestamp: DateTime.now(),
      isSpontaneous: userMessage == null,
    );
  }

  Future<Insight?> extractInsight(LumoInteraction interaction) async {
    if (interaction.message.contains("percebo que")) {
       return Insight(
         id: DateTime.now().millisecondsSinceEpoch.toString(),
         householdId: 'current',
         content: "O Lumo percebeu uma mudança positiva na rotina de hoje.",
         generatedAt: DateTime.now(),
       );
    }
    return null;
  }
}
