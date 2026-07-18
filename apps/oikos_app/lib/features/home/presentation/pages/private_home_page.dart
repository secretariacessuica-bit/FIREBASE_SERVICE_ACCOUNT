import 'package:flutter/material.dart';
import '../../../../app/theme/adaptive_theme.dart';
import '../../../profiles/domain/entities/member_identity.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../../guardian/domain/entities/personal_pet.dart';
import '../../../guardian/domain/entities/community_guardian.dart';
import '../../../learning/data/horizon_gateway.dart';
import '../../../learning/data/horizon_gateway_http_impl.dart';
import '../../../../core/presentation/widgets/animated_pet_avatar.dart';
import '../../../../core/presentation/widgets/community_luma_orb.dart';
import '../../../guardian/presentation/widgets/floating_pet_speech.dart';
import '../../../guardian/presentation/widgets/guardian_chat_modal.dart';
import '../../../guardian/domain/entities/interrogation_models.dart';
import '../../../guardian/presentation/pages/pet_battle_arena_page.dart';

class PrivateHomePage extends StatefulWidget {
  final MemberIdentity member;
  final PersonalPet pet;
  final CommunityGuardian communityGuardian;

  const PrivateHomePage({
    super.key,
    required this.member,
    required this.pet,
    required this.communityGuardian,
  });

  @override
  State<PrivateHomePage> createState() => _PrivateHomePageState();
}

class _PrivateHomePageState extends State<PrivateHomePage> {
  final HorizonGateway _gateway = HorizonGatewayHttpImpl();
  List<DailyMission> _missions = [];
  bool _isLoading = true;

  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _loadMissions();
  }

  Future<void> _loadMissions() async {
    try {
      final missions = await _gateway.getDailyPacing(widget.member);
      if (mounted) {
        setState(() {
          _missions = missions;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _hasError = true;
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final adTheme = AdaptiveTheme.fromProfile(widget.member.theme);
    final isPlayful = widget.member.theme == ProfileTheme.playful;

    return Scaffold(
      backgroundColor: adTheme.backgroundColor,
      appBar: _buildAppBar(),
      body: _isLoading
          ? _buildLoadingState(adTheme)
          : _hasError 
              ? _buildErrorState(adTheme)
              : SafeArea(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _buildHeader(adTheme),
                        const SizedBox(height: 32),
                        _buildPetSanctuary(adTheme),
                        const SizedBox(height: 40),
                        _buildJourney(adTheme, isPlayful),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildLoadingState(AdaptiveTheme adTheme) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(color: adTheme.primaryColor),
          const SizedBox(height: 16),
          Text("A Luma está buscando suas missões no Horizon...", style: adTheme.bodyStyle),
        ],
      ),
    );
  }

  Widget _buildErrorState(AdaptiveTheme adTheme) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.wifi_off, size: 60, color: adTheme.primaryColor),
            const SizedBox(height: 16),
            Text(
              "Ops! A Luma não conseguiu se conectar ao Horizon Core.",
              style: adTheme.headingStyle.copyWith(color: adTheme.primaryColor, fontSize: 20),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _isLoading = true;
                  _hasError = false;
                });
                _loadMissions();
              },
              child: const Text("Tentar Novamente"),
            )
          ],
        ),
      ),
    );
  }

  AppBar _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.transparent,
      elevation: 0,
      actions: [
        Container(
          margin: const EdgeInsets.only(right: 24, top: 8),
          child: CommunityLumaOrb(hungerLevel: widget.communityGuardian.communityHunger),
        )
      ],
    );
  }

  Widget _buildHeader(AdaptiveTheme adTheme) {
    String greeting = widget.member.theme == ProfileTheme.formal 
        ? "Pronto para o foco, ${widget.member.name}?" 
        : (widget.member.theme == ProfileTheme.gamified 
            ? "Bora pra mais uma missão, ${widget.member.name}!" 
            : "Vamos brincar e aprender, ${widget.member.name}!");

    return Text(
      greeting,
      style: adTheme.headingStyle.copyWith(color: adTheme.primaryColor),
    );
  }

  Widget _buildPetSanctuary(AdaptiveTheme adTheme) {
    return Container(
      height: 220,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.6),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white, width: 2),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          // O Pet Pessoal Animado
          AnimatedPetAvatar(
            size: 100,
            icon: Icons.cruelty_free,
            color: adTheme.primaryColor,
            isHungry: widget.pet.hunger < 0.4,
          ),
          
          Positioned(
            bottom: 20,
            child: Row(
              children: [
                const Icon(Icons.star, color: Colors.amber, size: 16),
                const SizedBox(width: 4),
                Text(
                  "Nível ${widget.pet.level}",
                  style: adTheme.bodyStyle.copyWith(fontWeight: FontWeight.bold),
                )
              ],
            ),
          ),
          
          // O Balão de Fala Dinâmico (Interrogatório)
          Positioned(
            top: 10,
            right: 20,
            child: FloatingPetSpeech(
              text: "A praia ou a montanha?",
              onTap: () => _openChatModal(adTheme),
            ),
          )
        ],
      ),
    );
  }

  void _openChatModal(AdaptiveTheme adTheme) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => GuardianChatModal(
        theme: adTheme,
        prompt: const InterrogationPrompt(
          id: '1',
          question: 'Se você pudesse viajar pra qualquer lugar agora, seria praia ou montanha?',
          context: 'travel_discovery',
        ),
      ),
    );
  }

  Widget _buildJourney(AdaptiveTheme adTheme, bool isPlayful) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              widget.member.theme == ProfileTheme.formal ? "Seu Pacing Diário" : (isPlayful ? "Mapa do Tesouro" : "Missões Ativas"),
              style: adTheme.headingStyle.copyWith(fontSize: 20, color: adTheme.primaryColor),
            ),
            // Botão da Arena (Só destranca se a fome for >= 0.4)
            if (widget.pet.hunger >= 0.4)
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.redAccent,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                ),
                icon: const Icon(Icons.flash_on, size: 16),
                label: const Text("Arena"),
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(
                    builder: (_) => PetBattleArenaPage(
                      member: widget.member,
                      petLevel: widget.pet.level,
                    ),
                  ));
                },
              )
          ],
        ),
        const SizedBox(height: 16),
        ..._missions.map((mission) => _buildMissionCard(mission, isPlayful, adTheme)).toList(),
      ],
    );
  }

  Widget _buildMissionCard(DailyMission mission, bool isPlayful, AdaptiveTheme adTheme) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(isPlayful ? 24 : 12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFBF8F1),
              borderRadius: BorderRadius.circular(isPlayful ? 16 : 8),
            ),
            child: Icon(
              mission.type == 'vocabulary' ? Icons.translate : (mission.type == 'grammar' ? Icons.menu_book : Icons.local_fire_department),
              color: const Color(0xFF4A3E3D),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  mission.title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF4A3E3D)),
                ),
                const SizedBox(height: 4),
                Text(
                  mission.description,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF8C7E7C)),
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          Column(
            children: [
              const Icon(Icons.star, color: Colors.amber, size: 16),
              Text(
                "+${mission.xpReward} XP",
                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.amber),
              )
            ],
          )
        ],
      ),
    );
  }
}
