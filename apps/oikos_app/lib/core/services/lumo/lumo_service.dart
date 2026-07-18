import 'greeting_engine.dart';
import 'motivation_engine.dart';

class LumoService {
  final GreetingEngine greetingEngine;
  final MotivationEngine motivationEngine;

  LumoService(this.greetingEngine, this.motivationEngine);

  String getGreeting() {
    return greetingEngine.generateGreeting();
  }

  String getMotivation(int streak, int completedMissions) {
    return motivationEngine.generateMotivation(streak, completedMissions);
  }
}
