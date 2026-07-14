import 'package:flutter/material.dart';

class AvatarPage extends StatelessWidget {
  const AvatarPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text('Avatar'),
            ElevatedButton(
              onPressed: () => Navigator.pushReplacementNamed(context, '/pin'),
              child: const Text('Next'),
            ),
          ],
        ),
      ),
    );
  }
}
