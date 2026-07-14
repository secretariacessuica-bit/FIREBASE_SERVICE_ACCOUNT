import 'package:flutter/material.dart';

class OnboardingPage extends StatelessWidget {
  const OnboardingPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('Onboarding'),
            ElevatedButton(
              onPressed: () => Navigator.pushReplacementNamed(context, '/avatar'),
              child: const Text('Next'),
            ),
          ],
        ),
      ),
    );
  }
}
