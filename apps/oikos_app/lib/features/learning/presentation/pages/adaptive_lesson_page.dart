import 'package:flutter/material.dart';
import '../../profiles/domain/entities/member_identity.dart';
import '../data/horizon_gateway.dart';
import '../domain/adaptive_study_tool.dart';
import 'adaptive_result_page.dart';
import '../../guardian/domain/entities/personal_pet.dart';
import '../../guardian/domain/entities/community_guardian.dart';

class AdaptiveLessonPage extends StatefulWidget {
  final MemberIdentity member;
  final DailyMission mission;
  final PersonalPet pet;
  final CommunityGuardian luma;

  const AdaptiveLessonPage({
    super.key,
    required this.member,
    required this.mission,
    required this.pet,
    required this.luma,
  });

  @override
  State<AdaptiveLessonPage> createState() => _AdaptiveLessonPageState();
}

class _AdaptiveLessonPageState extends State<AdaptiveLessonPage> {
  void _onMissionComplete() {
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => AdaptiveResultPage(
          member: widget.member,
          mission: widget.mission,
          pet: widget.pet,
          luma: widget.luma,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // 1. Resolve qual ferramenta carregar baseado no tema
    final studyTool = AdaptiveStudyTool.fromTheme(
      theme: widget.member.theme,
      mission: widget.mission,
      onComplete: _onMissionComplete,
    );

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.grey),
          onPressed: () => Navigator.pop(context),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SafeArea(
        child: Stack(
          children: [
            // O Motor de Estudo (O Tool renderizado)
            Positioned.fill(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: studyTool,
              ),
            ),
            
            // O Sistema de Ajuda do Pet Pessoal (Overlay)
            Positioned(
              bottom: 24,
              left: 24,
              child: _buildPetAssistant(),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildPetAssistant() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Container(
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            color: const Color(0xFFFBF8F1),
            shape: BoxShape.circle,
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: const Icon(Icons.cruelty_free, color: Colors.blueGrey),
        ),
        const SizedBox(width: 12),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16).copyWith(bottomLeft: Radius.zero),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 4)],
          ),
          child: const Text(
            "Precisa de ajuda\ncom essa?",
            style: TextStyle(fontSize: 12, color: Colors.grey),
          ),
        )
      ],
    );
  }
}
