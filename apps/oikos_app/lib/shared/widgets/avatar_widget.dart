import 'package:flutter/material.dart';
import '../../features/domain/entities/family_member.dart';
import '../../features/avatar/domain/avatar.dart';
import '../../features/avatar/presentation/avatar_renderer.dart';

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
    // Tenta parsear como avatar dinâmico (JSON puro ou URL-encoded)
    final customAvatar = OikosAvatar.tryFromAvatarAsset(member.avatarAsset);

    if (customAvatar != null) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.08),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: ClipOval(
          child: Center(
            child: SizedBox(
              width: size * 1.5,
              height: size * 1.5,
              child: OikosAvatarRenderer(avatar: customAvatar, size: size * 1.5),
            ),
          ),
        ),
      );
    }

    // Asset estático (PNG/JPG) — só se não for JSON
    if (member.avatarAsset != null && 
        member.avatarAsset!.isNotEmpty && 
        !OikosAvatar.isAvatarJson(member.avatarAsset)) {
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
    
    // Fallback: emoji com cor do membro
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
