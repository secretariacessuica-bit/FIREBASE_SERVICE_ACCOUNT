import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/engine/language_engine.dart';
import '../../domain/engine/memory_interpreter.dart';
import '../../domain/engine/context_builder.dart';
import '../../domain/engine/prompt_crafter.dart';
import '../../domain/engine/response_curator.dart';
import '../../domain/entities/lumo_mind.dart';
import '../../data/engines/companion_engine_config.dart';
import '../../data/engines/mock_companion_engine.dart';
import '../../data/engines/fallback_companion_engine.dart';
import '../../data/engines/gemini_companion_engine.dart';
import '../../data/engines/secure_gateway_adapter.dart';
import '../../data/engines/narrative_mapper.dart';
import '../../domain/entities/narrative_context.dart';
import '../../domain/entities/household_atmosphere.dart';

class LumosCornerPage extends StatefulWidget {
  const LumosCornerPage({super.key});

  @override
  State<LumosCornerPage> createState() => _LumosCornerPageState();
}

class _LumosCornerPageState extends State<LumosCornerPage> with SingleTickerProviderStateMixin {
  late LumoMind _lumoMind;
  final List<String> _conversation = [];
  final TextEditingController _controller = TextEditingController();
  bool _isPondering = false;
  late AnimationController _breathingController;
  HouseholdAtmosphere? _atmosphere;

  @override
  void initState() {
    super.initState();
    
    final mockEngine = MockCompanionEngine();
    final fallbackEngine = FallbackCompanionEngine();
    final remoteEngine = GeminiCompanionEngine(
      gateway: LocalMockGatewayAdapter(),
      currentNarrative: const NarrativeContext(narrativeSentences: []),
    );

    _lumoMind = LumoMind(
      config: const CompanionEngineConfig(mode: CompanionEngineMode.remote), 
      memoryInterpreter: MemoryInterpreter(),
      contextBuilder: ContextBuilder(),
      narrativeMapper: NarrativeMapper(),
      promptCrafter: const PromptCrafter(),
      responseCurator: const ResponseCurator(),
      engines: {
        CompanionEngineMode.mock: mockEngine,
        CompanionEngineMode.fallback: fallbackEngine,
        CompanionEngineMode.remote: remoteEngine,
      },
    );

    _breathingController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 4), // Fallback inicial
    )..repeat(reverse: true);

    _loadAtmosphere();
  }

  Future<void> _loadAtmosphere() async {
    final atmosphere = await _lumoMind.currentAtmosphere;
    setState(() {
      _atmosphere = atmosphere;
      
      // Ajuste de UI baseado na Atmosfera
      String greeting;
      int breathingDurationSecs;

      switch (atmosphere.mood) {
        case LumoMood.celebrando:
          greeting = "Há uma alegria discreta caminhando por aqui.";
          breathingDurationSecs = 2; // Rápido e vibrante
          break;
        case LumoMood.silencioso:
          greeting = "Nem todo silêncio está vazio.";
          breathingDurationSecs = 6; // Lento e profundo
          break;
        case LumoMood.contemplativo:
          greeting = "O tempo parece pedir uma pausa hoje.";
          breathingDurationSecs = 5;
          break;
        default:
          greeting = "Hoje a casa parece respirar tranquilamente.";
          breathingDurationSecs = 4;
      }
      
      _conversation.add(greeting);
      _breathingController.duration = Duration(seconds: breathingDurationSecs);
      _breathingController.repeat(reverse: true);
    });

    _generateInitialWhisper();
  }

  Future<void> _generateInitialWhisper() async {
    setState(() => _isPondering = true);
    final interaction = await _lumoMind.ponder(null);
    setState(() {
      _conversation.add(interaction.message);
      _isPondering = false;
    });
  }

  Future<void> _speakToLumo(String text) async {
    if (text.trim().isEmpty) return;
    
    setState(() {
      _conversation.add("Família: $text");
      _isPondering = true;
    });
    
    _controller.clear();
    
    final interaction = await _lumoMind.ponder(text);
    
    setState(() {
      _conversation.add(interaction.message);
      _isPondering = false;
    });
  }

  @override
  void dispose() {
    _breathingController.dispose();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // Cores quentes, iluminação suave simulada
      backgroundColor: const Color(0xFFFAF8F5),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 24.0),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios_new_rounded, color: AppColors.textSecondary),
                    onPressed: () => Navigator.pop(context),
                  ),
                  const Spacer(),
                  AnimatedBuilder(
                    animation: _breathingController,
                    builder: (context, child) {
                      return Opacity(
                        opacity: 0.5 + (_breathingController.value * 0.5),
                        child: const Text('🌱', style: TextStyle(fontSize: 28)),
                      );
                    },
                  ),
                ],
              ),
            ),
            
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 24.0),
                itemCount: _conversation.length + (_isPondering ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index == _conversation.length && _isPondering) {
                    return Padding(
                      padding: const EdgeInsets.only(top: 32.0),
                      child: Center(
                        child: AnimatedBuilder(
                          animation: _breathingController,
                          builder: (context, child) {
                            return Opacity(
                              opacity: 0.3 + (_breathingController.value * 0.7),
                              child: const Text(
                                'Lumo está recordando...',
                                style: TextStyle(
                                  color: AppColors.textSecondary,
                                  fontStyle: FontStyle.italic,
                                  fontSize: 16,
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                    );
                  }

                  final msg = _conversation[index];
                  final isFamily = msg.startsWith("Família: ");
                  final text = isFamily ? msg.replaceAll("Família: ", "") : msg;

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 48.0),
                    child: Text(
                      text,
                      textAlign: isFamily ? TextAlign.right : TextAlign.left,
                      style: TextStyle(
                        fontSize: isFamily ? 20 : 24,
                        fontWeight: isFamily ? FontWeight.w500 : FontWeight.w400,
                        color: isFamily ? AppColors.textSecondary : AppColors.textPrimary,
                        height: 1.5,
                        fontStyle: isFamily ? FontStyle.normal : FontStyle.italic,
                      ),
                    ),
                  );
                },
              ),
            ),
            
            // Área de reflexão (Input)
            Container(
              padding: const EdgeInsets.all(32.0),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    const Color(0xFFFAF8F5).withOpacity(0.0),
                    const Color(0xFFFAF8F5),
                  ],
                ),
              ),
              child: TextField(
                controller: _controller,
                onSubmitted: _speakToLumo,
                style: const TextStyle(fontSize: 18, color: AppColors.textPrimary),
                decoration: InputDecoration(
                  hintText: 'Compartilhe uma memória...',
                  hintStyle: TextStyle(color: AppColors.textSecondary.withOpacity(0.5), fontStyle: FontStyle.italic),
                  border: InputBorder.none,
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.send_rounded, color: AppColors.textSecondary),
                    onPressed: () => _speakToLumo(_controller.text),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
