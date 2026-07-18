import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/onboarding_wizard_provider.dart';
import '../widgets/onboarding_scaffold.dart';
import '../../../avatar/domain/avatar.dart';
import '../../../avatar/presentation/avatar_renderer.dart';
import '../../../avatar/presentation/pages/avatar_editor_page.dart';

class ChildrenPage extends ConsumerStatefulWidget {
  const ChildrenPage({super.key});

  @override
  ConsumerState<ChildrenPage> createState() => _ChildrenPageState();
}

class _ChildrenPageState extends ConsumerState<ChildrenPage> {
  final TextEditingController _controller = TextEditingController();
  OikosAvatar? _customAvatar;
  bool _isAdding = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _addChild() {
    final name = _controller.text.trim();
    if (name.isNotEmpty && _customAvatar != null) {
      ref.read(onboardingWizardProvider.notifier).addChild(name, '😀', '#2196F3', avatar: _customAvatar);
      setState(() {
        _controller.clear();
        _customAvatar = null;
        _isAdding = false;
      });
    }
  }

  void _openAvatarEditor() {
    if (_customAvatar == null) {
      _customAvatar = OikosAvatar.defaultAvatar('child_${DateTime.now().millisecondsSinceEpoch}', scale: 0.65);
    }
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (context) => ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
        child: AvatarEditorPage(
          avatarId: _customAvatar!.id,
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
    final state = ref.watch(onboardingWizardProvider);
    final children = state.children;

    return OnboardingScaffold(
      title: 'Crianças e Membros',
      subtitle: 'Adicione quem mais vai participar do Life.',
      progress: 0.45,
      onBack: () => ref.read(onboardingWizardProvider.notifier).previousStep(),
      onNext: () => ref.read(onboardingWizardProvider.notifier).nextStep(),
      isNextEnabled: !_isAdding, 
      nextLabel: children.isEmpty ? 'Pular por enquanto' : 'Continuar',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (children.isNotEmpty) ...[
            const Text(
              'Membros adicionados:',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: children.length,
              separatorBuilder: (context, index) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final child = children[index];
                final color = Color(int.parse(child.colorHex.replaceFirst('#', 'ff'), radix: 16));
                
                Widget avatarWidget = const Icon(Icons.person);
                if (child.avatarAsset != null && child.avatarAsset!.startsWith('{')) {
                  avatarWidget = ClipOval(
                    child: OikosAvatarRenderer(avatar: OikosAvatar.fromJsonString(child.avatarAsset!)),
                  );
                }

                return ListTile(
                  tileColor: color.withValues(alpha: 0.1),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  leading: CircleAvatar(
                    backgroundColor: Colors.white,
                    child: avatarWidget,
                  ),
                  title: Text(child.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                  trailing: IconButton(
                    icon: const Icon(Icons.close, color: Colors.black54),
                    onPressed: () {
                      ref.read(onboardingWizardProvider.notifier).removeChild(child.id);
                    },
                  ),
                );
              },
            ),
            const SizedBox(height: 24),
          ],

          if (!_isAdding)
            OutlinedButton.icon(
              onPressed: () {
                setState(() {
                  _isAdding = true;
                  _customAvatar = null;
                });
              },
              icon: const Icon(Icons.add),
              label: Text(children.isEmpty ? 'Adicionar membro' : 'Adicionar outro membro'),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size(double.infinity, 56),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                side: const BorderSide(color: AppColors.primary),
              ),
            ),

          if (_isAdding) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.textSecondary.withValues(alpha: 0.2)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Novo Membro', style: TextStyle(fontWeight: FontWeight.bold)),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () {
                          setState(() {
                            _isAdding = false;
                            _controller.clear();
                            _customAvatar = null;
                          });
                        },
                      )
                    ],
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _controller,
                    textCapitalization: TextCapitalization.words,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      hintText: 'Nome',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                      filled: true,
                      fillColor: Colors.black.withValues(alpha: 0.05),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Center(
                    child: Column(
                      children: [
                        GestureDetector(
                          onTap: _openAvatarEditor,
                          child: Container(
                            width: 120,
                            height: 150,
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
                                  : Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: const [
                                        Icon(Icons.person_add, size: 40, color: Colors.grey),
                                        SizedBox(height: 8),
                                        Text('Montar', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))
                                      ],
                                    ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: (_controller.text.trim().isNotEmpty && _customAvatar != null) 
                        ? _addChild 
                        : null,
                    style: FilledButton.styleFrom(
                      minimumSize: const Size(double.infinity, 48),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: const Text('Salvar Membro'),
                  )
                ],
              ),
            ),
          ]
        ],
      ),
    );
  }
}
