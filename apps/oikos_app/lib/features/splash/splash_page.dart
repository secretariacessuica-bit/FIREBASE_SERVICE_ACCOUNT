import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../avatar/avatar_page.dart';
import '../presentation/providers/app_bootstrap_provider.dart';

import '../onboarding/presentation/pages/welcome_page.dart';
import '../onboarding/presentation/pages/onboarding_flow_page.dart';

class SplashPage extends ConsumerStatefulWidget {
  const SplashPage({super.key});

  @override
  ConsumerState<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<SplashPage> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );
    
    _scaleAnimation = Tween<double>(begin: 0.8, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutBack),
    );

    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(appBootstrapProvider);
    print("SPLASH_PAGE: build called. Current state: \$state");
    
    if (state != AppBootstrapState.loading) {
      print("SPLASH_PAGE: state is not loading, starting 2 second delay...");
      Future.delayed(const Duration(milliseconds: 2000), () {
        print("SPLASH_PAGE: 2 seconds passed. mounted=\$mounted");
        if (mounted) {
          Widget nextPage;
          if (state == AppBootstrapState.needsOnboarding) {
            print("SPLASH_PAGE: navigating to OnboardingFlowPage");
            nextPage = const OnboardingFlowPage();
          } else {
            print("SPLASH_PAGE: navigating to AvatarPage");
            nextPage = const AvatarPage();
          }

          try {
            Navigator.of(context).pushReplacement(
              PageRouteBuilder(
                pageBuilder: (context, animation, secondaryAnimation) => nextPage,
                transitionsBuilder: (context, animation, secondaryAnimation, child) {
                  return FadeTransition(opacity: animation, child: child);
                },
                transitionDuration: const Duration(milliseconds: 600),
              ),
            );
            print("SPLASH_PAGE: navigation pushed successfully");
          } catch (e, stack) {
            print("SPLASH_PAGE ERROR: \$e\\n\$stack");
          }
        }
      });
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: AnimatedBuilder(
          animation: _controller,
          builder: (context, child) {
            return FadeTransition(
              opacity: _fadeAnimation,
              child: Transform.scale(
                scale: _scaleAnimation.value,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 100,
                      height: 100,
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        borderRadius: BorderRadius.circular(32),
                      ),
                      child: const Center(
                        child: Icon(Icons.language_rounded, color: Colors.white, size: 48),
                      ),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Oikos',
                      style: AppTypography.heading1.copyWith(color: AppColors.primary),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
