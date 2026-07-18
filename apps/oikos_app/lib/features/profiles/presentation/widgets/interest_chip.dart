import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';

class InterestChip extends StatelessWidget {
  final String interest;
  final Color baseColor;

  const InterestChip({
    super.key,
    required this.interest,
    required this.baseColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: baseColor.withOpacity(0.3), width: 1.5),
        boxShadow: [
          BoxShadow(
            color: baseColor.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Text(
        interest,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
        ),
      ),
    );
  }
}
