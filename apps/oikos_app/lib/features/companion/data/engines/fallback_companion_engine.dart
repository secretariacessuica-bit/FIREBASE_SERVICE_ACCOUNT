import '../../domain/engine/language_engine.dart';
import '../../domain/engine/narrative_metrics.dart';

class FallbackCompanionEngine implements LanguageEngine {
  @override
  Future<String> generate(String prompt) async {
    return "Estou guardando este momento com cuidado. Podemos voltar a ele quando a casa estiver mais tranquila.";
  }
}
