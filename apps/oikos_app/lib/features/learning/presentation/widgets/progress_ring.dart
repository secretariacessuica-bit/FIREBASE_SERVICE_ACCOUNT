import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';

class ProgressRing extends StatelessWidget {
  final double percentage; // 0.0 to 1.0
  final double size;
  final Color activeColor;
  final Color backgroundColor;
  final double strokeWidth;

  const ProgressRing({
    super.key,
    required this.percentage,
    this.size = 60.0,
    this.activeColor = AppColors.primary,
    this.backgroundColor = const Color(0xFFE5E7EB),
    this.strokeWidth = 6.0,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          TweenAnimationBuilder<double>(
            tween: Tween<double>(begin: 0.0, end: percentage),
            duration: const Duration(milliseconds: 800),
            curve: Curves.easeOutCubic,
            builder: (context, value, _) {
              return CircularProgressIndicator(
                value: value,
                strokeWidth: strokeWidth,
                backgroundColor: backgroundColor,
                valueColor: AlwaysStoppedAnimation<Color>(activeColor),
                strokeCap: StrokeCap.round,
              );
            },
          ),
          Text(
            '${(percentage * 100).toInt()}%',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: size * 0.25,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}
