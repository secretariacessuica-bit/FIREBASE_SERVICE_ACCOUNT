import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/reflection.dart';

class ReflectionCard extends StatelessWidget {
  final Reflection reflection;

  const ReflectionCard({
    super.key,
    required this.reflection,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.textPrimary.withOpacity(0.05)),
      ),
      child: Column(
        children: [
          const Text('🌱', style: TextStyle(fontSize: 24)),
          const SizedBox(height: 16),
          Text(
            reflection.text,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w500,
              color: AppColors.textSecondary,
              fontStyle: FontStyle.italic,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
