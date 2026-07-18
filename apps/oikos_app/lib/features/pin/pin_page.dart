import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../../shared/widgets/pin_keyboard.dart';
import '../../features/domain/entities/family_member.dart';
import '../../features/domain/entities/age_experience_mode.dart';
import '../home/home_page.dart';
import '../presentation/providers/di_providers.dart';
import '../avatar/domain/avatar.dart';
import '../avatar/presentation/avatar_renderer.dart';

class PinPage extends ConsumerStatefulWidget {
  final FamilyMember member;
  
  const PinPage({super.key, required this.member});

  @override
  ConsumerState<PinPage> createState() => _PinPageState();
}

class _PinPageState extends ConsumerState<PinPage> with SingleTickerProviderStateMixin {
  String _pin = '';
  bool _hasError = false;
  late AnimationController _shakeController;

  @override
  void initState() {
    super.initState();
    _shakeController = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
  }

  @override
  void dispose() {
    _shakeController.dispose();
    super.dispose();
  }

  String? _assetFromEmoji(String emoji) {
    if (emoji.contains('👨')) return 'assets/images/avatars/dad_avatar.png';
    if (emoji.contains('👩')) return 'assets/images/avatars/mom_avatar.png';
    if (emoji.contains('👦')) return 'assets/images/avatars/boy_avatar.png';
    if (emoji.contains('👧')) return 'assets/images/avatars/girl_avatar.png';
    return null;
  }

  void _onDigitPressed(String digit) async {
    if (digit == 'cancel') {
      Navigator.pop(context);
      return;
    }
    if (digit == 'backspace') {
      if (_pin.isNotEmpty) {
        setState(() {
          _pin = _pin.substring(0, _pin.length - 1);
          _hasError = false;
        });
      }
      return;
    }

    if (_pin.length < 4) {
      setState(() {
        _hasError = false;
        _pin += digit;
      });

      if (_pin.length == 4) {
        final verifyUseCase = ref.read(verifyPinUseCaseProvider);
        final isValid = await verifyUseCase.execute(widget.member.id, _pin);

        if (isValid) {
          Future.delayed(const Duration(milliseconds: 300), () {
            if (mounted) {
              Navigator.of(context).pushReplacement(
                PageRouteBuilder(
                  pageBuilder: (context, animation, secondaryAnimation) => HomePage(userId: widget.member.id, userName: widget.member.name),
                  transitionsBuilder: (context, animation, secondaryAnimation, child) {
                    return FadeTransition(opacity: animation, child: child);
                  },
                  transitionDuration: const Duration(milliseconds: 600),
                ),
              );
            }
          });
        } else {
          _shakeController.forward(from: 0.0);
          setState(() {
            _hasError = true;
            _pin = '';
          });
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    Color themeColor = AppColors.primary;
    try {
      themeColor = Color(int.parse(widget.member.colorHex.replaceFirst('#', '0xFF')));
    } catch (_) {}

    // Detect if child based on experienceMode (earlyChildhood or explorer) or fallback to emoji
    final em = widget.member.experienceMode;
    final bool isChild = em == AgeExperienceMode.earlyChildhood || em == AgeExperienceMode.explorer;
    
    // Determine the conversational title
    String title = isChild ? "Qual é o nosso número secreto? 🔑" : "Bem-vindo de volta, ${widget.member.name}.";
    String subtitle = isChild ? "Toque nos números para entrar!" : "Digite seu PIN para continuar.";

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Stack(
          children: [
            // Back Button
            Positioned(
              top: 24,
              left: 24,
              child: IconButton(
                icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 32),
                color: themeColor,
                onPressed: () => Navigator.pop(context),
              ),
            ),
            
            Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 48.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Avatar and Speech Bubble
                    Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Speech Bubble
                        Container(
                          margin: const EdgeInsets.only(bottom: 24),
                          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(color: themeColor.withOpacity(0.1), blurRadius: 20, offset: const Offset(0, 10)),
                            ],
                          ),
                          child: Column(
                            children: [
                              Text(
                                title,
                                style: AppTypography.heading2.copyWith(color: themeColor, fontSize: isChild ? 28 : 24),
                                textAlign: TextAlign.center,
                              ),
                              if (isChild) ...[
                                const SizedBox(height: 8),
                                Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.volume_up_rounded, color: Colors.grey),
                                    const SizedBox(width: 8),
                                    Text(
                                      "Ouvir",
                                      style: AppTypography.bodySmall.copyWith(color: Colors.grey),
                                    ),
                                  ],
                                ),
                              ] else ...[
                                const SizedBox(height: 8),
                                Text(
                                  subtitle,
                                  style: AppTypography.bodyMedium,
                                  textAlign: TextAlign.center,
                                ),
                              ]
                            ],
                          ),
                        ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.2),
                        
                        // Avatar Image
                        SizedBox(
                          height: 300,
                          child: () {
                            // Usa o helper centralizado (suporta { e %7B)
                            final customAvatar = OikosAvatar.tryFromAvatarAsset(widget.member.avatarAsset);
                            if (customAvatar != null) {
                              return Center(
                                child: SizedBox(
                                  width: 200,
                                  height: 300,
                                  child: OikosAvatarRenderer(avatar: customAvatar),
                                ),
                              );
                            }

                            // Asset est\u00e1tico (PNG): s\u00f3 se n\u00e3o for JSON
                            final String? assetPath = (!OikosAvatar.isAvatarJson(widget.member.avatarAsset) &&
                                widget.member.avatarAsset != null && 
                                widget.member.avatarAsset!.isNotEmpty) 
                                ? widget.member.avatarAsset 
                                : _assetFromEmoji(widget.member.emoji);
                            return assetPath != null
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(24),
                                  child: Image.asset(assetPath, fit: BoxFit.contain),
                                )
                              : Container(
                                  width: 150,
                                  height: 150,
                                  decoration: BoxDecoration(
                                    color: themeColor.withOpacity(0.2),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Center(child: Text(widget.member.emoji, style: const TextStyle(fontSize: 80))),
                                );
                          }(),
                        ).animate().fadeIn(duration: 500.ms).scale(begin: const Offset(0.8, 0.8)),
                      ],
                    ),
                    
                    const SizedBox(width: 80),
                    
                    // Pin Pad Area
                    Container(
                      width: 380, // Larger area for kids
                      padding: const EdgeInsets.all(32),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.5),
                        borderRadius: BorderRadius.circular(40),
                        border: Border.all(color: Colors.white, width: 2),
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          // PIN Dots
                          AnimatedBuilder(
                            animation: _shakeController,
                            builder: (context, child) {
                              final sinValue = _shakeController.value * 4 * 3.14159;
                              final dx = _hasError ? 10 * (1 - _shakeController.value) * (sinValue.isFinite ? sinValue : 0).abs() % 10 : 0.0;
                              return Transform.translate(
                                offset: Offset(dx, 0),
                                child: child,
                              );
                            },
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: List.generate(4, (index) {
                                bool isFilled = index < _pin.length;
                                return AnimatedContainer(
                                  duration: const Duration(milliseconds: 150),
                                  margin: const EdgeInsets.symmetric(horizontal: 12),
                                  width: 32,
                                  height: 32,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    color: isFilled ? themeColor : AppColors.surface,
                                    border: Border.all(
                                      color: _hasError ? Colors.red : (isFilled ? themeColor : themeColor.withOpacity(0.3)),
                                      width: 3,
                                    ),
                                  ),
                                );
                              }),
                            ),
                          ).animate().fadeIn(delay: 400.ms),
                          
                          if (_hasError)
                            Padding(
                              padding: const EdgeInsets.only(top: 16.0),
                              child: Text(
                                isChild ? 'Ops! Tente novamente!' : 'PIN Incorreto',
                                style: AppTypography.bodyMedium.copyWith(color: Colors.red, fontWeight: FontWeight.bold, fontSize: isChild ? 20 : 16),
                              ).animate().fadeIn(),
                            ),
                          
                          const SizedBox(height: 48),
                          PinKeyboard(
                            onDigitPressed: _onDigitPressed,
                            themeColor: themeColor,
                          ).animate().slideY(begin: 0.2, duration: 400.ms, curve: Curves.easeOutQuad),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}


