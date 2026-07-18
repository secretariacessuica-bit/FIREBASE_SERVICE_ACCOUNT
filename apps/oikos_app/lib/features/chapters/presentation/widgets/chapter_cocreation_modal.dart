import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../domain/entities/household_chapter.dart';

class ChapterCocrationModal extends StatelessWidget {
  final HouseholdChapter suggestedChapter;
  final VoidCallback onAccept;
  final VoidCallback onRename;
  final VoidCallback onPostpone;

  const ChapterCocrationModal({
    super.key,
    required this.suggestedChapter,
    required this.onAccept,
    required this.onRename,
    required this.onPostpone,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.auto_awesome, color: AppColors.accent, size: 32),
          const SizedBox(height: 16),
          const Text(
            "Um Novo Capítulo se Encerra",
            style: TextStyle(fontSize: 14, letterSpacing: 1.5, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          Text(
            suggestedChapter.chapterTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 24, fontFamily: 'Serif', color: AppColors.textPrimary),
          ),
          const SizedBox(height: 16),
          Text(
            suggestedChapter.chapterReflection,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 16, height: 1.5, fontStyle: FontStyle.italic, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 32),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              TextButton(
                onPressed: onPostpone,
                child: const Text("Ainda não", style: TextStyle(color: AppColors.textSecondary)),
              ),
              TextButton(
                onPressed: onRename,
                child: const Text("Renomear", style: TextStyle(color: AppColors.textSecondary)),
              ),
              ElevatedButton(
                onPressed: onAccept,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: const Text("Guardar no Livro"),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
