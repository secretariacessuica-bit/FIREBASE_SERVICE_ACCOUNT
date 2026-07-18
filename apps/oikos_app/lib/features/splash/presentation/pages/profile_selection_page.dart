import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../profiles/domain/entities/member_identity.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../../pin/presentation/pages/adaptive_pin_page.dart';

class ProfileSelectionPage extends StatelessWidget {
  final List<MemberIdentity> members;
  
  const ProfileSelectionPage({super.key, required this.members});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFBF8F1), // Cor de fundo off-white/bege claro da imagem
      body: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 60),
            const Text(
              "Bem-vindo à nossa jornada!",
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: Color(0xFF4A3E3D),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Escolha seu avatar para continuar",
              style: TextStyle(
                fontSize: 16,
                color: Color(0xFF8C7E7C),
              ),
            ),
            const Spacer(),
            // Grade horizontal/Row de Avatares
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: members.map((member) => _buildAvatarItem(context, member)).toList(),
              ),
            ),
            const Spacer(),
            // Guardiã Luma na parte inferior
            _buildGuardianSection(),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildAvatarItem(BuildContext context, MemberIdentity member) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => AdaptivePinPage(selectedMember: member),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 12),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Placeholder para a imagem 3D do Avatar (Papai, Sofia, Lorenzo, etc)
            Container(
              width: 100,
              height: 140,
              decoration: BoxDecoration(
                color: Colors.black12,
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Icon(Icons.person, size: 60, color: Colors.white),
            ),
            const SizedBox(height: 16),
            Container(
              width: 60,
              height: 4,
              decoration: BoxDecoration(
                color: member.favoriteColor.withOpacity(0.5),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              member.name,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: Color(0xFF4A3E3D),
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildGuardianSection() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        // Placeholder para a imagem da Luma (Bebê planta verde)
        Container(
          width: 80,
          height: 80,
          decoration: const BoxDecoration(
            color: Color(0xFF90D2A8), // Verde da Luma
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.pets, color: Colors.white, size: 40),
        ),
        const SizedBox(width: 16),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24).copyWith(bottomLeft: Radius.zero),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 10,
                offset: const Offset(0, 4),
              )
            ],
          ),
          child: const Text(
            "Oi! Eu sou o Lumo,\nseu guardião da família.",
            style: TextStyle(
              fontSize: 16,
              color: Color(0xFF6B5E5C),
              height: 1.4,
            ),
          ),
        )
      ],
    );
  }
}
