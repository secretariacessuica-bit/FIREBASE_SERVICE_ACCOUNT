import 'package:flutter/foundation.dart';

class BattleState extends ChangeNotifier {
  final int maxPlayerHp;
  final int maxEnemyHp;
  
  late int currentPlayerHp;
  late int currentEnemyHp;

  bool isPlayerTurn = true;
  String battleLog = "Um monstro selvagem apareceu!";
  bool isBattleOver = false;

  BattleState({required this.maxPlayerHp, required this.maxEnemyHp}) {
    currentPlayerHp = maxPlayerHp;
    currentEnemyHp = maxEnemyHp;
  }

  void attackEnemy(int damage) {
    if (isBattleOver) return;
    
    currentEnemyHp -= damage;
    if (currentEnemyHp <= 0) {
      currentEnemyHp = 0;
      battleLog = "Seu Pet desferiu o golpe final! Você Venceu!";
      isBattleOver = true;
    } else {
      battleLog = "Acerto! Você causou $damage de dano.";
      isPlayerTurn = false;
      _enemyTurn();
    }
    notifyListeners();
  }

  void missAttack() {
    if (isBattleOver) return;
    battleLog = "Você errou a palavra! Perdeu o turno.";
    isPlayerTurn = false;
    notifyListeners();
    _enemyTurn();
  }

  void _enemyTurn() {
    Future.delayed(const Duration(seconds: 2), () {
      if (isBattleOver) return;

      int enemyDamage = 10;
      currentPlayerHp -= enemyDamage;
      
      if (currentPlayerHp <= 0) {
        currentPlayerHp = 0;
        battleLog = "Seu Pet desmaiou... Fim de Batalha.";
        isBattleOver = true;
      } else {
        battleLog = "O Inimigo atacou e causou $enemyDamage de dano! Sua vez.";
        isPlayerTurn = true;
      }
      notifyListeners();
    });
  }
}
