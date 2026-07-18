import 'package:flutter/material.dart';
import '../../features/domain/entities/family_member.dart';

class AvatarWidget extends StatelessWidget {
  final FamilyMember member;
  final double size;

  const AvatarWidget({
    super.key,
    required this.member,
    this.size = 60,
  });

  @override
  Widget build(BuildContext context) {
    if (member.avatarAsset != null && member.avatarAsset!.isNotEmpty) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          image: DecorationImage(
            image: AssetImage(member.avatarAsset!),
            fit: BoxFit.cover,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
      );
    }
    
    // Fallback to emoji
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Color(int.parse(member.colorHex.replaceAll('#', '0xFF'))).withOpacity(0.2),
        shape: BoxShape.circle,
        border: Border.all(
          color: Color(int.parse(member.colorHex.replaceAll('#', '0xFF'))),
          width: 2,
        ),
      ),
      child: Center(
        child: Text(
          member.emoji,
          style: TextStyle(fontSize: size * 0.5),
        ),
      ),
    );
  }
}
