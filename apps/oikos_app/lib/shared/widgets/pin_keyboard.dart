import 'package:flutter/material.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';

class PinKeyboard extends StatelessWidget {
  final Function(String) onDigitPressed;
  final Color themeColor;
  
  const PinKeyboard({
    super.key, 
    required this.onDigitPressed,
    this.themeColor = AppColors.primary,
  });

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        childAspectRatio: 1.5,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: 12,
      itemBuilder: (context, index) {
        if (index == 9) {
          // X / cancel button
          return GestureDetector(
            onTap: () => onDigitPressed('cancel'),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.red.shade300,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Center(
                child: Icon(Icons.close_rounded, color: Colors.white, size: 28),
              ),
            ),
          );
        }
        
        if (index == 11) {
          // Backspace
          return GestureDetector(
            onTap: () => onDigitPressed('backspace'),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.blue.shade400,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Center(
                child: Icon(Icons.backspace_rounded, color: Colors.white, size: 28),
              ),
            ),
          );
        }
        
        final digit = index == 10 ? '0' : '${index + 1}';
        
        return GestureDetector(
          onTap: () => onDigitPressed(digit),
          child: Container(
            decoration: BoxDecoration(
              color: themeColor.withOpacity(0.3),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: themeColor.withOpacity(0.5),
                width: 2,
              ),
            ),
            child: Center(
              child: Text(
                digit,
                style: AppTypography.heading2.copyWith(
                  color: themeColor.withOpacity(0.9),
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

