import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../../features/domain/entities/family_member.dart';
import 'avatar_widget.dart';

class FamilyAvatarCard extends StatefulWidget {
  final FamilyMember member;
  final VoidCallback onTap;

  const FamilyAvatarCard({
    super.key,
    required this.member,
    required this.onTap,
  });

  @override
  State<FamilyAvatarCard> createState() => _FamilyAvatarCardState();
}

class _FamilyAvatarCardState extends State<FamilyAvatarCard> {
  bool _isHovered = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: widget.onTap,
      child: MouseRegion(
        onEnter: (_) => setState(() => _isHovered = true),
        onExit: (_) => setState(() => _isHovered = false),
        child: AnimatedScale(
          scale: _isHovered ? 1.05 : 1.0,
          duration: const Duration(milliseconds: 200),
          curve: Curves.easeOutBack,
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(32),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 16,
                  offset: const Offset(0, 4),
                ),
                if (_isHovered)
                  BoxShadow(
                    color: Color(int.parse(widget.member.colorHex.replaceFirst('#', '0xFF'))).withOpacity(0.3),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
              ],
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                AvatarWidget(
                  member: widget.member,
                  size: 100,
                ),
                const SizedBox(height: 16),
                Text(
                  widget.member.name,
                  style: AppTypography.heading2.copyWith(fontSize: 20),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

