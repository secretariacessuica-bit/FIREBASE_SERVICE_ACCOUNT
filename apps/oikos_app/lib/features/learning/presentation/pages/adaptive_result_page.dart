import 'package:flutter/material.dart';
import '../../profiles/domain/entities/member_identity.dart';
import '../../profiles/domain/entities/profile_theme.dart';
import '../data/horizon_gateway.dart';
import '../../guardian/domain/entities/personal_pet.dart';
import '../../guardian/domain/entities/community_guardian.dart';

class AdaptiveResultPage extends StatelessWidget {
  final MemberIdentity member;
  final DailyMission mission;
  final PersonalPet pet;
  final CommunityGuardian luma;

  const AdaptiveResultPage({
    super.key,
    required this.member,
    required this.mission,
    required this.pet,
    required this.luma,
  });

  @override
  Widget build(BuildContext context) {
    // Simula a evolução e alimentação
    final updatedPet = pet.feed(mission.xpReward);
    final updatedLuma = luma.addCollectiveEffort(mission.xpReward ~/ 2);

    final isPlayful = member.theme == ProfileTheme.playful;
    final isGamified = member.theme == ProfileTheme.gamified;

    return Scaffold(
      backgroundColor: isPlayful ? const Color(0xFFFFF0F5) : (isGamified ? const Color(0xFFE8F1FA) : const Color(0xFFFBF8F1)),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(32.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                "Missão Concluída!",
                style: TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: isPlayful ? const Color(0xFFC55A7B) : (isGamified ? const Color(0xFF4A7DBC) : const Color(0xFF4A3E3D)),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 40),
              
              // Alimentação do Pet Pessoal
              _buildRewardBox("Seu Pet ganhou", "+${mission.xpReward} XP", Icons.star, Colors.amber),
              const SizedBox(height: 16),
              
              if (updatedPet.level > pet.level)
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text(
                    "🎉 Seu Pet subiu de nível!",
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    textAlign: TextAlign.center,
                  ),
                ),
                
              const SizedBox(height: 40),

              // Alimentação da Guardiã Coletiva
              _buildRewardBox("Fome da Luma saciada", "+${mission.xpReward ~/ 2} Energia", Icons.favorite, Colors.redAccent),
              
              const Spacer(),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF90D2A8),
                  padding: const EdgeInsets.symmetric(vertical: 20),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: () => Navigator.pop(context),
                child: const Text("Voltar para o Santuário", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
              )
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRewardBox(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 16, color: Colors.grey, fontWeight: FontWeight.w600)),
          Row(
            children: [
              Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
              const SizedBox(width: 8),
              Icon(icon, color: color),
            ],
          )
        ],
      ),
    );
  }
}
