import 'package:flutter/material.dart';
import '../../brain/domain/entities/learning_decision.dart';
import '../../brain/domain/entities/learning_event.dart';
import '../../brain/domain/entities/difficulty_level.dart';

abstract class AdaptiveStudyTool extends Widget {
  final String title;
  final void Function(LearningEvent) onEvent;
  final void Function(SessionFinished) onSessionFinished;

  const AdaptiveStudyTool({
    super.key,
    required this.title,
    required this.onEvent,
    required this.onSessionFinished,
  });

  factory AdaptiveStudyTool.fromDecision({
    required LearningDecision decision,
    String? toolIdOverride,
    required void Function(LearningEvent) onEvent,
    required void Function(SessionFinished) onSessionFinished,
  }) {
    final toolIdToUse = toolIdOverride ?? decision.toolId;
    if (toolIdToUse == 'story') {
      return StoryQuestTool(title: decision.topic, onEvent: onEvent, onSessionFinished: onSessionFinished);
    } else if (toolIdToUse == 'game') {
      return TimeAttackTool(title: decision.topic, onEvent: onEvent, onSessionFinished: onSessionFinished);
    } else {
      return ClassicFlashcardTool(title: decision.topic, onEvent: onEvent, onSessionFinished: onSessionFinished);
    }
  }
}

// ==========================================
// 1. O Clássico (Adultos / Foco)
// ==========================================
class ClassicFlashcardTool extends StatelessWidget implements AdaptiveStudyTool {
  @override
  final String title;
  @override
  final void Function(LearningEvent) onEvent;
  @override
  final void Function(SessionFinished) onSessionFinished;

  const ClassicFlashcardTool({super.key, required this.title, required this.onEvent, required this.onSessionFinished});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Text("Revisão Espaçada", style: TextStyle(fontSize: 16, color: Colors.grey)),
        const SizedBox(height: 24),
        Container(
          width: 300,
          height: 400,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 10)],
          ),
          child: Center(
            child: Text(
              title, 
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
          ),
        ),
        const SizedBox(height: 40),
        ElevatedButton(
          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF4A3E3D)),
          onPressed: () {
            // TODO: Emitir evento real baseado no progresso
            onSessionFinished(SessionFinished(
              eventId: DateTime.now().millisecondsSinceEpoch.toString(),
              sessionId: 'sess_1',
              userId: 'u_1',
              timestamp: DateTime.now(),
              toolId: 'flashcard',
              topic: title,
              difficulty: DifficultyLevel.medium,
              totalDurationSeconds: 120,
              accuracy: 0.8,
              errorCount: 2,
            ));
          },
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 40, vertical: 16),
            child: Text("Mostrar Resposta"),
          ),
        )
      ],
    );
  }
}

// ==========================================
// 2. O Lúdico (Crianças / Historinha)
// ==========================================
class StoryQuestTool extends StatelessWidget implements AdaptiveStudyTool {
  @override
  final String title;
  @override
  final void Function(LearningEvent) onEvent;
  @override
  final void Function(SessionFinished) onSessionFinished;

  const StoryQuestTool({super.key, required this.title, required this.onEvent, required this.onSessionFinished});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: const Color(0xFFFFF0F5),
            borderRadius: BorderRadius.circular(24),
          ),
          child: const Text(
            "A Luma perdeu suas cores na floresta! Vamos ajudá-la?",
            style: TextStyle(fontSize: 22, color: Color(0xFFC55A7B), fontWeight: FontWeight.bold),
            textAlign: TextAlign.center,
          ),
        ),
        const SizedBox(height: 40),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildColorBubble(Colors.red),
            _buildColorBubble(Colors.blue),
            _buildColorBubble(Colors.green),
          ],
        ),
        const SizedBox(height: 60),
        ElevatedButton(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFFC55A7B),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))
          ),
          onPressed: () {
            onSessionFinished(SessionFinished(
              eventId: DateTime.now().millisecondsSinceEpoch.toString(),
              sessionId: 'sess_1',
              userId: 'u_1',
              timestamp: DateTime.now(),
              toolId: 'story',
              topic: title,
              difficulty: DifficultyLevel.easy,
              totalDurationSeconds: 300,
              accuracy: 1.0,
              errorCount: 0,
            ));
          },
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 40, vertical: 16),
            child: Text("Resgatar!", style: TextStyle(fontSize: 18)),
          ),
        )
      ],
    );
  }

  Widget _buildColorBubble(Color color) {
    return Container(
      width: 80,
      height: 80,
      decoration: BoxDecoration(color: color, shape: BoxShape.circle),
    );
  }
}

// ==========================================
// 3. O Gamificado (Adolescentes / Desafio)
// ==========================================
class TimeAttackTool extends StatelessWidget implements AdaptiveStudyTool {
  @override
  final String title;
  @override
  final void Function(LearningEvent) onEvent;
  @override
  final void Function(SessionFinished) onSessionFinished;

  const TimeAttackTool({super.key, required this.title, required this.onEvent, required this.onSessionFinished});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: const [
            Icon(Icons.timer, color: Colors.red, size: 40),
            SizedBox(width: 8),
            Text("00:15", style: TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: Colors.red)),
          ],
        ),
        const SizedBox(height: 40),
        Text(
          title,
          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF4A7DBC)),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 40),
        Column(
          children: [
            _buildAttackButton("Ataque Rápido"),
            const SizedBox(height: 16),
            _buildAttackButton("Ataque Carregado"),
          ],
        ),
        const SizedBox(height: 40),
        ElevatedButton(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF4A7DBC),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))
          ),
          onPressed: () {
            onSessionFinished(SessionFinished(
              eventId: DateTime.now().millisecondsSinceEpoch.toString(),
              sessionId: 'sess_1',
              userId: 'u_1',
              timestamp: DateTime.now(),
              toolId: 'game',
              topic: title,
              difficulty: DifficultyLevel.hard,
              totalDurationSeconds: 15,
              accuracy: 0.5,
              errorCount: 3,
            ));
          },
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 40, vertical: 16),
            child: Text("Vencer Batalha"),
          ),
        )
      ],
    );
  }

  Widget _buildAttackButton(String text) {
    return Container(
      width: 250,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        border: Border.all(color: const Color(0xFF4A7DBC), width: 3),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Center(
        child: Text(text, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF4A7DBC))),
      ),
    );
  }
}
