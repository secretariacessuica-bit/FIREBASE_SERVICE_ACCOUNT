class MotivationEngine {
  String generateMotivation(int streak, int completedMissions) {
    if (streak == 0) {
      return 'Preparei algo especial para vocês hoje.';
    } else if (streak > 3) {
      return 'Uau, você está numa sequência incrível de $streak dias!';
    } else {
      return 'Que bom te ver novamente!';
    }
  }
}
