import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../domain/entities/enduring_value.dart';

class LegacyCocreationModal extends StatelessWidget {
  final EnduringValue suggestedValue;
  final VoidCallback onAccept;
  final VoidCallback onRename;
  final VoidCallback onPostpone;

  const LegacyCocreationModal({
    super.key,
    required this.suggestedValue,
    required this.onAccept,
    required this.onRename,
    required this.onPostpone,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.account_balance, color: AppColors.accent, size: 40),
          const SizedBox(height: 24),
          const Text(
            "Um Novo Pilar foi Revelado",
            style: TextStyle(fontSize: 12, letterSpacing: 2.0, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 16),
          Text(
            suggestedValue.familyName,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 28, fontFamily: 'Serif', color: AppColors.textPrimary),
          ),
          const SizedBox(height: 16),
          Text(
            suggestedValue.reflection,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 16, height: 1.6, fontStyle: FontStyle.italic, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 40),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              TextButton(
                onPressed: onPostpone,
                child: const Text("Ainda não", style: TextStyle(color: AppColors.textSecondary)),
              ),
              TextButton(
                onPressed: onRename,
                child: const Text("Dar outro nome", style: TextStyle(color: AppColors.textSecondary)),
              ),
              ElevatedButton(
                onPressed: onAccept,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: const Text("Erguer este Pilar", style: TextStyle(letterSpacing: 1.0)),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
