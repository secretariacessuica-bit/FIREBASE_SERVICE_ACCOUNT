import 'package:flutter/material.dart';
import 'core/theme.dart';
import 'presentation/pages/login_page.dart';

void main() {
  runApp(const CMELausanneApp());
}

class CMELausanneApp extends StatelessWidget {
  const CMELausanneApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CME Lausanne MVP',
      theme: AppTheme.lightTheme,
      home: const LoginPage(),
      debugShowCheckedModeBanner: false,
    );
  }
}
