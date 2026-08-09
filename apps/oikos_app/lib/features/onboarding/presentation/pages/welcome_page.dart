import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/onboarding_wizard_provider.dart';

class WelcomePage extends ConsumerWidget {
  const WelcomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28.0, vertical: 24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(flex: 2),
              
              // Logotipo/Mascote Oikos
              Center(
                child: Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    color: AppColors.lumoGreen.withValues(alpha: 0.15),
                    shape: BoxShape.circle,
                  ),
                  child: const Center(
                    child: Text('🌱', style: TextStyle(fontSize: 54)),
                  ),
                ),
              ),
              const SizedBox(height: 28),

              // Título
              Text(
                'Bem-vindos ao Oikos',
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.w900,
                  color: AppColors.textPrimary,
                  letterSpacing: -0.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),

              // Texto Principal Emocional
              Text(
                'Cada família vive uma história diferente. O Oikos ajuda vocês a ganhar confiança e autonomia para enfrentar o dia a dia.',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: AppColors.textSecondary,
                  height: 1.55,
                  fontSize: 16,
                ),
                textAlign: TextAlign.center,
              ),
              
              const Spacer(flex: 3),

              // Grid de destaques com ícones modernos
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(child: _buildHighlightCard(context, Icons.storefront_rounded, Colors.blue, 'Mercado')),
                        const SizedBox(width: 12),
                        Expanded(child: _buildHighlightCard(context, Icons.directions_transit_rounded, Colors.orange, 'Transporte')),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(child: _buildHighlightCard(context, Icons.school_rounded, Colors.green, 'Escola')),
                        const SizedBox(width: 12),
                        Expanded(child: _buildHighlightCard(context, Icons.description_rounded, Colors.purple, 'Cartas importantes')),
                      ],
                    ),
                  ],
                ),
              ),

              const Spacer(flex: 4),

              // Botão Começar
              FilledButton(
                onPressed: () {
                  ref.read(onboardingWizardProvider.notifier).nextStep();
                },
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  minimumSize: const Size(double.infinity, 58),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                  elevation: 0,
                ),
                child: const Text(
                  'Começar',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHighlightCard(BuildContext context, IconData icon, Color iconColor, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          )
        ],
        border: Border.all(color: Colors.black.withValues(alpha: 0.03)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: iconColor, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
