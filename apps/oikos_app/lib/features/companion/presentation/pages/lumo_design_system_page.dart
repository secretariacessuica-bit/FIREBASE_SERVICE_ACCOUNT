import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../../domain/lumo_variant.dart';
import '../widgets/lumo_renderer.dart';

class LumoDesignSystemPage extends StatelessWidget {
  const LumoDesignSystemPage({super.key});

  @override
  Widget build(BuildContext context) {
    final bool isMobile = MediaQuery.of(context).size.width < 800;

    return Scaffold(
      backgroundColor: const Color(0xFFF2FAF9), // Light minty background
      appBar: AppBar(
        title: const Text('Lumo Design System', style: TextStyle(color: Colors.white)),
        backgroundColor: const Color(0xFF1E3532),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(isMobile ? 16.0 : 48.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildHero(isMobile),
            SizedBox(height: isMobile ? 32 : 64),
            _buildSectionTitle('04 — COMPONENTES', 'Primitivos Isolados'),
            const SizedBox(height: 24),
            _buildPrimitivesGrid(isMobile),
            SizedBox(height: isMobile ? 32 : 64),
            _buildSectionTitle('05 — EXPRESSÕES', '10 Estados Emocionais'),
            const SizedBox(height: 24),
            _buildExpressionsGrid(isMobile),
          ],
        ),
      ),
    );
  }

  Widget _buildHero(bool isMobile) {
    return Container(
      padding: EdgeInsets.all(isMobile ? 24 : 48),
      decoration: BoxDecoration(
        color: const Color(0xFF1E3532),
        borderRadius: BorderRadius.circular(24),
      ),
      child: isMobile 
        ? Column(
            children: [
              const LumoRenderer(variant: LumoVariant.idle, size: 180),
              const SizedBox(height: 32),
              _buildHeroText(isMobile),
            ],
          )
        : Row(
            children: [
              Expanded(child: _buildHeroText(isMobile)),
              const SizedBox(width: 48),
              const LumoRenderer(variant: LumoVariant.idle, size: 250),
            ],
          ),
    );
  }

  Widget _buildHeroText(bool isMobile) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFF38D8C0).withOpacity(0.2),
            borderRadius: BorderRadius.circular(20),
          ),
          child: const Text('LUMO DESIGN SYSTEM', style: TextStyle(color: Color(0xFF38D8C0), fontWeight: FontWeight.bold)),
        ),
        const SizedBox(height: 24),
        Text(
          'Olá, sou o\nLumo.',
          style: TextStyle(fontSize: isMobile ? 48 : 64, fontWeight: FontWeight.bold, color: Colors.white, height: 1.1),
        ),
        const SizedBox(height: 24),
        const Text(
          'Mascote oficial do Oikos — um guia de acolhimento para famílias imigrantes que aprendem um novo idioma através de pequenas missões do dia a dia.',
          style: TextStyle(fontSize: 18, color: Colors.white70),
        ),
      ],
    );
  }

  Widget _buildSectionTitle(String subtitle, String title) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(subtitle, style: const TextStyle(color: Color(0xFF38D8C0), fontWeight: FontWeight.bold, letterSpacing: 1.5)),
        const SizedBox(height: 8),
        Text(title, style: const TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Color(0xFF1E3532))),
      ],
    );
  }

  Widget _buildPrimitivesGrid(bool isMobile) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('ESTRUTURA', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black54)),
        const SizedBox(height: 16),
        Wrap(
          spacing: 16,
          runSpacing: 16,
          children: [
            _buildComponentCard('Body', 'assets/images/lumo/body/body.svg', isMobile),
            _buildComponentCard('Glow', 'assets/images/lumo/glow/glow.svg', isMobile),
            _buildComponentCard('Shadow', 'assets/images/lumo/shadow/shadow.svg', isMobile),
            _buildComponentCard('Left Arm', 'assets/images/lumo/arms/arm_left.svg', isMobile),
            _buildComponentCard('Right Arm', 'assets/images/lumo/arms/arm_right.svg', isMobile),
          ],
        ),
        const SizedBox(height: 32),
        const Text('OLHOS — VARIANTES', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black54)),
        const SizedBox(height: 16),
        Wrap(
          spacing: 16,
          runSpacing: 16,
          children: [
            _buildComponentCard('Normal', 'assets/images/lumo/eyes/eye_normal.svg', isMobile),
            _buildComponentCard('Feliz', 'assets/images/lumo/eyes/eye_happy.svg', isMobile, backgroundOpacity: 0.1),
            _buildComponentCard('Grande', 'assets/images/lumo/eyes/eye_big.svg', isMobile),
            _buildComponentCard('Estrela', 'assets/images/lumo/eyes/eye_star.svg', isMobile),
            _buildComponentCard('Fechado', 'assets/images/lumo/eyes/eye_closed.svg', isMobile),
          ],
        ),
        const SizedBox(height: 32),
        const Text('BOCA — VARIANTES', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black54)),
        const SizedBox(height: 16),
        Wrap(
          spacing: 16,
          runSpacing: 16,
          children: [
            _buildComponentCard('Sorriso', 'assets/images/lumo/mouths/mouth_smile.svg', isMobile),
            _buildComponentCard('Sorriso Amplo', 'assets/images/lumo/mouths/mouth_big_smile.svg', isMobile),
            _buildComponentCard('Neutro', 'assets/images/lumo/mouths/mouth_neutral.svg', isMobile),
            _buildComponentCard('Triste', 'assets/images/lumo/mouths/mouth_sad.svg', isMobile),
            _buildComponentCard('Ondulado', 'assets/images/lumo/mouths/mouth_wavy.svg', isMobile),
          ],
        ),
      ],
    );
  }

  Widget _buildComponentCard(String name, String asset, bool isMobile, {double backgroundOpacity = 0.05}) {
    return Container(
      width: isMobile ? 150 : 180,
      height: isMobile ? 120 : 140,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.black.withOpacity(0.05)),
      ),
      child: Column(
        children: [
          Expanded(
            child: Container(
              margin: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFF38D8C0).withOpacity(backgroundOpacity),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Center(
                child: SvgPicture.asset(asset, width: isMobile ? 40 : 60, height: isMobile ? 40 : 60),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.only(bottom: 16.0),
            child: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black54, fontSize: 12)),
          ),
        ],
      ),
    );
  }

  Widget _buildExpressionsGrid(bool isMobile) {
    return Wrap(
      spacing: 16,
      runSpacing: 16,
      alignment: WrapAlignment.center,
      children: LumoVariant.values.map((variant) {
        return Container(
          width: isMobile ? 160 : 220,
          padding: EdgeInsets.all(isMobile ? 16 : 24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.black.withOpacity(0.05)),
          ),
          child: Column(
            children: [
              LumoRenderer(variant: variant, size: isMobile ? 80 : 100),
              const SizedBox(height: 16),
              Text(
                variant.name.toUpperCase(),
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: isMobile ? 14 : 16, color: const Color(0xFF1E3532)),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }
}
