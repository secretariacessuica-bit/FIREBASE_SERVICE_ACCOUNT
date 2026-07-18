import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/member_identity.dart';

class IdentityHeader extends StatelessWidget {
  final MemberIdentity identity;

  const IdentityHeader({
    super.key,
    required this.identity,
  });

  String _getExpressionEmoji() {
    switch (identity.currentExpression) {
      case IdentityExpression.happy: return '😄';
      case IdentityExpression.proud: return '⭐';
      case IdentityExpression.curious: return '🤔';
      case IdentityExpression.calm: default: return '🙂';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Stack(
          alignment: Alignment.bottomRight,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: identity.favoriteColor.withOpacity(0.15),
                shape: BoxShape.circle,
                border: Border.all(color: identity.favoriteColor.withOpacity(0.5), width: 4),
              ),
              child: Center(
                child: Text(
                  identity.name.substring(0, 1).toUpperCase(),
                  style: TextStyle(
                    fontSize: 48,
                    fontWeight: FontWeight.w800,
                    color: identity.favoriteColor,
                  ),
                ),
              ),
            ),
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppColors.surface,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.background, width: 3),
              ),
              child: Text(
                _getExpressionEmoji(),
                style: const TextStyle(fontSize: 24),
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        Text(
          identity.name,
          style: const TextStyle(
            fontSize: 32,
            fontWeight: FontWeight.w900,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: identity.favoriteColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            'Aprendendo conosco há ${identity.daysLearning} dias',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: identity.favoriteColor,
            ),
          ),
        ),
      ],
    );
  }
}
