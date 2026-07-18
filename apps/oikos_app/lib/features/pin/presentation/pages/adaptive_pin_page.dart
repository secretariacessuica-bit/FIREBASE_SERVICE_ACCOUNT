import 'package:flutter/material.dart';
import '../../../profiles/domain/entities/member_identity.dart';
import '../../../profiles/domain/entities/profile_theme.dart';
import '../../../../app/theme/adaptive_theme.dart';

class AdaptivePinPage extends StatefulWidget {
  final MemberIdentity selectedMember;

  const AdaptivePinPage({super.key, required this.selectedMember});

  @override
  State<AdaptivePinPage> createState() => _AdaptivePinPageState();
}

class _AdaptivePinPageState extends State<AdaptivePinPage> {
  String pin = "";

  void _onDigitPressed(String digit) {
    if (pin.length < 4) {
      setState(() {
        pin += digit;
      });
      if (pin.length == 4) {
        // Authenticate and navigate to specific home
      }
    }
  }

  void _onDeletePressed() {
    if (pin.isNotEmpty) {
      setState(() {
        pin = pin.substring(0, pin.length - 1);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final adTheme = AdaptiveTheme.fromProfile(widget.selectedMember.theme);
    
    Color bgColor = adTheme.backgroundColor;
    Color padColor = adTheme.surfaceColor;
    Color padTextColor = adTheme.onSurfaceColor;
    String title = "Olá, ${widget.selectedMember.name}!";
    String subtitle = "Digite sua senha";
    String hint = "Use o número que você escolheu como chave.";

    // Adaptação da copy baseada no ProfileTheme
    if (widget.selectedMember.theme == ProfileTheme.playful) {
      subtitle = "Conta comigo!";
      hint = "Vamos aprender coisas incríveis juntos!";
    } else if (widget.selectedMember.theme == ProfileTheme.gamified) {
      subtitle = "Bora nessa!";
      hint = "Que tal um desafio hoje? Você consegue!";
    }

    return Scaffold(
      backgroundColor: bgColor,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 40),
          child: Column(
            children: [
              // Header
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Icon(Icons.lock, color: adTheme.highlightColor),
                  Row(
                    children: List.generate(4, (index) => Container(
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      width: 12,
                      height: 12,
                      decoration: BoxDecoration(
                        color: index < pin.length ? adTheme.primaryColor : Colors.transparent,
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFFD4C9C7), width: 2),
                      ),
                    )),
                  )
                ],
              ),
              const Spacer(),
              // Avatar + Mascote (Lado a Lado ou adaptativo)
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  // Placeholder para o Avatar 3D
                  Container(
                    width: 120,
                    height: 180,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: const Icon(Icons.person, size: 80, color: Colors.white),
                  ),
                  const SizedBox(width: 24),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: adTheme.headingStyle.copyWith(color: adTheme.primaryColor),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          subtitle,
                          style: adTheme.bodyStyle.copyWith(fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          hint,
                          style: adTheme.bodyStyle.copyWith(fontSize: 14),
                        ),
                      ],
                    ),
                  )
                ],
              ),
              const SizedBox(height: 40),
              // Numpad Adaptativo
              _buildAdaptiveNumpad(padColor, padTextColor, theme),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAdaptiveNumpad(Color btnColor, Color txtColor, ProfileTheme theme) {
    return Column(
      children: [
        for (var i = 0; i < 3; i++)
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              for (var j = 1; j <= 3; j++)
                _buildNumButton((i * 3 + j).toString(), btnColor, txtColor, theme),
            ],
          ),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            _buildActionBtn(Icons.close, const Color(0xFFFFC6BE), const Color(0xFFD95A50), _onDeletePressed, theme),
            _buildNumButton("0", btnColor, txtColor, theme),
            _buildActionBtn(Icons.backspace, const Color(0xFF89B3D9), Colors.white, _onDeletePressed, theme),
          ],
        )
      ],
    );
  }

  Widget _buildNumButton(String number, Color bgColor, Color textColor, ProfileTheme theme) {
    // Se gamified, o botão é ligeiramente mais quadrado ou diferente
    BorderRadius radius = BorderRadius.circular(12);
    if (theme == ProfileTheme.playful) radius = BorderRadius.circular(20);
    
    return GestureDetector(
      onTap: () => _onDigitPressed(number),
      child: Container(
        margin: const EdgeInsets.all(6),
        width: 72,
        height: 64,
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: radius,
        ),
        child: Center(
          child: Text(
            number,
            style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: textColor),
          ),
        ),
      ),
    );
  }

  Widget _buildActionBtn(IconData icon, Color bgColor, Color iconColor, VoidCallback onTap, ProfileTheme theme) {
    BorderRadius radius = BorderRadius.circular(12);
    if (theme == ProfileTheme.playful) radius = BorderRadius.circular(20);

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.all(6),
        width: 72,
        height: 64,
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: radius,
        ),
        child: Center(
          child: Icon(icon, color: iconColor, size: 28),
        ),
      ),
    );
  }
}
