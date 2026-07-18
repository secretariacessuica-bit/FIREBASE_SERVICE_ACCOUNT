import '../../domain/engine/language_engine.dart';

class MockCompanionEngine implements LanguageEngine {
  @override
  Future<String> generate(String prompt) async {
    await Future.delayed(const Duration(seconds: 2));
    
    // Simula as reflexões poéticas do Lumo baseadas no contexto recebido no prompt
    if (prompt.contains("discovery")) {
      return "Estive observando como a curiosidade tem guiado nossos últimos dias. Algumas descobertas mudam nossa forma de ver o mundo para sempre.";
    } else if (prompt.contains("percebeu")) {
      return "Percebo que vocês têm celebrado pequenas conquistas com frequência ultimamente. O lar fica mais leve quando valorizamos os passos curtos.";
    } else {
      return "O tempo passa, mas as histórias que escolhemos guardar permanecem conosco. Estou aqui para cuidar de cada uma delas.";
    }
  }
}
