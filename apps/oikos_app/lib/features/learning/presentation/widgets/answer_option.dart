import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/answer.dart';

class AnswerOption extends StatelessWidget {
  final Answer answer;
  final bool isSelected;
  final bool hasSubmitted;
  final VoidCallback onTap;

  const AnswerOption({
    super.key,
    required this.answer,
    required this.isSelected,
    required this.hasSubmitted,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    Color backgroundColor = AppColors.surface;
    Color borderColor = AppColors.textPrimary.withOpacity(0.1);
    Color textColor = AppColors.textPrimary;

    if (hasSubmitted) {
      if (answer.isCorrect) {
        backgroundColor = AppColors.lumoGreen.withOpacity(0.1);
        borderColor = AppColors.lumoGreen;
        textColor = AppColors.lumoGreen;
      } else if (isSelected) {
        // Selected but wrong
        backgroundColor = Colors.redAccent.withOpacity(0.1);
        borderColor = Colors.redAccent;
        textColor = Colors.redAccent;
      }
    } else if (isSelected) {
      backgroundColor = AppColors.primary.withOpacity(0.05);
      borderColor = AppColors.primary;
      textColor = AppColors.primary;
    }

    return GestureDetector(
      onTap: hasSubmitted ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeInOut,
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
        decoration: BoxDecoration(
          color: backgroundColor,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: borderColor, width: 2),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(
                answer.text,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                  color: textColor,
                ),
              ),
            ),
            if (hasSubmitted && answer.isCorrect)
              const Icon(Icons.check_circle_rounded, color: AppColors.lumoGreen)
            else if (hasSubmitted && isSelected && !answer.isCorrect)
              const Icon(Icons.cancel_rounded, color: Colors.redAccent)
            else if (isSelected)
              const Icon(Icons.radio_button_checked_rounded, color: AppColors.primary)
            else
              Icon(Icons.radio_button_unchecked_rounded, color: AppColors.textPrimary.withOpacity(0.2)),
          ],
        ),
      ),
    );
  }
}
