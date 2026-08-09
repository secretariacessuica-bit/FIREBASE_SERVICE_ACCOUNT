import 'package:flutter/material.dart';

class KidsWelcomePage extends StatefulWidget {
  const KidsWelcomePage({super.key});

  @override
  State<KidsWelcomePage> createState() => _KidsWelcomePageState();
}

class _KidsWelcomePageState extends State<KidsWelcomePage> {
  final TextEditingController _nameController = TextEditingController();
  int? _selectedAge;

  void _finishOnboarding() {
    if (_nameController.text.trim().isNotEmpty && _selectedAge != null) {
      // TODO: Salvar perfil infantil localmente
      Navigator.pushReplacementNamed(context, '/learning');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Preencha seu nome e escolha sua idade!')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFE8F5E9), // Verde bem clarinho e lúdico
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 32.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Placeholder para o Mascote Lumo
              Center(
                child: Container(
                  width: 120,
                  height: 120,
                  decoration: const BoxDecoration(
                    color: Color(0xFF88B04B),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.face, size: 80, color: Colors.white),
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Oi! Eu sou o Lumo!',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF2C3E50),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              const Text(
                'Como você se chama?',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF34495E),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _nameController,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                decoration: InputDecoration(
                  hintText: 'Seu Nome',
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(32),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
              const SizedBox(height: 48),
              const Text(
                'Quantos anos você tem?',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF34495E),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              Wrap(
                alignment: WrapAlignment.center,
                spacing: 16,
                runSpacing: 16,
                children: [
                  for (int i = 4; i <= 9; i++)
                    _buildAgeButton(i),
                ],
              ),
              const SizedBox(height: 64),
              SizedBox(
                height: 64,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF88B04B),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(32)),
                    elevation: 4,
                  ),
                  onPressed: _finishOnboarding,
                  child: const Text(
                    'VAMOS BRINCAR!',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAgeButton(int age) {
    final isSelected = _selectedAge == age;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedAge = age;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        width: 80,
        height: 80,
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFFFB74D) : Colors.white, // Laranja divertido se selecionado
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: isSelected ? const Color(0xFFFFB74D).withValues(alpha: 0.4) : Colors.black12,
              blurRadius: 12,
              offset: const Offset(0, 6),
            ),
          ],
          border: isSelected ? null : Border.all(color: Colors.grey.withValues(alpha: 0.2)),
        ),
        child: Center(
          child: Text(
            age.toString(),
            style: TextStyle(
              fontSize: 36,
              fontWeight: FontWeight.bold,
              color: isSelected ? Colors.white : const Color(0xFF2C3E50),
            ),
          ),
        ),
      ),
    );
  }
}
