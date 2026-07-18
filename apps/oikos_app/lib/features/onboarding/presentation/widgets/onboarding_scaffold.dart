import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';

class OnboardingScaffold extends StatelessWidget {
  final Widget child;
  final String title;
  final String subtitle;
  final VoidCallback? onBack;
  final VoidCallback? onNext;
  final String nextLabel;
  final bool isNextEnabled;
  final bool showProgress;
  final double progress;

  const OnboardingScaffold({
    super.key,
    required this.child,
    required this.title,
    required this.subtitle,
    this.onBack,
    this.onNext,
    this.nextLabel = 'Continuar',
    this.isNextEnabled = true,
    this.showProgress = true,
    this.progress = 0.0,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Column(
          children: [
            // App Bar / Top section
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              child: Row(
                children: [
                  if (onBack != null)
                    IconButton(
                      icon: const Icon(Icons.arrow_back_rounded),
                      onPressed: onBack,
                      color: AppColors.textPrimary,
                    )
                  else
                    const SizedBox(width: 48), // balance
                  
                  Expanded(
                    child: showProgress ? ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: progress,
                        backgroundColor: AppColors.textSecondary.withValues(alpha: 0.1),
                        valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                        minHeight: 8,
                      ),
                    ) : const SizedBox(),
                  ),
                  
                  const SizedBox(width: 48), // balance
                ],
              ),
            ),
            
            // Content
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 32),
                    child,
                  ],
                ),
              ),
            ),
            
            // Bottom action
            if (onNext != null)
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 10,
                      offset: const Offset(0, -5),
                    )
                  ],
                ),
                child: SafeArea(
                  top: false,
                  child: FilledButton(
                    onPressed: isNextEnabled ? onNext : null,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      minimumSize: const Size(double.infinity, 56),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: Text(
                      nextLabel,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
