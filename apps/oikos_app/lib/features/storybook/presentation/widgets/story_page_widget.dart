import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/story.dart';
import '../../domain/entities/story_illustration_type.dart';

class StoryPageWidget extends StatelessWidget {
  final Story story;

  const StoryPageWidget({
    super.key,
    required this.story,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.background,
      padding: const EdgeInsets.all(32.0),
      child: SingleChildScrollView(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
          // Ilustração
          Container(
            padding: const EdgeInsets.all(48),
            decoration: BoxDecoration(
              color: AppColors.surface,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 32,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            child: Text(
              story.illustration.emoji,
              style: const TextStyle(fontSize: 96),
            ),
          ),
          const SizedBox(height: 64),
          
          // Título
          Text(
            story.title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.w900,
              color: AppColors.textPrimary,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 24),
          
          // Narrativa
          Text(
            story.narrative,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w500,
              color: AppColors.textPrimary,
              height: 1.6,
            ),
          ),
          const SizedBox(height: 48),
          
              // Reflexão do Lumo
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                decoration: BoxDecoration(
                  color: AppColors.lumoGreen.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text('🌱', style: TextStyle(fontSize: 20)),
                    const SizedBox(width: 12),
                    Flexible(
                      child: Text(
                        story.lumoReflection,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 64),
              
              // Promote to Memory Button (Bridge)
              IconButton(
                icon: const Text('⭐', style: TextStyle(fontSize: 32)),
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Guardado na Caixa de Tesouros!')),
                  );
                },
              ),
            ],
        ),
      ),
    );
  }
}
