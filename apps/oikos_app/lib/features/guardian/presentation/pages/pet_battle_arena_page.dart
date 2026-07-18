import 'package:flutter/material.dart';
import '../../../../app/theme/adaptive_theme.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../domain/entities/battle_state.dart';

class PetBattleArenaPage extends StatefulWidget {
  final ProfileTheme theme;
  final int petLevel;

  const PetBattleArenaPage({
    super.key,
    required this.theme,
    required this.petLevel,
  });

  @override
  State<PetBattleArenaPage> createState() => _PetBattleArenaPageState();
}

class _PetBattleArenaPageState extends State<PetBattleArenaPage> {
  late BattleState _battleState;
  
  late String _question;
  late List<String> _options;
  late String _correct;
  
  bool get isVisualMode => widget.theme == ProfileTheme.playful;

  @override
  void initState() {
    super.initState();
    
    if (widget.theme == ProfileTheme.playful) {
      _question = "Encontre o igual: 🍎";
      _options = ["🍌", "🍎", "🍊"];
      _correct = "🍎";
    } else {
      _question = "Como se diz 'Maçã'?";
      _options = ["Apple", "Orange", "Banana"];
      _correct = "Apple";
    }
    
    // O HP do Pet escala com o nível dele!
    _battleState = BattleState(maxPlayerHp: widget.petLevel * 20, maxEnemyHp: 100);
    _battleState.addListener(_onBattleUpdate);
  }

  @override
  void dispose() {
    _battleState.removeListener(_onBattleUpdate);
    _battleState.dispose();
    super.dispose();
  }

  void _onBattleUpdate() {
    setState(() {}); // Reconstrói a UI com os HPs novos
  }

  void _onAnswerTap(String option) {
    if (!_battleState.isPlayerTurn || _battleState.isBattleOver) return;

    if (option == _correct) {
      _battleState.attackEnemy(widget.petLevel * 10); // Dano escala com o nível
    } else {
      _battleState.missAttack();
    }
  }

  @override
  Widget build(BuildContext context) {
    final adTheme = AdaptiveTheme.fromProfile(widget.theme);

    return Scaffold(
      backgroundColor: Colors.grey.shade900, // Arena escura
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text("Batalha de Conhecimento", style: TextStyle(color: Colors.white, fontFamily: adTheme.headingStyle.fontFamily)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // ÁREA DE COMBATE SUPERIOR
            Expanded(
              flex: 3,
              child: Stack(
                children: [
                  // INIMIGO (Canto Superior Direito)
                  Positioned(
                    top: 20,
                    right: 40,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        _buildHpBar("Lobo Selvagem", _battleState.currentEnemyHp, _battleState.maxEnemyHp, Colors.red),
                        const SizedBox(height: 16),
                        const Icon(Icons.pest_control, size: 100, color: Colors.redAccent),
                      ],
                    ),
                  ),
                  
                  // JOGADOR (Canto Inferior Esquerdo)
                  Positioned(
                    bottom: 20,
                    left: 40,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.cruelty_free, size: 120, color: Colors.blueAccent),
                        const SizedBox(height: 16),
                        _buildHpBar("Seu Pet (Nv ${widget.petLevel})", _battleState.currentPlayerHp, _battleState.maxPlayerHp, Colors.green),
                      ],
                    ),
                  ),

                  // LOG DE BATALHA
                  Positioned(
                    bottom: 150,
                    left: 0,
                    right: 0,
                    child: Center(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                        decoration: BoxDecoration(
                          color: Colors.black54,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          _battleState.battleLog,
                          style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                  )
                ],
              ),
            ),
            
            // ÁREA DE CONTROLE (GOLPES = PERGUNTAS)
            Expanded(
              flex: 2,
              child: Container(
                decoration: BoxDecoration(
                  color: adTheme.surfaceColor,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
                ),
                padding: const EdgeInsets.all(24),
                child: _battleState.isBattleOver
                  ? Center(
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: adTheme.primaryColor,
                          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                        ),
                        onPressed: () => Navigator.pop(context),
                        child: const Text("Sair da Arena", style: TextStyle(color: Colors.white, fontSize: 18)),
                      ),
                    )
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          isVisualMode ? _question : "Rápido! $_question",
                          style: adTheme.headingStyle.copyWith(color: adTheme.primaryColor, fontSize: isVisualMode ? 28 : 24),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 24),
                        Expanded(
                          child: isVisualMode 
                            ? Row(
                                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                children: _options.map((option) => Expanded(
                                  child: Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 8),
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 24),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(24),
                                          side: BorderSide(color: adTheme.primaryColor.withOpacity(0.2), width: 4),
                                        ),
                                      ),
                                      onPressed: () => _onAnswerTap(option),
                                      child: Text(option, style: const TextStyle(fontSize: 48)),
                                    ),
                                  ),
                                )).toList(),
                              )
                            : Column(
                                children: _options.map((option) => Padding(
                                  padding: const EdgeInsets.only(bottom: 12),
                                  child: SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.white,
                                        foregroundColor: adTheme.primaryColor,
                                        padding: const EdgeInsets.symmetric(vertical: 16),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: adTheme.buttonRadius,
                                          side: BorderSide(color: adTheme.primaryColor.withOpacity(0.2), width: 2),
                                        ),
                                      ),
                                      onPressed: () => _onAnswerTap(option),
                                      child: Text(option, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                                    ),
                                  ),
                                )).toList(),
                              ),
                        ),
                      ],
                    ),
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildHpBar(String name, int current, int max, Color color) {
    double pct = current / max;
    if (pct < 0) pct = 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Container(
          width: 150,
          height: 12,
          decoration: BoxDecoration(
            color: Colors.grey.shade800,
            borderRadius: BorderRadius.circular(6),
          ),
          child: FractionallySizedBox(
            alignment: Alignment.centerLeft,
            widthFactor: pct,
            child: Container(
              decoration: BoxDecoration(
                color: color,
                borderRadius: BorderRadius.circular(6),
              ),
            ),
          ),
        ),
        const SizedBox(height: 4),
        Text("$current / $max HP", style: const TextStyle(color: Colors.white70, fontSize: 12)),
      ],
    );
  }
}
