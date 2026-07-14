import 'package:flutter/material.dart';
import '../../app/theme/app_colors.dart';
import '../../app/theme/app_typography.dart';
import '../../shared/widgets/guardian_header.dart';
import '../../shared/widgets/family_avatar_card.dart';
import '../pin/pin_page.dart';

class AvatarPage extends StatelessWidget {
  const AvatarPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '🏡 Família Oliveira',
                style: AppTypography.heading2,
              ),
              const SizedBox(height: 24),
              const GuardianHeader(),
              const SizedBox(height: 32),
              Text(
                'Quem vai aprender agora?',
                style: AppTypography.bodyLarge.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 24),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 16,
                crossAxisSpacing: 16,
                childAspectRatio: 0.9,
                children: [
                  FamilyAvatarCard(
                    name: 'João',
                    emoji: '😀',
                    backgroundColor: Colors.blue,
                    onTap: () => _navigateToPin(context, 'João'),
                  ),
                  FamilyAvatarCard(
                    name: 'Maria',
                    emoji: '😊',
                    backgroundColor: Colors.pink,
                    onTap: () => _navigateToPin(context, 'Maria'),
                  ),
                  FamilyAvatarCard(
                    name: 'Pedro',
                    emoji: '😎',
                    backgroundColor: Colors.orange,
                    onTap: () => _navigateToPin(context, 'Pedro'),
                  ),
                  FamilyAvatarCard(
                    name: 'Sofia',
                    emoji: '👧',
                    backgroundColor: Colors.purple,
                    onTap: () => _navigateToPin(context, 'Sofia'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _navigateToPin(BuildContext context, String name) {
    Navigator.of(context).push(
      PageRouteBuilder(
        pageBuilder: (context, animation, secondaryAnimation) => PinPage(userName: name),
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          const begin = Offset(1.0, 0.0);
          const end = Offset.zero;
          const curve = Curves.easeInOutQuart;
          var tween = Tween(begin: begin, end: end).chain(CurveTween(curve: curve));
          return SlideTransition(position: animation.drive(tween), child: child);
        },
        transitionDuration: const Duration(milliseconds: 500),
      ),
    );
  }
}
