import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/onboarding_wizard_provider.dart';
import '../widgets/onboarding_scaffold.dart';

class ReviewPage extends ConsumerWidget {
  const ReviewPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(onboardingWizardProvider);

    return OnboardingScaffold(
      title: 'Tudo certo?',
      subtitle: 'Revise os dados da sua comunidade antes de finalizar.',
      progress: 0.85,
      onBack: () => ref.read(onboardingWizardProvider.notifier).previousStep(),
      onNext: state.isSubmitting ? null : () => ref.read(onboardingWizardProvider.notifier).submit(),
      nextLabel: state.isSubmitting ? 'Salvando...' : 'Finalizar Cadastro',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSection('Nome da Família', state.familyName),
          const SizedBox(height: 24),
          const Text('Responsável', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
          const SizedBox(height: 8),
          if (state.guardian != null)
            _buildMemberRow(state.guardian!.name, state.guardian!.emoji, state.guardian!.colorHex),
            
          const SizedBox(height: 24),
          const Text('Crianças / Membros', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
          const SizedBox(height: 8),
          if (state.children.isEmpty)
            const Text('Nenhum membro adicional', style: TextStyle(fontStyle: FontStyle.italic)),
          ...state.children.map((child) => Padding(
            padding: const EdgeInsets.only(bottom: 8.0),
            child: _buildMemberRow(child.name, child.emoji, child.colorHex),
          )),
          
          const SizedBox(height: 24),
          _buildSection('Segurança', 'PIN configurado com sucesso. (****)'),
        ],
      ),
    );
  }

  Widget _buildSection(String title, String content) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.textSecondary)),
        const SizedBox(height: 4),
        Text(content, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
      ],
    );
  }

  Widget _buildMemberRow(String name, String emoji, String hexColor) {
    final color = Color(int.parse(hexColor.replaceFirst('#', 'ff'), radix: 16));
    return Row(
      children: [
        CircleAvatar(
          backgroundColor: color.withValues(alpha: 0.2),
          child: Text(emoji, style: const TextStyle(fontSize: 20)),
        ),
        const SizedBox(width: 12),
        Text(name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
      ],
    );
  }
}
