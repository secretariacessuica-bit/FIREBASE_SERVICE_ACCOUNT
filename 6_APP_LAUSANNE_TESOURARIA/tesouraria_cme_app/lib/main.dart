import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'core/theme.dart';
import 'presentation/pages/login_page.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('pt_BR', null);
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
