import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../providers/exercise_provider.dart';
import '../providers/current_journey_provider.dart';
import '../widgets/completion_banner.dart';
import '../widgets/xp_dialog.dart';

class ResultPage extends ConsumerStatefulWidget {
  const ResultPage({super.key});

  @override
  ConsumerState<ResultPage> createState() => _ResultPageState();
}

class _ResultPageState extends ConsumerState<ResultPage> {
  bool _showXp = false;

  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(milliseconds: 1500), () {
      if (mounted) {
        setState(() {
          _showXp = true;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(exerciseProvider);
    final earnedXp = state.correctAnswers * 10; // Simplified logic

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Stack(
          children: [
            Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0.0, end: 1.0),
                  duration: const Duration(milliseconds: 800),
                  curve: Curves.easeOutCubic,
                  builder: (context, value, child) {
                    return Transform.translate(
                      offset: Offset(0, 50 * (1 - value)),
                      child: Opacity(
                        opacity: value,
                        child: child,
                      ),
                    );
                  },
                  child: CompletionBanner(
                    correctAnswers: state.correctAnswers,
                    totalQuestions: state.exercises.length,
                    earnedXp: earnedXp,
                  ),
                ),
              ),
            ),
            
            if (_showXp)
              Container(
                color: Colors.black.withOpacity(0.4),
                child: Center(
                  child: XPDialog(
                    xpAmount: earnedXp,
                    onContinue: () {
                      // Navigate back to Home or Journey
                      Navigator.of(context).popUntil((route) => route.isFirst);
                    },
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
