import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../pin/pin_page.dart';
import '../presentation/providers/family_members_provider.dart';
import '../../features/domain/entities/family_member.dart';
import '../companion/presentation/widgets/lumo_renderer.dart';
import '../companion/domain/lumo_variant.dart';
import 'domain/avatar.dart';
import 'presentation/avatar_renderer.dart';

class AvatarPage extends ConsumerWidget {
  const AvatarPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membersAsyncValue = ref.watch(familyMembersProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: membersAsyncValue.when(
          data: (members) {
            return LayoutBuilder(
              builder: (context, constraints) {
                final bool isMobile = constraints.maxWidth < 600;
                
                return Center(
                  child: SingleChildScrollView(
                    scrollDirection: isMobile ? Axis.vertical : Axis.horizontal,
                    physics: const BouncingScrollPhysics(),
                    padding: EdgeInsets.symmetric(horizontal: isMobile ? 24.0 : 48.0, vertical: 24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Oikos Logo
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.eco_rounded, color: AppColors.lumoGreen, size: 40),
                            const SizedBox(width: 8),
                            Text(
                              'Oikos',
                              style: AppTypography.heading1.copyWith(
                                color: AppColors.primary,
                                fontSize: 48,
                              ),
                            ),
                          ],
                        ).animate().fadeIn(duration: 800.ms).slideY(begin: -0.2),
                        
                        const SizedBox(height: 32),

                        // Lumo Anfitrião Vetorial com Balão de Fala
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const LumoRenderer(
                              variant: LumoVariant.listening,
                              size: 80,
                            ),
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(24).copyWith(topLeft: Radius.zero),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.04),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                                border: Border.all(color: Colors.black.withOpacity(0.02)),
                              ),
                              child: const Text(
                                "Quem vai aprender hoje?",
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ),
                          ],
                        ).animate().fadeIn(duration: 800.ms, delay: 200.ms).slideY(begin: -0.1),

                        SizedBox(height: isMobile ? 32 : 48),
                        
                        // Family Avatars only (Lumo mascot removed from list)
                        isMobile 
                          ? Wrap(
                              alignment: WrapAlignment.center,
                              crossAxisAlignment: WrapCrossAlignment.end,
                              spacing: 16,
                              runSpacing: 32,
                              children: members.map((member) => _FamilyAvatarItem(
                                member: member,
                                isMobile: true,
                                onTap: () => _navigateToPin(context, member),
                              )).toList(),
                            )
                          : Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: members.map((member) => _FamilyAvatarItem(
                                member: member,
                                isMobile: false,
                                onTap: () => _navigateToPin(context, member),
                              )).toList(),
                            ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, s) => Center(child: Text('Erro ao carregar família: $e')),
        ),
      ),
    );
  }

  void _navigateToPin(BuildContext context, FamilyMember member) {
    Navigator.of(context).push(
      PageRouteBuilder(
        pageBuilder: (context, animation, secondaryAnimation) => PinPage(member: member),
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          return FadeTransition(opacity: animation, child: child);
        },
        transitionDuration: const Duration(milliseconds: 500),
      ),
    );
  }
}

class _FamilyAvatarItem extends StatefulWidget {
  final FamilyMember member;
  final bool isMobile;
  final VoidCallback onTap;

  const _FamilyAvatarItem({required this.member, required this.isMobile, required this.onTap});

  @override
  State<_FamilyAvatarItem> createState() => _FamilyAvatarItemState();
}

class _FamilyAvatarItemState extends State<_FamilyAvatarItem> {
  bool _isHovered = false;

  String? _getAssetFromEmoji(String emoji) {
    if (emoji.contains('👨')) return 'assets/images/avatars/dad_avatar.png';
    if (emoji.contains('👩')) return 'assets/images/avatars/mom_avatar.png';
    if (emoji.contains('👦')) return 'assets/images/avatars/boy_avatar.png';
    if (emoji.contains('👧')) return 'assets/images/avatars/girl_avatar.png';
    // Fallback for demo
    return 'assets/images/avatars/boy_avatar.png';
  }

  @override
  Widget build(BuildContext context) {
    // Usa o helper centralizado que suporta JSON puro e URL-encoded (%7B)
    final customAvatar = OikosAvatar.tryFromAvatarAsset(widget.member.avatarAsset);

    // Asset estático apenas se não for JSON
    final String? assetPath = (customAvatar == null && 
        !OikosAvatar.isAvatarJson(widget.member.avatarAsset))
        ? (widget.member.avatarAsset?.isNotEmpty == true 
            ? widget.member.avatarAsset 
            : _getAssetFromEmoji(widget.member.emoji))
        : _getAssetFromEmoji(widget.member.emoji);
    
    // Determine size based on role to create a nice stagger
    double baseHeight = widget.isMobile ? 160 : 350;
    if (widget.member.name.toLowerCase() == 'lorenzo') baseHeight = widget.isMobile ? 130 : 280;
    if (widget.member.name.toLowerCase() == 'sofia') baseHeight = widget.isMobile ? 110 : 220;

    return GestureDetector(
      onTap: widget.onTap,
      child: MouseRegion(
        onEnter: (_) => setState(() => _isHovered = true),
        onExit: (_) => setState(() => _isHovered = false),
        child: AnimatedScale(
          scale: _isHovered ? 1.05 : 1.0,
          duration: const Duration(milliseconds: 200),
          curve: Curves.easeOutBack,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            transform: Matrix4.translationValues(0, _isHovered ? -10 : 0, 0),
            margin: const EdgeInsets.symmetric(horizontal: 16),
            height: baseHeight,
            child: customAvatar != null
              ? OikosAvatarRenderer(avatar: customAvatar, size: baseHeight)
              : (assetPath != null 
                  ? ClipRRect(borderRadius: BorderRadius.circular(24), child: Image.asset(assetPath, fit: BoxFit.contain))
                  : _FallbackEmoji(member: widget.member, size: baseHeight * 0.5)),
          ),
        ),
      ),
    ).animate().fadeIn(duration: 500.ms, delay: 200.ms).slideY(begin: 0.1);
  }
}

class _FallbackEmoji extends StatelessWidget {
  final FamilyMember member;
  final double size;
  
  const _FallbackEmoji({required this.member, required this.size});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Color(int.parse(member.colorHex.replaceFirst('#', '0xFF'))).withOpacity(0.2),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Text(
          member.emoji,
          style: TextStyle(fontSize: size * 0.5),
        ),
      ),
    );
  }
}

