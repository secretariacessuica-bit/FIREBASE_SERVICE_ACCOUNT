import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/onboarding_wizard_provider.dart';
import '../widgets/onboarding_scaffold.dart';
import '../../../avatar/domain/avatar.dart';
import '../../../avatar/presentation/avatar_renderer.dart';
import '../../../avatar/presentation/pages/avatar_editor_page.dart';

class GuardianPage extends ConsumerStatefulWidget {
  const GuardianPage({super.key});

  @override
  ConsumerState<GuardianPage> createState() => _GuardianPageState();
}

class _GuardianPageState extends ConsumerState<GuardianPage> {
  late TextEditingController _controller;
  OikosAvatar? _customAvatar;

  @override
  void initState() {
    super.initState();
    final state = ref.read(onboardingWizardProvider);
    _controller = TextEditingController(text: state.guardian?.name ?? '');
    _customAvatar = OikosAvatar.tryFromAvatarAsset(state.guardian?.avatarAsset) 
        ?? OikosAvatar.defaultAvatar('guardian_1');
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onNext() {
    final name = _controller.text.trim();
    if (name.isNotEmpty && _customAvatar != null) {
      ref.read(onboardingWizardProvider.notifier).setGuardian(name, '😀', '#2196F3', avatar: _customAvatar);
      ref.read(onboardingWizardProvider.notifier).nextStep();
    }
  }

  void _openAvatarEditor() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        child: AvatarEditorPage(
          avatarId: 'guardian_1',
          initialAvatar: _customAvatar,
          onSave: (avatar) {
            setState(() {
              _customAvatar = avatar;
            });
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return OnboardingScaffold(
      title: 'Responsável',
      subtitle: 'Quem vai gerenciar a conta da família?',
      progress: 0.30,
      onBack: () => ref.read(onboardingWizardProvider.notifier).previousStep(),
      onNext: _onNext,
      isNextEnabled: _controller.text.trim().isNotEmpty && _customAvatar != null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: _controller,
            textCapitalization: TextCapitalization.words,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Seu nome ou apelido',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide.none,
              ),
              filled: true,
              fillColor: Colors.black.withValues(alpha: 0.05),
              contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
            ),
            style: const TextStyle(fontSize: 18),
          ),
          const SizedBox(height: 32),
          Center(
            child: Column(
              children: [
                GestureDetector(
                  onTap: _openAvatarEditor,
                  child: Container(
                    width: 160,
                    height: 200,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        )
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(24),
                      child: _customAvatar != null 
                          ? OikosAvatarRenderer(avatar: _customAvatar!)
                          : const Center(child: CircularProgressIndicator()),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: _openAvatarEditor,
                  icon: const Icon(Icons.edit),
                  label: const Text('Personalizar meu Avatar'),
                  style: ElevatedButton.styleFrom(
                    foregroundColor: Colors.blue,
                    backgroundColor: Colors.blue.withValues(alpha: 0.1),
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
